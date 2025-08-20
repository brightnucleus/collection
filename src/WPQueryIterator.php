<?php
/**
 * Bright Nucleus Collection WP_Query Iterator
 *
 * @package   BrightNucleus\Collection
 * @author    Alain Schlesser <alain.schlesser@gmail.com>
 * @license   MIT+
 * @link      http://www.brightnucleus.com/
 * @copyright 2018 Alain Schlesser, Bright Nucleus
 */

namespace BrightNucleus\Collection;

use Iterator;

/**
 * Class WPQueryIterator
 *
 * @package BrightNucleus\Collection
 * @author  Alain Schlesser <alain.schlesser@gmail.com>
 */
class WPQueryIterator implements Iterator
{

    /**
     * @var WPQueryCollection 
     */
    protected $collection;

    public function __construct( WPQueryCollection $collection )
    {
        $this->collection = $collection;
    }

    /**
     * Return the current element
     *
     * @link   http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->collection->current();
    }

    /**
     * Move forward to next element
     *
     * @link   http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next(): void
    {
        $this->collection->next();
    }

    /**
     * Return the key of the current element
     *
     * @link   http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->collection->key();
    }

    /**
     * Checks if current position is valid
     *
     * @link   http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then
     *     evaluated. Returns true on success or false on failure.
     */
    public function valid(): bool
    {
        // Check if the current element exists and is not false
        try {
            $current = $this->collection->current();
            return $current !== false && $current !== null;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Rewind the Iterator to the first element
     *
     * @link   http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind(): void
    {
        $this->collection->first();
    }

}
