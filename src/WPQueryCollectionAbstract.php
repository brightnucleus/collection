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

use Closure;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use WP_Query;

/**
 * Class WPQueryCollectionAbstract
 *
 * @package BrightNucleus\Collection
 * @author  Alain Schlesser <alain.schlesser@gmail.com>
 */
abstract class WPQueryCollectionAbstract implements WPQueryCollection {

	/**
	 * The backed collection to use.
	 *
	 * @var ArrayCollection
	 */
	protected $collection;

	/**
	 * Query to use for hydrating the collection.
	 *
	 * @var WP_Query
	 */
	protected $query;

	/**
	 * Whether the collection was already hydrated.
	 *
	 * @var bool
	 */
	protected $isHydrated = false;

	/**
	 * Criteria to qualify the collection by.
	 *
	 * @var Criteria
	 */
	protected $criteria;

	/**
	 * Instantiate a WPQueryCollectionAbstract object.
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
	public function count() {
		// TODO: Make smarter to not automatically hydrate if not needed.
		$this->hydrate();
		return $this->collection->count();
	}

	/**
	 * {@inheritDoc}
	 */
	public function add( $element ) {
		$this->hydrate();
		return $this->collection->add( $element );
	}

	/**
	 * {@inheritDoc}
	 */
	public function clear() {
		$this->collection = new ArrayCollection();
		$this->isHydrated = true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function contains( $element ) {
		$this->hydrate();
		return $this->collection->contains( $element );
	}

	/**
	 * {@inheritDoc}
	 */
	public function isEmpty() {
		return $this->count() === 0;
	}

	/**
	 * {@inheritDoc}
	 */
	public function remove( $key ) {
		$this->hydrate();
		return $this->collection->remove( $key );
	}

	/**
	 * {@inheritDoc}
	 */
	public function removeElement( $element ) {
		$this->hydrate();
		return $this->collection->removeElement( $element );
	}

	/**
	 * {@inheritDoc}
	 */
	public function containsKey( $key ) {
		$this->hydrate();
		return $this->collection->containsKey( $key );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get( $key ) {
		$this->hydrate();
		return $this->collection->get( $key );
	}

	/**
	 * {@inheritDoc}
	 */
	public function getKeys() {
		$this->hydrate();
		return $this->collection->getKeys();
	}

	/**
	 * {@inheritDoc}
	 */
	public function getValues() {
		$this->hydrate();
		return $this->collection->getValues();
	}

	/**
	 * {@inheritDoc}
	 */
	public function set( $key, $value ) {
		$this->hydrate();
		$this->collection->set( $key, $value );
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray() {
		$this->hydrate();
		return $this->collection->toArray();
	}

	/**
	 * {@inheritDoc}
	 */
	public function first() {
		$this->hydrate();
		return $this->collection->first();
	}

	/**
	 * {@inheritDoc}
	 */
	public function last() {
		$this->hydrate();
		return $this->collection->last();
	}

	/**
	 * {@inheritDoc}
	 */
	public function key() {
		$this->hydrate();
		return $this->collection->key();
	}

	/**
	 * {@inheritDoc}
	 */
	public function current() {
		$this->hydrate();
		return $this->collection->current();
	}

	/**
	 * {@inheritDoc}
	 */
	public function next() {
		$this->hydrate();
		return $this->collection->next();
	}

	/**
	 * {@inheritDoc}
	 */
	public function exists( Closure $p ) {
		$this->hydrate();
		return $this->collection->exists( $p );
	}

	/**
	 * {@inheritDoc}
	 */
	public function filter( Closure $p ) {
		$this->hydrate();
		return $this->collection->filter( $p );
	}

	/**
	 * {@inheritDoc}
	 */
	public function forAll( Closure $p ) {
		$this->hydrate();
		return $this->collection->forAll( $p );
	}

	/**
	 * {@inheritDoc}
	 */
	public function map( Closure $func ) {
		$this->hydrate();
		return $this->collection->map( $func );
	}

	/**
	 * {@inheritDoc}
	 */
	public function partition( Closure $p ) {
		$this->hydrate();
		return $this->collection->partition( $p );
	}

	/**
	 * {@inheritDoc}
	 */
	public function indexOf( $element ) {
		$this->hydrate();
		return $this->collection->indexOf( $element );
	}

	/**
	 * {@inheritDoc}
	 */
	public function slice( $offset, $length = null ) {
		$this->hydrate();
		return $this->collection->slice( $offset, $length );
	}

	/**
	 * {@inheritDoc}
	 */
	public function getIterator() {
		$this->hydrate();
		return $this->collection->getIterator();
	}

	/**
	 * {@inheritDoc}
	 */
	public function offsetExists( $offset ) {
		$this->hydrate();
		return $this->collection->offsetExists( $offset );
	}

	/**
	 * {@inheritDoc}
	 */
	public function offsetGet( $offset ) {
		$this->hydrate();
		return $this->collection->offsetGet( $offset );
	}

	/**
	 * {@inheritDoc}
	 */
	public function offsetSet( $offset, $value ) {
		$this->hydrate();
		$this->collection->offsetSet( $offset, $value );
	}

	/**
	 * {@inheritDoc}
	 */
	public function offsetUnset( $offset ) {
		$this->hydrate();
		$this->collection->offsetUnset( $offset );
	}

	/**
	 * Is the query already hydrated?
	 *
	 * @return bool
	 */
	public function isHydrated(): bool {
		return $this->isHydrated;
	}

	/**
	 * Hydrate the collection.
	 *
	 * @return void
	 */
	protected function hydrate(): void {
		if ( ! $this->isHydrated ) {
			$this->doHydrate();
			$this->isHydrated = true;
		}
	}

	/**
	 * Selects all elements from a selectable that match the expression and
	 * returns a new collection containing these elements.
	 *
	 * @param Criteria $criteria
	 *
	 * @return Collection
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

		if ( $this->query ) {
			$posts = $this->query->get_posts();
			foreach ( $posts as $post ) {
				$this->collection->add( $post );
			}

			return;
		}

		if ( $this->criteria ) {
			$query = implode( ' ', array_filter( [
				$this->getSelectClause(),
				$this->getFromClause(),
				$this->getWhereClause(),
				$this->getOrderByClause(),
				$this->getLimitClause(),
			] ) );

			$posts = $wpdb->get_results( $query );
			if ( $posts ) {
				$posts = array_map( 'get_post', $posts );
			}

			foreach ( $posts as $post ) {
				$this->collection->add( $post );
			}
		}
	}

	/**
	 * Get the table name that the collection is based on.
	 *
	 * @return string
	 */
	abstract protected function getTableName(): string;

	/**
	 * Get SELECT clause.
	 *
	 * @return string SELECT clause.
	 */
	protected function getSelectClause() {
		$fields = [ '*' ];
		return 'SELECT ' . implode( ',', $fields );
	}

	/**
	 * Get the FROM clause.
	 *
	 * @return string FROM clause.
	 */
	protected function getFromClause(): string {
		global $wpdb;
		$from = $wpdb->get_blog_prefix() . static::getTableName();
		return "FROM {$from}";
	}

	/**
	 * Get the WHERE clause.
	 *
	 * Can be an empty string.
	 *
	 * @return string WHERE clause.
	 */
	protected function getWhereClause(): string {
		if ( $this->criteria instanceof NullCriteria ) {
			return '';
		}

		$expression = $this->criteria->getWhereExpression();

		if ( null === $expression ) {
			return '';
		}

		$visitor = new WPQuerySQLExpressionVisitor();
		return 'WHERE ' . $visitor->dispatch( $expression );
	}

	/**
	 * Get the ORDER BY clause.
	 *
	 * Can be an empty string.
	 *
	 * @return string ORDER BY clause.
	 */
	private function getOrderByClause(): string {
		$orderings = $this->criteria->getOrderings();

		if ( empty( $orderings ) ) {
			return '';
		}

		$strings = [];
		foreach ( $orderings as $field => $order ) {
			$strings [] = "{$field} {$order}";
		}

		return 'ORDER BY ' . implode( ', ', $strings );
	}

	/**
	 * Get the LIMIT clause.
	 *
	 * Can be an empty string.
	 *
	 * @return string LIMIT clause.
	 */
	protected function getLimitClause(): string {
		$offset = $this->criteria->getFirstResult();
		$limit  = $this->criteria->getMaxResults();

		if ( $limit === PHP_INT_MAX || $limit === null ) {
			return '';
		}

		if ( $offset === 0 || $offset === null ) {
			return "LIMIT {$limit}";
		}

		return "LIMIT {$offset}, {$limit}";
	}
}
