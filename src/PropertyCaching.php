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

trait PropertyCaching
{

    /**
     * @var PropertyCache 
     */
    protected $propertyCache;

    /**
     * Get the property cache instance in use.
     *
     * @return ?PropertyCache Property cache instance.
     */
    public function getPropertyCache(): ?PropertyCache
    {
        return $this->propertyCache;
    }

    /**
     * Configure the object to use a specific property cache instance.
     *
     * @param PropertyCache $propertyCache Property cache instance to use.
     */
    public function withPropertyCache( PropertyCache $propertyCache ): void
    {
        $this->propertyCache = $propertyCache;
    }
}
