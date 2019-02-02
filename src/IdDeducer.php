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

use BrightNucleus\Exception\RuntimeException;

final class IdDeducer {

	/**
	 * Deduce the ID of a given element.
	 *
	 * Recognized types of elements are:
	 * - Entities
	 * - WP_Post objects
	 * - Objects with one of the following methods:
	 *     - getId()
	 *     - get_id()
	 *     - ID()
	 *     - id()
	 *     - Id()
	 * - Arrays with one of the following properties:
	 *     - ID
	 *     - id
	 *     - Id
	 * - Integer values
	 * - Strings that are UUIDS
	 * - Strings that are integer values
	 *
	 * @param object|array|int|string $element Element to deduce the ID from.
	 * @return mixed Deduced ID.
	 */
	public function deduceId( $element ) {
		static $methods = [
			'getId',
			'get_id',
			'ID',
			'id',
			'Id',
		];

		static $properties = [
			'ID',
			'id',
			'Id',
		];

		switch ( gettype( $element ) ) {
			case 'integer':
				return $element;
			case 'string':
				if ( ctype_digit( $element ) ) {
					return (int) $element;
				}
				if ( preg_match(
					     '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
					     $element
				     ) === 1 ) {
					return $element;
				}
				break;
			case 'object':
				if ( $element instanceof Entity ) {
					return $element->getId();
				}

				if ( $element instanceof WP_Post ) {
					return $element->ID;
				}

				foreach ( $methods as $method ) {
					if ( method_exists( $element, $method ) ) {
						return $element->$method();
					}
				}

				foreach ( $properties as $property ) {
					if ( property_exists( $element, $property ) ) {
						return $element->$property;
					}
				}
				break;
			case 'array':
				foreach ( $properties as $property ) {
					if ( array_key_exists( $property, $element ) ) {
						return $element[ $property ];
					}
				}
				break;
		}

		throw new RuntimeException( sprintf(
			'Could not deduce ID for element of type "%s".',
			is_object( $element )
				? get_class( $element )
				: gettype( $element )
		) );
	}
}
