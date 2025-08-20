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
 * Class PostMetaPropertyCollection
 *
 * @package BrightNucleus\Collection
 * @author  Alain Schlesser <alain.schlesser@gmail.com>
 */
class PostMetaPropertyCollection extends AbstractPropertyCollection
{

    /**
     * Get the query generator to use.
     *
     * @return QueryGenerator
     */
    protected function getQueryGenerator(): QueryGenerator
    {
        return new PostMetaQueryGenerator($this->criteria);
    }
}
