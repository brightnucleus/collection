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

trait ID {

	/** @var int|string */
	protected $id;

	/**
	 * Get the ID of the entity.
	 *
	 * @return int|string ID.
	 */
	public function getId() {
		return $this->id;
	}
}
