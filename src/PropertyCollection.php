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

/**
 * Interface PropertyCollection
 *
 * @package BrightNucleus\Collection
 * @author  Alain Schlesser <alain.schlesser@gmail.com>
 */
interface PropertyCollection extends Collection
{

    /**
     * Get the property object stored under a given key.
     *
     * @param string $key Key to retrieve the property for.
     *
     * @return Property Property stored under the requested key.
     */
    public function getProperty( string $key ): Property;

    /**
     * Selects all elements from a selectable that match the expression and
     * returns a new collection containing these elements.
     *
     * @param Criteria $criteria
     *
     * @return PropertyCollection
     */
    public function matching( Criteria $criteria );
}
