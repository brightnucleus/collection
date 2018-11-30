<?php declare( strict_types=1 );

use BrightNucleus\Collection\Criteria;
use BrightNucleus\Collection\NullCriteria;
use BrightNucleus\Collection\PostCollection;

final class PostCollectionTest extends WP_UnitTestCase {

	public function test_it_can_be_instantiated() {
		$posts = new PostCollection();

		$this->assertInstanceOf( PostCollection::class, $posts );
	}

	public function test_it_accepts_posts() {
		$factory = new WP_UnitTest_Factory();
		$post_a  = $factory->post->create_and_get();
		$post_b  = $factory->post->create_and_get();
		$posts   = new PostCollection( [ $post_a, $post_b ] );

		$this->assertCount( 2, $posts );
		foreach ( $posts as $post ) {
			$this->assertInstanceOf( WP_Post::class, $post );
		}
	}

	public function test_it_accepts_a_wp_query() {
		$factory = new WP_UnitTest_Factory();
		$post_a  = $factory->post->create_and_get();
		$post_b  = $factory->post->create_and_get();

		$query_args = [
			'post__in' => [ $post_a->ID, $post_b->ID ],
		];
		$posts      = new PostCollection( new WP_Query( $query_args ) );

		$this->assertCount( 2, $posts );
		foreach ( $posts as $post ) {
			$this->assertInstanceOf( WP_Post::class, $post );
		}
	}

	public function test_it_accepts_another_collection() {
		$factory = new WP_UnitTest_Factory();
		$post_a  = $factory->post->create_and_get();
		$post_b  = $factory->post->create_and_get();

		$posts_a = new PostCollection( [ $post_a, $post_b ] );

		$this->assertCount( 2, $posts_a );
		foreach ( $posts_a as $post ) {
			$this->assertInstanceOf( WP_Post::class, $post );
		}

		$posts_b = new PostCollection( $posts_a );

		$this->assertCount( 2, $posts_b );
		foreach ( $posts_b as $post ) {
			$this->assertInstanceOf( WP_Post::class, $post );
		}
	}

	public function test_it_can_match_on_criteria() {
		$factory = new WP_UnitTest_Factory();
		$post_a  = $factory->post->create_and_get();
		$post_b  = $factory->post->create_and_get();
		$post_c  = $factory->post->create_and_get();
		$post_d  = $factory->post->create_and_get();

		$posts = new PostCollection( new NullCriteria() );

		$expr     = Criteria::expr();
		$criteria = Criteria::create()
		                    ->where( $expr->eq( 'ID', $post_b->ID ) )
		                    ->orWhere( $expr->eq( 'ID', $post_c->ID ) );

		$matched_posts = $posts->matching( $criteria );

		$this->assertCount( 2, $matched_posts );
		$this->assertInstanceOf( WP_Post::class, $matched_posts[0] );
		$this->assertEquals( $post_b->ID, $matched_posts[0]->ID );
		$this->assertInstanceOf( WP_Post::class, $matched_posts[1] );
		$this->assertEquals( $post_c->ID, $matched_posts[1]->ID );
	}

	public function test_it_can_match_on_hydrated_collection() {
		$factory = new WP_UnitTest_Factory();
		$post_a  = $factory->post->create_and_get();
		$post_b  = $factory->post->create_and_get();
		$post_c  = $factory->post->create_and_get();
		$post_d  = $factory->post->create_and_get();

		$posts = new PostCollection( [
			$post_a,
			$post_b,
			$post_c,
			$post_d,
		] );

		$expr     = Criteria::expr();
		$criteria = Criteria::create()
		                    ->where( $expr->eq( 'ID', $post_b->ID ) )
		                    ->orWhere( $expr->eq( 'ID', $post_c->ID ) );

		$matched_posts = $posts->matching( $criteria );

		$this->assertCount( 2, $matched_posts );
		$this->assertInstanceOf( WP_Post::class, $matched_posts[0] );
		$this->assertEquals( $post_b->ID, $matched_posts[0]->ID );
		$this->assertInstanceOf( WP_Post::class, $matched_posts[1] );
		$this->assertEquals( $post_c->ID, $matched_posts[1]->ID );
	}

	public function test_it_can_match_on_wp_query() {
		$factory = new WP_UnitTest_Factory();
		$post_a  = $factory->post->create_and_get();
		$post_b  = $factory->post->create_and_get();
		$post_c  = $factory->post->create_and_get();
		$post_d  = $factory->post->create_and_get();

		$posts = new PostCollection(
			new WP_Query( [
				'post_type' => 'post',
				'orderby'   => 'ID',
				'order'     => 'ASC',
			] )
		);

		$expr     = Criteria::expr();
		$criteria = Criteria::create()
		                    ->where( $expr->eq( 'ID', $post_b->ID ) )
		                    ->orWhere( $expr->eq( 'ID', $post_c->ID ) );

		$matched_posts = $posts->matching( $criteria );

		$this->assertCount( 2, $matched_posts );
		$this->assertInstanceOf( WP_Post::class, $matched_posts[0] );
		$this->assertEquals( $post_b->ID, $matched_posts[0]->ID );
		$this->assertInstanceOf( WP_Post::class, $matched_posts[1] );
		$this->assertEquals( $post_c->ID, $matched_posts[1]->ID );
	}
}
