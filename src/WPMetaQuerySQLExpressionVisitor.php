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
            // For Column objects, check if the table matches our context
            if ($field->getTableName() === $this->tableName) {
                // Same table, use our table alias with the column name
                return [ "{$this->tableName}.{$field->getColumnName()}" ];
            }
            // Different table (for joins), use the full reference
            return [ "{$field->getTableName()}.{$field->getColumnName()}" ];
        }

        if (is_string($field)) {
            // Check if the field already contains a table prefix (has a dot)
            if (strpos($field, '.') !== false) {
                // Split into table and column parts
                $parts = explode('.', $field, 2);
                $fieldTable = $parts[0];
                $fieldColumn = $parts[1];
                
                // If it's for our table, ensure proper aliasing
                if ($fieldTable === $this->tableName) {
                    return [ "{$this->tableName}.{$fieldColumn}" ];
                }
                
                // Different table reference (for joins), return as-is
                return [ $field ];
            }
            
            // Check field mapping for convenience shortcuts
            if (array_key_exists($field, static::FIELD_MAP)) {
                $field = static::FIELD_MAP[$field];
            }
        }

        // Plain field name, add our table prefix
        return [ "{$this->tableName}.{$field}" ];
    }
}
