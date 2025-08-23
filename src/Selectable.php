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

/**
 * Interface for collections that allow efficient filtering with criteria.
 * 
 * This interface is independent of Doctrine to provide isolation from
 * third-party API changes.
 */
interface Selectable
{
    /**
     * Selects all elements from a selectable that match the criteria and
     * returns a new collection containing these elements.
     *
     * @param Criteria $criteria
     *
     * @return Collection&Selectable
     */
    public function matching(Criteria $criteria);

    /**
     * Get the current criteria of the selectable.
     *
     * @return Criteria Current criteria of the selectable.
     */
    public function getCriteria(): Criteria;
}
