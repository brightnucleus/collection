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

final class PostMetaPropertyCache implements PropertyCache
{

    /**
     * @var array 
     */
    private $ids;

    /**
     * @var PostMetaPropertyCollection[] 
     */
    private $properties = [];

    /**
     * @var bool 
     */
    private $isHydrated = false;

    /**
     * Instantiate a PostMetaPropertyCache object.
     *
     * @param array $ids IDs to prime the cache with.
     */
    public function __construct( array $ids )
    {
        $this->ids = array_combine($ids, $ids);
    }

    /**
     * Remember a provided set of properties.
     *
     * @param  int|string                                              $id ID for which to remember the property.
     * @param  PropertyCollection Collection of properties to remember.
     * @return PropertyCache Modified instance of the cache.
     */
    public function remember( $id, PropertyCollection $properties ): PropertyCache
    {
        $this->properties[ $id ] = $properties;

        return $this;
    }

    /**
     * Invalidate remembered properties for a specific ID.
     *
     * @param  int|string $id ID for which to invalidate the properties.
     * @return PropertyCache Modified instance of the cache.
     */
    public function invalidate( $id ): PropertyCache
    {
        unset($this->properties[ $id ]);

        return $this;
    }

    /**
     * Flush the entire cache.
     *
     * @return PropertyCache Modified instance of the cache.
     */
    public function flush(): PropertyCache
    {
        unset($this->properties);
        $this->isHydrated = false;

        return $this;
    }

    /**
     * Get the properties for a given ID.
     *
     * @param  int|string $id ID to get the properties for.
     * @return PropertyCollection Collection of properties.
     */
    public function get( $id ): PropertyCollection
    {
        if (array_key_exists($id, $this->ids) ) {
            if (! $this->isHydrated ) {
                $this->hydrate($this->ids);
                $this->isHydrated = true;
            }
        }

        if (! array_key_exists($id, $this->properties) ) {
            $this->hydrate([ $id ]);
        }

        if (array_key_exists($id, $this->properties)
            && null !== $this->properties[ $id ] 
        ) {
            return $this->properties[ $id ];
        };

        return new PostMetaPropertyCollection($id);
    }

    /**
     * Hydrate one or more entires of the properties cache.
     *
     * @param array $ids Array of IDs to hydrate.
     */
    private function hydrate( array $ids ): void
    {
        global $wpdb;
        $expr     = Criteria::expr();
        $criteria = Criteria::create()
            ->where($expr->in(new Column(Table::POSTMETA, 'post_id'), $ids));

        $query = ( new PostMetaQueryGenerator($criteria) )->getQuery();

        $results = $wpdb->get_results($query);

        $properties = [];
        array_walk(
            $results, function ( $element ) use ( &$properties ) {
                $id = (int) $element->post_id;
                if (! array_key_exists($id, $properties) ) {
                    $properties[ $id ] = [];
                }

                $properties[ $id ][] = [ $element->meta_key => $element->meta_value ];
            } 
        );

        foreach ( $properties as $id => $meta ) {
            $this->remember(
                $id,
                new PostMetaPropertyCollection($id, array_filter($meta))
            );
        }
    }
}
