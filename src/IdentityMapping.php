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

trait IdentityMapping
{

    /**
     * @var IdentityMap 
     */
    protected $identityMap;

    /**
     * Get the identity map that is being used.
     *
     * @return IdentityMap
     */
    public function getIdentityMap(): IdentityMap
    {
        return $this->identityMap;
    }

    /**
     * Return a deduplicated entity for a given ID.
     *
     * If the entity is already known to the identity map, it is retrieved from
     * there. If not, it is being being retrieved through the initializer
     * callable and stored in the identity map before being returned.
     *
     * @param  mixed    $id          ID of the entity to deduplicate.
     * @param  callable $initializer Initializer callable to retrieve the entity
     *                               if it is not yet known.
     * @return Entity Deduplicated entity reference.
     */
    public function deduplicated( $id, callable $initializer )
    {
        if ($this->identityMap->has($id) ) {
            return $this->identityMap->get($id);
        }

        $entity = $initializer($id);
        $this->identityMap->put($id, $entity);

        return $entity;
    }
}
