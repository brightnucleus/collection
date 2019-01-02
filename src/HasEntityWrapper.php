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

interface HasEntityWrapper {

	/**
	 * Get the class to be used to wrap entities.
	 *
	 * The wrapper class will need to accept a WP_Post object in its
	 * constructor.
	 *
	 * @return string
	 */
	public function getEntityWrapperClass(): string;
}
