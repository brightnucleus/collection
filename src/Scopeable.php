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

interface Scopeable {

	/**
	 * Get the current criteria of the Scopeable.
	 *
	 * @return Scope Current scope of the Scopeable.
	 */
	public function getScope(): Scope;

	/**
	 * Use a specific scope for the current Scopeable.
	 *
	 * If the Scopeable already has a Scope of the same type, it will be
	 * replaced.
	 *
	 * @param Scope $scope Scope to use.
	 * @return Collection
	 */
	public function withScope( Scope $scope );

	/**
	 * Add a specific scope for the current Scopeable.
	 *
	 * If the Scopeable already has a Scope of the same type, it will be
	 * extended instead of replaced.
	 *
	 * @param Scope $scope Scope to add.
	 * @return Collection
	 */
	public function addScope( Scope $scope );
}
