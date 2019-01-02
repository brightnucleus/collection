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

/**
 * Class AbstractWPQueryCollection
 *
 * @package BrightNucleus\Collection
 * @author  Alain Schlesser <alain.schlesser@gmail.com>
 */
class WPMetaQuerySQLExpressionVisitor extends WPQuerySQLExpressionVisitor {

	/**
	 * Map the convenient shorthand properties to the actual table columns.
	 */
	const FIELD_MAP = [
		'id'    => 'meta_id',
		'key'   => 'meta_key',
		'value' => 'meta_value',
	];

	/**
	 * Builds the left-hand-side of a where condition statement.
	 *
	 * @param string     $field
	 * @param array|null $assoc
	 *
	 * @return string[]
	 */
	protected function getSelectConditionStatementColumnSQL( $field, $assoc = null ) {
		global $wpdb;

		if ( array_key_exists( $field, static::FIELD_MAP ) ) {
			$field = static::FIELD_MAP[ $field ];
		}

		return [ "{$wpdb->get_blog_prefix()}{$this->tableName}.{$field}" ];
	}
}
