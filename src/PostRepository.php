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

/**
 * Class PostCollection
 *
 * @package BrightNucleus\Collection
 * @author  Alain Schlesser <alain.schlesser@gmail.com>
 */
final class PostRepository extends PostTypeRepository
{

    /**
     * Find all elements the repository contains.
     *
     * @return PostCollection
     */
    public function findAll(): PostCollection
    {
        // Override only used for setting the return type.
        return parent::findAll();
    }

    /**
     * Find a collection of elements that fit a given set of criteria.
     *
     * @param  Criteria $criteria Criteria to qualify the desired result set.
     * @return PostCollection
     */
    public function findBy( Criteria $criteria ): PostCollection
    {
        return parent::findBy($criteria);
    }

    /**
     * Get the class that represents the collection the repository returns.
     *
     * @return string
     */
    protected static function getCollectionClass(): string
    {
        return PostCollection::class;
    }

    /**
     * Get the property-to-post-field mappings for a given entity.
     *
     * @param  Entity $entity Entity to get the mappings for.
     * @return array Array of property-to-post-field mappings.
     */
    protected function get_properties_to_post_mappings( Entity $entity ): array
    {
        // Return empty array as PostRepository doesn't need custom mappings
        return [];
    }

    /**
     * Get the property-to-postmeta mappings for a given entity.
     *
     * @param  Entity $entity Entity to get the mappings for.
     * @return array Array of property-to-postmeta mappings.
     */
    protected function get_properties_to_postmeta_mappings( Entity $entity ): array
    {
        // Return empty array as PostRepository doesn't need custom mappings
        return [];
    }

    /**
     * Get the property-to-taxonomy mappings for a given entity.
     *
     * @param  Entity $entity Entity to get the mappings for.
     * @return array Array of property-to-taxonomy mappings.
     */
    protected function get_properties_to_taxonomies_mappings( Entity $entity ): array
    {
        // Return empty array as PostRepository doesn't need custom mappings
        return [];
    }
}
