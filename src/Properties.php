<?php
/**
 * Bright Nucleus Collection Post Collection
 *
 * @package   BrightNucleus\Collection
 * @author    Alain Schlesser <alain.schlesser@gmail.com>
 * @license   MIT+
 * @link      http://www.brightnucleus.com/
 * @copyright 2018 Alain Schlesser, Bright Nucleus
 */

namespace BrightNucleus\Collection;

trait Properties
{

    /**
     * @var PropertyCollection 
     */
    protected $properties;

    /**
     * Get the collection of properties for the given Entity.
     *
     * @return PropertyCollection
     */
    public function getProperties(): PropertyCollection
    {
        return $this->properties;
    }
}
