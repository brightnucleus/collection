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

use ArrayIterator;
use BrightNucleus\Exception\InvalidArgumentException;
use BrightNucleus\Exception\RangeException;
use Doctrine\Common\Collections\ArrayCollection;
use WP_Meta_Query;
use WP_Post;

/**
 * Class AbstractPropertyCollection
 *
 * @package BrightNucleus\Collection
 * @author  Alain Schlesser <alain.schlesser@gmail.com>
 */
abstract class AbstractPropertyCollection
	extends LazilyHydratedCollection
	implements PropertyCollection {

	/**
	 * Post ID of the post that the post meta belongs to.
	 *
	 * @var int
	 */
	protected $postID;

	/**
	 * Instantiate a AbstractPropertyCollection object.
	 *
	 * @param WP_Post|int|string                   $postID   Post or post ID of
	 *                                                       the post that the
	 *                                                       post meta belongs
	 *                                                       to.
	 * @param Criteria|WP_Meta_Query|Iterable|null $argument Construction
	 *                                                       argument to use.
	 */
	public function __construct( $postID, $argument = null ) {
		if ( $postID instanceof WP_Post ) {
			$postID = $postID->ID;
		}

		if ( ! is_int( $postID ) && ! ( is_string( $postID ) && ctype_digit( $postID ) ) ) {
			throw new InvalidArgumentException(
				'Invalid type provided for argument $post, requires either a WP_Post instance or a post ID.'
			);
		}

		$this->postID = $postID;

		$expr           = Criteria::expr();
		$this->criteria = Criteria::create()
		                          ->where( $expr->eq( 'post_id', $postID ) );

		if ( $argument instanceof Criteria ) {
			$this->criteria = $this->criteria->merge( $argument );
			return;
		}

		if ( is_iterable( $argument ) ) {
			$this->collection = new ArrayCollection();
			$this->isHydrated = true;
			foreach ( $argument as $element ) {
				$this->add( $element );
			}
			return;
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function containsKey( $key ) {
		$this->hydrate();
		return $this->exists(
			function ( $index, $element ) use ( $key ) {
				/** @var Property $element */
				return $element->getKey() === $key;
			}
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function add( $element ) {
		$this->hydrate();
		$element = $this->normalizeEntity( $element );
		return $this->collection[ $element->getKey() ] = $element;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get( $key ) {
		if ( $this->isHydrated ) {
			return $this->getProperty( $key )->getValue();
		}

		$expr     = Criteria::expr();
		$criteria = Criteria::create()
		                    ->where( $expr->eq( 'key', $key ) );

		return $this->matching( $criteria )->first()->getValue();
	}

	/**
	 * Get the property object stored under a given key.
	 *
	 * @param string $key Key to retrieve the property for.
	 *
	 * @return Property Property stored under the requested key.
	 */
	public function getProperty( string $key ): Property {
		$property = parent::get( $key );

		if ( null === $property ) {
			throw new RangeException( "Could not retrieve property for key {$key}." );
		}

		return $property;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getIterator() {
		$this->hydrate();
		return new ArrayIterator( $this->toArray() );
	}

	/**
	 * Selects all elements from a selectable that match the expression and
	 * returns a new collection containing these elements.
	 *
	 * @param Criteria $criteria
	 *
	 * @return PropertyCollection
	 */
	public function matching( Criteria $criteria ) {
		if ( $this->isHydrated ) {
			$this->collection = new ArrayCollection(
				$this->collection->matching( $criteria )->toArray()
			);
			$this->criteria   = $criteria;
			$this->isHydrated = true;
		}

		$collection = clone $this;

		$collection->criteria = $collection->criteria->merge( $criteria );

		return $collection;
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

		if ( $this->criteria ) {
			$query = $this->getQueryGenerator()->getQuery();

			$posts = $wpdb->get_results( $query );
		}

		if ( empty( $posts ) ) {
			return;
		}

		foreach ( $posts as $post ) {
			$property = $this->normalizeEntity( $post );
			$this->collection[ $property->getKey() ] = $property;
		}
	}

	/**
	 * Normalize the entity and cast it to the correct type.
	 *
	 * @param mixed $entity Entity to normalize.
	 * @return mixed Normalized entity.
	 */
	protected function normalizeEntity( $entity ) {
		if ( ! $entity instanceof Property ) {
			$entity = new Property( $entity );
		}

		return $entity;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray() {
		$properties = parent::toArray();

		return array_map(
			function ( Property $property ) { return $property->getValue(); },
			$properties
		);
	}

	/**
	 * Get the query generator to use.
	 *
	 * @return QueryGenerator
	 */
	abstract protected function getQueryGenerator(): QueryGenerator;
}
