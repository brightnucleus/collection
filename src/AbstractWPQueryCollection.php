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
use Doctrine\Common\Collections\ArrayCollection;
use stdClass;
use WP_Query;

/**
 * Class AbstractWPQueryCollection
 *
 * @package BrightNucleus\Collection
 * @author  Alain Schlesser <alain.schlesser@gmail.com>
 */
abstract class AbstractWPQueryCollection extends LazilyHydratedCollection implements WPQueryCollection {

	/**
	 * Query to use for hydrating the collection.
	 *
	 * @var WP_Query
	 */
	protected $query;

	/**
	 * Instantiate a AbstractWPQueryCollection object.
	 *
	 * @param Criteria|WP_Query|Iterable|null $argument Construction argument to
	 *                                                  use.
	 */
	public function __construct( $argument = null ) {
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
		$element = $this->normalizeEntity( $element );
		$this->assertType( $element );
		return parent::add( $element );
	}

	/**
	 * Selects all elements from a selectable that match the expression and
	 * returns a new collection containing these elements.
	 *
	 * @param Criteria $criteria
	 *
	 * @return PostCollection
	 */
	public function matching( Criteria $criteria ) {
		if ( $this->query ) {
			// TODO: This should actually modify the WP_QUERY arguments.
			$this->doHydrate();
			$this->isHydrated = true;
		}

		if ( $this->isHydrated ) {
			$this->collection = new static( $this->collection->matching( $criteria ) );
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

		if ( $this->query ) {
			$posts = $this->query->get_posts();
		} elseif ( $this->criteria ) {
			$query = $this->getQueryGenerator()->getQuery();

			$posts = $wpdb->get_results( $query );
		}

		if ( empty( $posts ) ) {
			return;
		}

		foreach ( $posts as $post ) {
			$post = $this->normalizeEntity( $post );
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
