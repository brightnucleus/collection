<?php declare( strict_types=1 );

use BrightNucleus\Collection\Criteria;
use BrightNucleus\Collection\NullCriteria;
use BrightNucleus\Collection\PostMetaPropertyCollection;
use BrightNucleus\Collection\Property;

final class PostMetaPropertyCollectionTest extends WP_UnitTestCase {

	public function test_it_can_be_instantiated() {
		$posts = new PostMetaPropertyCollection( 0 );

		$this->assertInstanceOf( PostMetaPropertyCollection::class, $posts );
	}

	public function test_it_accepts_associative_arrays() {
		$property_a = [ 'some_key' => 'some_value' ];
		$property_b = [ 'another_key' => 'another_value' ];
		$properties = new PostMetaPropertyCollection( 0, [ $property_a, $property_b ] );

		$this->assertCount( 2, $properties );
		foreach ( $properties as $property ) {
			$this->assertInstanceOf( Property::class, $property );
		}
		$this->assertEquals( [
			[ 'some_key' => 'some_value' ],
			[ 'another_key' => 'another_value' ],
		], $properties->toArray() );
	}

	public function test_it_accepts_another_collection() {
		$property_a = [ 'some_key' => 'some_value' ];
		$property_b = [ 'another_key' => 'another_value' ];
		$properties_a = new PostMetaPropertyCollection( 0, [ $property_a, $property_b ] );

		$this->assertCount( 2, $properties_a );
		foreach ( $properties_a as $property ) {
			$this->assertInstanceOf( Property::class, $property );
		}

		$properties_b = new PostMetaPropertyCollection( 0, $properties_a );

		$this->assertCount( 2, $properties_b );
		foreach ( $properties_b as $property ) {
			$this->assertInstanceOf( Property::class, $property );
		}
	}

	public function test_it_can_match_on_criteria() {
		$factory = new WP_UnitTest_Factory();
		$post_id = $factory->post->create();
		add_post_meta( $post_id, 'key_1', 'value_1' );
		add_post_meta( $post_id, 'key_2', 'value_2' );
		add_post_meta( $post_id, 'key_3', 'value_3' );
		add_post_meta( $post_id, 'key_4', 'value_4' );

		$properties = new PostMetaPropertyCollection( $post_id, new NullCriteria() );

		$expr     = Criteria::expr();
		$criteria = Criteria::create()
		                    ->where( $expr->eq( 'key', 'key_2' ) )
		                    ->orWhere( $expr->eq( 'key', 'key_3' ) );

		$matched_properties = $properties->matching( $criteria );

		$this->assertCount( 2, $matched_properties );
		$this->assertInstanceOf( Property::class, $matched_properties[0] );
		$this->assertEquals( get_post_meta( $post_id, 'key_2', true ), $matched_properties[0]->getValue() );
		$this->assertInstanceOf( Property::class, $matched_properties[1] );
		$this->assertEquals( get_post_meta( $post_id, 'key_3', true ), $matched_properties[1]->getValue() );
	}

	public function test_it_can_match_on_hydrated_collection() {
		$property_a = new Property( 'key_1', 'value_1' );
		$property_b = new Property( 'key_2', 'value_2' );
		$property_c = new Property( 'key_3', 'value_3' );
		$property_d = new Property( 'key_4', 'value_4' );

		$properties = new PostMetaPropertyCollection( 0, [
			$property_a,
			$property_b,
			$property_c,
			$property_d,
		] );

		$expr     = Criteria::expr();
		$criteria = Criteria::create()
		                    ->where( $expr->eq( 'key', $property_b->getKey() ) )
		                    ->orWhere( $expr->eq( 'key', $property_c->getKey() ) );

		$matched_properties = $properties->matching( $criteria );

		$this->assertCount( 2, $matched_properties );
		$this->assertInstanceOf( Property::class, $matched_properties[0] );
		$this->assertEquals( $property_b->getValue(), $matched_properties[0]->getValue() );
		$this->assertInstanceOf( Property::class, $matched_properties[1] );
		$this->assertEquals( $property_c->getValue(), $matched_properties[1]->getValue() );
	}
}
