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

final class Column
{

    /**
     * @var string 
     */
    private $tableName;

    /**
     * @var string 
     */
    private $columnName;

    /**
     * Instantiate a Column object.
     *
     * @param string $tableName  Table that contains the column.
     * @param string $columnName Name of the column.
     */
    public function __construct( string $tableName, string $columnName )
    {
        $this->tableName  = $tableName;
        $this->columnName = $columnName;
    }

    /**
     * Get the name of the table.
     *
     * @return string Name of the table.
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * Get name of the column.
     *
     * @return string Name of the column.
     */
    public function getColumnName()
    {
        return $this->columnName;
    }

    /**
     * Return the string representation of the column.
     *
     * @return string String representation of the column.
     */
    public function __toString()
    {
        return "{$this->tableName}.{$this->columnName}";
    }
}
