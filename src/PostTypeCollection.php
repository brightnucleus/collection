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
abstract class PostTypeCollection
	extends AbstractWPQueryCollection
	implements PropertyCacheAware {

	use PropertyCaching;

	/**
	 * Assert that the element corresponds to the correct type for the
	 * collection.
	 *
	 * @param mixed $element Element to assert the type of.
	 *
	 * @throws InvalidArgumentException If the type didn't match.
	 */
	public static function assertType( $element ): void {
		if ( $element instanceof WP_Post && $element->post_type === static::getPostType() ) {
			return;
		}

		if ( is_subclass_of( static::class, HasEntityWrapper::class ) ) {
			$wrapper = static::getEntityWrapperClass();
			if ( $element instanceof $wrapper ) {
				return;
			}
		}

		$message = sprintf(
			"Invalid type of element, WP_Post object of type '%s'%s required, got %s.",
			static::getPostType(),
			is_subclass_of( static::class, HasEntityWrapper::class ) ? " or {$wrapper} object" : '',
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
