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
use BrightNucleus\Exception\RuntimeException;
use WP_Post;

/**
 * Class PostTypeCollection
 *
 * @package BrightNucleus\Collection
 * @author  Alain Schlesser <alain.schlesser@gmail.com>
 */
abstract class PostTypeCollection
	extends AbstractWPQueryCollection
	implements PropertyCacheAware, Scopeable {

	use PropertyCaching;

	/** @var Scope */
	protected $scope;

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
	 * Get the current criteria of the Scopeable.
	 *
	 * @return Scope Current scope of the Scopeable.
	 */
	public function getScope(): Scope {
		return $this->scope;
	}

	/**
	 * Use a specific scope for the current Scopeable.
	 *
	 * If the Scopeable already has a Scope of the same type, it will be
	 * replaced.
	 *
	 * @param Scope $scope Scope to use.
	 * @return PostTypeCollection
	 */
	public function withScope( Scope $scope ) {
		if ( ! $scope instanceof Status ) {
			throw new RuntimeException(
				sprintf(
					'Invalid Scope type "%1$s" for Collection of type "%2$s"',
					get_class( $scope ),
					get_class( $this )
				)
			);
		}

		$newCollection = clone $this;

		$newCollection->scope = $scope;

		return $newCollection;
	}

	/**
	 * Add a specific scope for the current Scopeable.
	 *
	 * If the Scopeable already has a Scope of the same type, it will be
	 * extended instead of replaced.
	 *
	 * @param Scope $scope Scope to add.
	 * @return PostTypeCollection
	 */
	public function addScope( Scope $scope ) {
		if ( ! $scope instanceof Status ) {
			throw new RuntimeException(
				sprintf(
					'Invalid Scope type "%1$s" for Collection of type "%2$s"',
					get_class( $scope ),
					get_class( $this )
				)
			);
		}

		$newCollection = clone $this;

		$newCollection->scope = $newCollection->scope->mergeWith( $scope );

		return $newCollection;
	}

	/**
	 * Get the slug of the post type.
	 *
	 * @return string
	 */
	abstract public static function getPostType(): string;
}
