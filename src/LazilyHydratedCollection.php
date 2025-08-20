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
abstract class LazilyHydratedCollection implements Collection
{

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
    public function count(): int
    {
        // TODO: Make smarter to not automatically hydrate if not needed.
        $this->hydrate();
        return $this->collection->count();
    }

    /**
     * {@inheritDoc}
     */
    public function add( $element ): bool
    {
        $this->hydrate();
        $result = $this->collection->add($element);
        // Doctrine's ArrayCollection::add() returns true or void/null
        return $result !== false;
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): void
    {
        $this->collection = new ArrayCollection();
        $this->isHydrated = true;
    }

    /**
     * {@inheritDoc}
     */
    public function contains( $element ): bool
    {
        $this->hydrate();
        return $this->collection->contains($element);
    }

    /**
     * {@inheritDoc}
     */
    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function remove( $key )
    {
        $this->hydrate();
        return $this->collection->remove($key);
    }

    /**
     * {@inheritDoc}
     */
    public function removeElement( $element ): bool
    {
        $this->hydrate();
        return $this->collection->removeElement($element);
    }

    /**
     * {@inheritDoc}
     */
    public function containsKey( $key ): bool
    {
        $this->hydrate();
        return $this->collection->containsKey($key);
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function get( $key )
    {
        $this->hydrate();
        return $this->collection->get($key);
    }

    /**
     * {@inheritDoc}
     */
    public function getKeys(): array
    {
        $this->hydrate();
        return $this->collection->getKeys();
    }

    /**
     * {@inheritDoc}
     */
    public function getValues(): array
    {
        $this->hydrate();
        return $this->collection->getValues();
    }

    /**
     * {@inheritDoc}
     */
    public function set( $key, $value ): void
    {
        $this->hydrate();
        $this->collection->set($key, $value);
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        $this->hydrate();
        return $this->collection->toArray();
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function first()
    {
        $this->hydrate();
        return $this->collection->first();
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function last()
    {
        $this->hydrate();
        return $this->collection->last();
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        $this->hydrate();
        return $this->collection->key();
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        $this->hydrate();
        return $this->collection->current();
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function next()
    {
        $this->hydrate();
        return $this->collection->next();
    }

    /**
     * {@inheritDoc}
     */
    public function exists( Closure $p ): bool
    {
        $this->hydrate();
        return $this->collection->exists($p);
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function filter( Closure $p )
    {
        $this->hydrate();
        return $this->collection->filter($p);
    }

    /**
     * {@inheritDoc}
     */
    public function forAll( Closure $p ): bool
    {
        $this->hydrate();
        return $this->collection->forAll($p);
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function map( Closure $func )
    {
        $this->hydrate();
        return $this->collection->map($func);
    }

    /**
     * {@inheritDoc}
     */
    public function partition( Closure $p ): array
    {
        $this->hydrate();
        return $this->collection->partition($p);
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function indexOf( $element )
    {
        $this->hydrate();
        return $this->collection->indexOf($element);
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function slice( $offset, $length = null )
    {
        $this->hydrate();
        return $this->collection->slice($offset, $length);
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator(): \Traversable
    {
        $this->hydrate();
        return $this->collection->getIterator();
    }

    /**
     * {@inheritDoc}
     */
    public function offsetExists( $offset ): bool
    {
        $this->hydrate();
        return $this->collection->offsetExists($offset);
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function offsetGet( $offset )
    {
        $this->hydrate();
        return $this->collection->offsetGet($offset);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet( $offset, $value ): void
    {
        $this->hydrate();
        $this->collection->offsetSet($offset, $value);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetUnset( $offset ): void
    {
        $this->hydrate();
        $this->collection->offsetUnset($offset);
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function findFirst( Closure $p )
    {
        $this->hydrate();
        if (method_exists($this->collection, 'findFirst') ) {
            return $this->collection->findFirst($p);
        }
        // Fallback for older Doctrine versions
        foreach ( $this->collection as $key => $element ) {
            if ($p($key, $element) ) {
                return $element;
            }
        }
        return null;
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function reduce( Closure $func, $initial = null )
    {
        $this->hydrate();
        if (method_exists($this->collection, 'reduce') ) {
            return $this->collection->reduce($func, $initial);
        }
        // Fallback for older Doctrine versions
        $accumulator = $initial;
        foreach ( $this->collection as $element ) {
            $accumulator = $func($accumulator, $element);
        }
        return $accumulator;
    }

    /**
     * Is the query already hydrated?
     *
     * @return bool
     */
    public function isHydrated(): bool
    {
        return $this->isHydrated;
    }

    /**
     * Hydrate the collection.
     *
     * @return void
     */
    protected function hydrate(): void
    {
        if (! $this->isHydrated ) {
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
