<?php declare( strict_types=1 );

namespace BrightNucleus\Collection\Tests\Integration;

use BrightNucleus\Collection\Criteria;
use BrightNucleus\Collection\NullCriteria;
use BrightNucleus\Collection\PostMetaPropertyCollection;
use BrightNucleus\Collection\Property;
use WP_UnitTest_Factory;
use WP_UnitTestCase;

final class PostMetaPropertyCollectionTest extends WP_UnitTestCase
{

    public function test_it_can_be_instantiated()
    {
        $posts = new PostMetaPropertyCollection(0);

        $this->assertInstanceOf(PostMetaPropertyCollection::class, $posts);
    }

    public function test_it_accepts_associative_arrays()
    {
        $property_a = [ 'some_key' => 'some_value' ];
        $property_b = [ 'another_key' => 'another_value' ];
        $properties = new PostMetaPropertyCollection(
            0,
            [ $property_a, $property_b ]
        );

        $this->assertCount(2, $properties);
        $result = [];
        foreach ( $properties as $key => $value ) {
            $result[ $key ] = $value;
        }
        $this->assertArrayHasKey('some_key', $result);
        $this->assertArrayHasKey('another_key', $result);
        $this->assertEquals('some_value', $result['some_key']);
        $this->assertEquals('another_value', $result['another_key']);
    }

    public function test_it_can_match_on_criteria()
    {
        $factory = new WP_UnitTest_Factory();
        $post_id = $factory->post->create();
        add_post_meta($post_id, 'key_1', 'value_1');
        add_post_meta($post_id, 'key_2', 'value_2');
        add_post_meta($post_id, 'key_3', 'value_3');
        add_post_meta($post_id, 'key_4', 'value_4');

        $properties = new PostMetaPropertyCollection(
            $post_id,
            new NullCriteria()
        );

        $expr     = Criteria::expr();
        $criteria = Criteria::create()
            ->where($expr->eq('key', 'key_2'))
            ->orWhere($expr->eq('key', 'key_3'));

        $matched_properties = $properties->matching($criteria);

        $this->assertCount(2, $matched_properties);
        $this->assertEquals(
            get_post_meta($post_id, 'key_2', true),
            $matched_properties['key_2']->getValue()
        );
        $this->assertEquals(
            get_post_meta($post_id, 'key_3', true),
            $matched_properties['key_3']->getValue()
        );
    }

    public function test_it_can_match_on_hydrated_collection()
    {
        $property_a = new Property('key_1', 'value_1');
        $property_b = new Property('key_2', 'value_2');
        $property_c = new Property('key_3', 'value_3');
        $property_d = new Property('key_4', 'value_4');

        $properties = new PostMetaPropertyCollection(
            0,
            [
            $property_a,
            $property_b,
            $property_c,
            $property_d,
            ]
        );

        $expr     = Criteria::expr();
        $criteria = Criteria::create()
            ->where($expr->eq('key', $property_b->getKey()))
            ->orWhere(
                $expr->eq(
                    'key',
                    $property_c->getKey() 
                ) 
            );

        $matched_properties = $properties->matching($criteria);

        $this->assertCount(2, $matched_properties);
        $this->assertEquals(
            $property_b->getValue(),
            $matched_properties['key_2']->getValue()
        );
        $this->assertEquals(
            $property_c->getValue(),
            $matched_properties['key_3']->getValue()
        );
    }

    public function test_values_can_be_directly_retrieved()
    {
        $property_a = [ 'some_key' => 'some_value' ];
        $property_b = [ 'another_key' => 'another_value' ];
        $properties = new PostMetaPropertyCollection(
            0,
            [ $property_a, $property_b ]
        );

        $this->assertCount(2, $properties);
        $this->assertEquals(
            'some_value',
            $properties->get('some_key')
        );

        $this->assertEquals(
            'another_value',
            $properties->get('another_key')
        );


    }

    public function test_property_objects_can_be_directly_retrieved()
    {
        $property_a = [ 'some_key' => 'some_value' ];
        $property_b = [ 'another_key' => 'another_value' ];
        $properties = new PostMetaPropertyCollection(
            0,
            [ $property_a, $property_b ]
        );

        $this->assertCount(2, $properties);
        $this->assertInstanceOf(
            Property::class,
            $properties->getProperty('some_key')
        );
        $this->assertEquals(
            'some_value',
            $properties->getProperty('some_key')->getValue()
        );
        $this->assertInstanceOf(
            Property::class,
            $properties->getProperty('another_key')
        );
        $this->assertEquals(
            'another_value',
            $properties->getProperty('another_key')->getValue()
        );
    }
}
