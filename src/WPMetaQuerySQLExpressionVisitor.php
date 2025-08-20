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
class WPMetaQuerySQLExpressionVisitor extends WPQuerySQLExpressionVisitor
{

    /**
     * Map the convenient shorthand properties to the actual table columns.
     */
    const FIELD_MAP = [
    'id'    => 'meta_id',
    'key'   => 'meta_key',
    'value' => 'meta_value',
    ];

    /**
     * Instantiate a WPQuerySQLExpressionVisitor object.
     *
     * @param string $tableName Name of the table to generate SQL for.
     */
    public function __construct( string $tableName )
    {
        $this->tableName = $tableName;
    }

    /**
     * Builds the left-hand-side of a where condition statement.
     *
     * @param string     $field
     * @param array|null $assoc
     *
     * @return string[]
     */
    protected function getSelectConditionStatementColumnSQL( $field, $assoc = null )
    {
        global $wpdb;

        if ($field instanceof Column ) {
            return [ "{$field->getTableName()}.{$field->getColumnName()}" ];
        }

        if (is_string($field) && array_key_exists($field, static::FIELD_MAP) ) {
            $field = static::FIELD_MAP[ $field ];
        }

        return [ "{$this->tableName}.{$field}" ];
    }
}
