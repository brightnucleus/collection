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

/**
 * Class PostCollection
 *
 * @package BrightNucleus\Collection
 * @author  Alain Schlesser <alain.schlesser@gmail.com>
 */
final class PostCollection extends PostTypeCollection {

	const POST_TYPE = 'post';

	/**
	 * Get the slug of the post type.
	 *
	 * @return string
	 */
	public static function getPostType(): string {
		return self::POST_TYPE;
	}

	/**
	 * Selects all elements from a selectable that match the expression and
	 * returns a new collection containing these elements.
	 *
	 * @param Criteria $criteria
	 *
	 * @return PostCollection
	 */
	public function matching( Criteria $criteria ): PostCollection {
		// Override only used for setting the return type.
		return parent::matching( $criteria );
	}

	/**
	 * @return WP_Post[]
	 */
	public function getIterator() {
		// Override only used for setting the return type.
		return parent::getIterator();
	}
}
