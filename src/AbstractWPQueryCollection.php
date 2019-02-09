<?php
/**
 * Bright Nucleus WP_Query Collection
 *
 * @package   BrightNucleus\Collection
 * @author    Alain Schlesser <alain.schlesser@gmail.com>
 * @license   MIT+
 * @link      http://www.brightnucleus.com/
 * @copyright 2018 Alain Schlesser, Bright Nucleus
 */

namespace BrightNucleus\Collection;

use BrightNucleus\Exception\InvalidArgumentException;
use BrightNucleus\Exception\RuntimeException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use stdClass;
use WP_Post;
use WP_Query;

/**
 * Class AbstractWPQueryCollection
 *
 * @package BrightNucleus\Collection
 * @author  Alain Schlesser <alain.schlesser@gmail.com>
 */
abstract class AbstractWPQueryCollection extends LazilyHydratedCollection implements WPQueryCollection, PropertyCacheAware {

	use IdentityMapping;
	use PropertyCaching;

	/** @var IdentityMapPool */
	protected static $identityMapPool;

	/**
	 * Query to use for hydrating the collection.
	 *
	 * @var WP_Query
	 */
	protected $query;

	/** @var int */
	protected $countCache;

	/**
	 * Instantiate a AbstractWPQueryCollection object.
	 *
	 * @param Criteria|WP_Query|Iterable|null $argument    Construction
	 *                                                     argument to use.
	 * @param IdentityMap                     $identityMap Optional. Identity
	 *                                                     map implementation
	 *                                                     to use.
	 */
	public function __construct( $argument = null, IdentityMap $identityMap = null ) {
		if ( null === $identityMap ) {
			$identityMap = $this->createIdentityMap();
		}

		$this->identityMap = $identityMap;

		if ( $argument instanceof Criteria ) {
			$this->criteria = $argument;
			return;
		}

		$this->criteria = new NullCriteria();

		if ( is_iterable( $argument ) ) {
			$this->collection = new ArrayCollection();
			$this->isHydrated = true;
			foreach ( $argument as $element ) {
				$this->add( $element );
			}
			return;
		}

		if ( $argument instanceof WP_Query ) {
			$this->query = $argument;
			return;
		}

		$this->clear();
	}

	/**
	 * {@inheritDoc}
	 */
	public function add( $element ) {
		$element = $this->deduplicated(
			( new IdDeducer() )->deduceId( $element ),
			function ( $id ) use ( $element ) {
				return $this->normalizeEntity( $element );
			}
		);
		$this->assertType( $element );

		unset( $this->countCache );

		return parent::add( $element );
	}

	/**
	 * {@inheritDoc}
	 */
	public function count() {
		global $wpdb;
		if ( $this->query ) {
			// TODO: Check whether this is even populated.
			return $this->query->found_posts;
		}

		if ( ! $this->isHydrated ) {
			if ( isset( $this->countCache ) ) {
				return $this->countCache;
			}

			$subQuery = implode( ' ', array_filter( [
				$this->getQueryGenerator()->getSelectClause(),
				$this->getQueryGenerator()->getFromClause(),
				$this->getQueryGenerator()->getWhereClause(),
				$this->getQueryGenerator()->getOrderByClause(),
				$this->getQueryGenerator()->getLimitClause(),
			] ) );

			$query = "SELECT COUNT(*) FROM ({$subQuery}) as count;";

			$result = $wpdb->get_results( $query );

			$this->countCache = (int) current(
				array_column( $result, 'COUNT(*)' )
			);

			return $this->countCache;
		}

		return parent::count();
	}

	/**
	 * Selects all elements from a selectable that match the expression and
	 * returns a new collection containing these elements.
	 *
	 * @param Criteria $criteria
	 *
	 * @return PostTypeCollection
	 */
	public function matching( Criteria $criteria ) {
		$collection = clone $this;

		unset( $collection->countCache );

		if ( $collection->query ) {
			// TODO: This should actually modify the WP_QUERY arguments.
			$collection->doHydrate();
			$collection->isHydrated = true;
		}

		$collection->criteria = $collection->criteria->merge( $criteria );

		if ( $collection->isHydrated ) {
			$collection->collection = $collection->collection->matching( $criteria );
		}

		return $collection;
	}

	/**
	 * Get the current criteria of the selectable.
	 *
	 * @return Criteria Current criteria of the selectable.
	 */
	public function getCriteria(): Criteria {
		return clone $this->criteria;
	}

	/**
	 * Do the actual hydration logic.
	 *
	 * @return void
	 */
	protected function doHydrate(): void {
		global $wpdb;
		$this->collection = new ArrayCollection();

		$posts = [];

		if ( $this->query ) {
			$posts = $this->query->get_posts();
		} elseif ( $this->criteria ) {
			$query = $this->getQueryGenerator()->getQuery();

			$posts = $wpdb->get_results( $query );
		}

		$this->query = false;

		if ( empty( $posts ) ) {
			return;
		}

		$this->primePropertyCache( $posts );

		$idDeducer = new IdDeducer();

		foreach ( $posts as $post ) {
			$post = $this->deduplicated(
				$idDeducer->deduceId( $post ),
				function ( $id ) use ( $post ) {
					return $this->normalizeEntity( $post );
				}
			);
			$this->assertType( $post );
			$this->collection->add( $post );
		}
	}

	/**
	 * Normalize the entity and cast it to the correct type.
	 *
	 * @param mixed $entity Entity to normalize.
	 * @return mixed Normalized entity.
	 */
	protected function normalizeEntity( $entity ) {
		if ( $entity instanceof stdClass ) {
			$entity = get_post( $entity );
		}

		if ( $this instanceof HasEntityWrapper ) {
			$wrapper = $this->getEntityWrapperClass();
			if ( ! $entity instanceof $wrapper ) {
				$entity = new $wrapper( $entity );
			}
		}

		return $entity;
	}

	/**
	 * Create an instance of the identity map to use.
	 *
	 * @return IdentityMap Identity map to use.
	 */
	protected function createIdentityMap(): IdentityMap {
		if ( null === static::$identityMapPool ) {
			static::$identityMapPool = new IdentityMapPool();
		}

		return static::$identityMapPool->getIdentityMap(
			$this->getIdentityMapType(),
			GenericIdentityMap::class
		);
	}

	/**
	 * Get the type of identity map to use.
	 *
	 * @return string Type of the identity map.
	 */
	protected function getIdentityMapType(): string {
		return get_class( $this );
	}

	/**
	 * Prime the property cache for all posts in the collection.
	 *
	 * @return void
	 */
	protected function primePropertyCache( array $posts ): void {
		if ( null !== $this->propertyCache ) {
			return;
		}

		$idDeducer = new IdDeducer();

		$ids = array_map(
			function ( $element ) use ( $idDeducer ) {
				return $idDeducer->deduceId( $element );
			},
			$posts
		);

		$this->propertyCache = new PostMetaPropertyCache( $ids );
	}

	/**
	 * Assert that the element corresponds to the correct type for the
	 * collection.
	 *
	 * @param mixed $element Element to assert the type of.
	 *
	 * @throws InvalidArgumentException If the type didn't match.
	 */
	abstract protected function assertType( $element ): void;

	/**
	 * Get the query generator to use.
	 *
	 * @return QueryGenerator
	 */
	abstract protected function getQueryGenerator(): QueryGenerator;
}
