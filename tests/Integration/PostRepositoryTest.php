<?php declare( strict_types=1 );

namespace BrightNucleus\Collection\Tests\Integration;

use BrightNucleus\Collection\Criteria;
use BrightNucleus\Collection\PostRepository;
use WP_Post;
use WP_UnitTest_Factory;
use WP_UnitTestCase;

final class PostRepositoryTest extends WP_UnitTestCase {

	public function test_it_can_be_instantiated() {
		$posts = new PostRepository();

		$this->assertInstanceOf( PostRepository::class, $posts );
	}

	public function test_it_can_find_all_posts() {
		$factory = new WP_UnitTest_Factory();
		$post_a  = $factory->post->create_and_get();
		$post_b  = $factory->post->create_and_get();
		$post_c  = $factory->post->create_and_get();
		$posts   = ( new PostRepository() )->findAll();

		$this->assertCount( 3, $posts );
		foreach ( $posts as $post ) {
			$this->assertInstanceOf( WP_Post::class, $post );
		}
	}

	public function test_it_can_find_posts_by_criteria() {
		$factory = new WP_UnitTest_Factory();
		$post_a  = $factory->post->create_and_get();
		$post_b  = $factory->post->create_and_get();
		$post_c  = $factory->post->create_and_get();
		$post_d  = $factory->post->create_and_get();

		$expr     = Criteria::expr();
		$criteria = Criteria::create()
		                    ->where( $expr->eq( 'ID', $post_b->ID ) )
		                    ->orWhere( $expr->eq( 'ID', $post_c->ID ) );

		$posts = ( new PostRepository() )->findBy( $criteria );

		$this->assertCount( 2, $posts );
		$this->assertInstanceOf( WP_Post::class, $posts[0] );
		$this->assertEquals( $post_b->ID, $posts[0]->ID );
		$this->assertInstanceOf( WP_Post::class, $posts[1] );
		$this->assertEquals( $post_c->ID, $posts[1]->ID );
	}

	public function test_it_can_find_a_singular_post() {
		$factory = new WP_UnitTest_Factory();
		$post_a  = $factory->post->create_and_get();
		$post_b  = $factory->post->create_and_get();
		$post_c  = $factory->post->create_and_get();

		$expr     = Criteria::expr();
		$criteria = Criteria::create()
		                    ->where( $expr->eq( 'ID', $post_b->ID ) );

		$post = ( new PostRepository() )->findOneBy( $criteria );

		$this->assertInstanceOf( WP_Post::class, $post );
		$this->assertEquals( $post_b->ID, $post->ID );
	}

	public function test_it_can_fetch_a_sepcific_post() {
		$factory = new WP_UnitTest_Factory();
		$post_a  = $factory->post->create_and_get();
		$post_b  = $factory->post->create_and_get();
		$post_c  = $factory->post->create_and_get();

		$post = ( new PostRepository() )->find( $post_b->ID );

		$this->assertInstanceOf( WP_Post::class, $post );
		$this->assertEquals( $post_b->ID, $post->ID );
	}
}
