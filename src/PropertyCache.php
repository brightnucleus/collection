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

interface PropertyCache {

	/**
	 * Remember a provided set of properties.
	 *
	 * @param int|string $id ID for which to remember the property.
	 * @param PropertyCollection Collection of properties to remember.
	 * @return PropertyCache Modified instance of the cache.
	 */
	public function remember( $id, PropertyCollection $properties ): PropertyCache;

	/**
	 * Invalidate remembered properties for a specific ID.
	 *
	 * @param int|string $id ID for which to invalidate the properties.
	 * @return PropertyCache Modified instance of the cache.
	 */
	public function invalidate( $id ): PropertyCache;

	/**
	 * Flush the entire cache.
	 *
	 * @return PropertyCache Modified instance of the cache.
	 */
	public function flush(): PropertyCache;

	/**
	 * Get the properties for a given ID.
	 *
	 * @param int|string $id ID to get the properties for.
	 * @return PropertyCollection Collection of properties.
	 */
	public function get( $id ): PropertyCollection;
}
