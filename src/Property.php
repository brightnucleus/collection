<?php
/**
 * Bright Nucleus WP_Query Collection
 *
 * @package   BrightNucleus\Collection
 * @author    Alain Schlesser <alain.schlesser@gmail.com>
 * @license   MIT+
 * @link      http://www.brightnucleus.com/
 * @copyright 2018 Alain Schlesser, Bright Nucleus
 */

namespace BrightNucleus\Collection;

use BrightNucleus\Exception\InvalidArgumentException;
use stdClass;

/**
 * Interface Property
 *
 * @package BrightNucleus\Collection
 * @author  Alain Schlesser <alain.schlesser@gmail.com>
 */
class Property {

	/** @var string */
	protected $key;

	/** @var mixed */
	protected $value;

	/**
	 * Instantiate a Property object.
	 *
	 * @param mixed $_ Arguments to use for initialization. Can be either a
	 *                 single-element associative array or a pair of scalar
	 *                 values where the first is the key and the second is the
	 *                 value.
	 */
	public function __construct( $_ ) {
		list ( $this->key, $this->value ) = $this->parseArguments( func_get_args() );
	}

	/**
	 * Get the key of the property.
	 *
	 * @return string Key of the property.
	 */
	public function getKey(): string {
		return $this->key;
	}

	/**
	 * Get the value of the property.
	 *
	 * @return mixed Value of the property.
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * Parses the arguments and returns an array containing the key and the
	 * value to use.
	 *
	 * @param array Array of arguments that were passed to the constructor.
	 *
	 * @return array Array containing the key and the value to use.
	 */
	protected function parseArguments( $arguments ): array {
		switch ( count( $arguments ) ) {
			case 1:
				// Single-element associative array:
				// $property = new Property( [ 'some_key' => 'some_value' ] );
				$argument = $arguments[0];

				if ( $argument instanceof stdClass
				     && isset( $argument->meta_key, $argument->meta_value ) ) {
					$argument = [ $argument->meta_key => $argument->meta_value ];
				}

				if ( ! is_array( $argument ) || 1 !== count( $argument ) ) {
					throw new InvalidArgumentException(
						'Invalid argument provided to Property constructor, needs to be a single-element array or a Property instance.'
					);
				}

				reset( $argument );

				return [
					key( $argument ),
					current( $argument ),
				];
			case 2:
				// Pair of arguments:
				// $property = new Property( 'some_key', 'some_value' );
				if ( ! is_string( $arguments[0] ) ) {
					$type = is_object( $arguments[0] )
						? get_class( $arguments[0] )
						: gettype( $arguments[0] );

					throw new InvalidArgumentException(
						"Invalid key provided to Property constructor, needs to be of type 'string', got '{$type}'."
					);
				}

				return $arguments;
			default:
				throw new InvalidArgumentException(
					'Invalid argument provided to Property constructor, needs to be a single-element array or a Property instance.'
				);
		}
	}

	/**
	 * Get the array representation of the property.
	 *
	 * @return array Array representation of the property.
	 */
	public function toArray() {
		return [ $this->key => $this->value ];
	}
}
