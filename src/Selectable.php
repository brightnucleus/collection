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

use Doctrine\Common\Collections\Selectable as DoctrineSelectable;
use Doctrine\Common\Collections\Criteria as DoctrineCriteria;

interface Selectable extends DoctrineSelectable {

	/**
	 * Get the current criteria of the selectable.
	 *
	 * @return DoctrineCriteria Current criteria of the selectable.
	 */
	public function getCriteria(): DoctrineCriteria;
}
