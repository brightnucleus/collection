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

use Doctrine\Common\Collections\Expr\ExpressionVisitor;

final class PostMetaQueryGenerator extends WPQueryGenerator
{

    /**
     * Get the expression visitor instance to use.
     *
     * @return ExpressionVisitor
     */
    protected function getVisitor(): ExpressionVisitor
    {
        return new WPMetaQuerySQLExpressionVisitor($this->getTableName());
    }

    /**
     * Get the name of the table to query against.
     *
     * @return string Name of the table to query against.
     */
    protected function getTableName(): string
    {
        return 'postmeta';
    }
}
