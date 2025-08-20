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

use Doctrine\Common\Collections\Criteria as DoctrineCriteria;

interface Scope
{

    /**
     * Add one or more values to the Scope.
     *
     * @param  mixed ...$scope Value(s) to add.
     * @return Scope Modified Scope.
     */
    public function add( ...$scope ): Scope;

    /**
     * Merge the Scope with another Scope and return the merged result.
     *
     * @param  Scope $scope Scope to merge with.
     * @return Scope Merged Scope.
     */
    public function mergeWith( Scope $scope ): Scope;

    /**
     * Get the Criteria for the current Scope.
     *
     * @return DoctrineCriteria Criteria representing the Scope.
     */
    public function getCriteria(): DoctrineCriteria;
}
