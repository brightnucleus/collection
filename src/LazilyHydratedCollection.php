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

use BrightNucleus\Exception\RuntimeException;
use Closure;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class LazilyHydratedCollection
 *
 * @package BrightNucleus\Collection
 * @author  Alain Schlesser <alain.schlesser@gmail.com>
 */
abstract class LazilyHydratedCollection implements Collection {

	/**
	 * The backed collection to use.
	 *
	 * @var ArrayCollection
	 */
	protected $collection;

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
		$element = $this->collection->first();
		if ( false === $element ) {
			throw new RuntimeException( 'Cannot retrieve the first element of an empty collection.' );
		}
		return $element;
	}

	/**
	 * {@inheritDoc}
	 */
	public function last() {
		$this->hydrate();
		$element = $this->collection->last();
		if ( false === $element ) {
			throw new RuntimeException( 'Cannot retrieve the last element of an empty collection.' );
		}
		return $element;
	}

	/**
	 * {@inheritDoc}
	 */
	public function key() {
		$this->hydrate();
		$element = $this->collection->key();
		if ( false === $element ) {
			throw new RuntimeException( 'Cannot retrieve the current key of an empty collection.' );
		}
		return $element;
	}

	/**
	 * {@inheritDoc}
	 */
	public function current() {
		$this->hydrate();
		$element = $this->collection->current();
		if ( false === $element ) {
			throw new RuntimeException( 'Cannot retrieve the current element of an empty collection.' );
		}
		return $element;
	}

	/**
	 * {@inheritDoc}
	 */
	public function next() {
		$this->hydrate();
		$element = $this->collection->next();
		if ( false === $element ) {
			throw new RuntimeException( 'Cannot retrieve the next element of an empty collection.' );
		}
		return $element;
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
	 * Do the actual hydration logic.
	 *
	 * @return void
	 */
	abstract protected function doHydrate(): void;
}
