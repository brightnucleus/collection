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

		$this->clear();
	}

	/**
	 * {@inheritDoc}
	 */
	public function add( $element ) {
		return parent::add( $this->normalizeEntity( $element ) );
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
			$this->collection = new static(
				$this->postID,
				$this->collection->matching( $criteria )
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
			$this->collection->add( $this->normalizeEntity( $post ) );
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
			function ( Property $property ) { return $property->toArray(); },
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
