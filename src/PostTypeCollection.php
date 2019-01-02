<?php
/**
 * Bright Nucleus Collection abstract Post Type Collection
 *
 * @package   BrightNucleus\Collection
 * @author    Alain Schlesser <alain.schlesser@gmail.com>
 * @license   MIT+
 * @link      http://www.brightnucleus.com/
 * @copyright 2018 Alain Schlesser, Bright Nucleus
 */

namespace BrightNucleus\Collection;

use BrightNucleus\Exception\InvalidArgumentException;
use WP_Post;

/**
 * Class PostTypeCollection
 *
 * @package BrightNucleus\Collection
 * @author  Alain Schlesser <alain.schlesser@gmail.com>
 */
abstract class PostTypeCollection extends AbstractWPQueryCollection {

	/**
	 * Assert that the element corresponds to the correct type for the
	 * collection.
	 *
	 * @param mixed $element Element to assert the type of.
	 *
	 * @throws InvalidArgumentException If the type didn't match.
	 */
	protected function assertType( $element ): void {
		if ( $element instanceof WP_Post && $element->post_type === static::getPostType() ) {
			return;
		}

		if ( $this instanceof HasEntityWrapper ) {
			$wrapper = $this->getEntityWrapperClass();
			if ( $element instanceof $wrapper ) {
				return;
			}
		}

		$message = sprintf(
			"Invalid type of element, WP_Post object of type '%s'%s required, got %s.",
			static::getPostType(),
			$this instanceof HasEntityWrapper ? " or {$this->getEntityWrapperClass()} object" : '',
			$element instanceof WP_Post
				? "WP_Post object of type {$element->post_type}"
				: ( is_object( $element ) ? get_class( $element ) : gettype( $element ) )
		);

		throw new InvalidArgumentException( $message );
	}

	/**
	 * Get the query generator to use.
	 *
	 * @return QueryGenerator
	 */
	protected function getQueryGenerator(): QueryGenerator {
		return new PostTypeQueryGenerator( $this->criteria );
	}

	/**
	 * Get the slug of the post type.
	 *
	 * @return string
	 */
	abstract public static function getPostType(): string;
}
