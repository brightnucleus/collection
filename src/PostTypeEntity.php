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

use WP_Post;

interface PostTypeEntity extends Entity {

	/**
	 * Get the internal post object that the listing is based on.
	 *
	 * @return WP_Post Internal post object.
	 */
	public function get_post_object(): WP_Post;
}
