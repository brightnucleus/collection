<?php declare( strict_types=1 );

namespace BrightNucleus\Collection\Tests\Integration;

use BrightNucleus\Collection\Criteria;
use BrightNucleus\Collection\NullCriteria;
use BrightNucleus\Collection\PostCollection;
use WP_Post;
use WP_Query;
use WP_UnitTest_Factory;
use WP_UnitTestCase;

final class PostCollectionTest extends WP_UnitTestCase
{
    public function test_it_can_be_instantiated()
    {
        $posts = new PostCollection();

        $this->assertInstanceOf(PostCollection::class, $posts);
    }

    public function test_it_accepts_posts()
    {
        $factory = new WP_UnitTest_Factory();
        $post_a  = $factory->post->create_and_get();
        $post_b  = $factory->post->create_and_get();
        $posts   = new PostCollection([ $post_a, $post_b ]);

        $this->assertCount(2, $posts);
        foreach ( $posts as $post ) {
            $this->assertInstanceOf(WP_Post::class, $post);
        }
    }

    public function test_it_accepts_a_wp_query()
    {
        $factory = new WP_UnitTest_Factory();
        $post_a  = $factory->post->create_and_get();
        $post_b  = $factory->post->create_and_get();

        $query_args = [
        'post__in' => [ $post_a->ID, $post_b->ID ],
        ];
        $posts      = new PostCollection(new WP_Query($query_args));

        $this->assertCount(2, $posts);
        foreach ( $posts as $post ) {
            $this->assertInstanceOf(WP_Post::class, $post);
        }
    }

    public function test_it_accepts_another_collection()
    {
        $factory = new WP_UnitTest_Factory();
        $post_a  = $factory->post->create_and_get();
        $post_b  = $factory->post->create_and_get();

        $posts_a = new PostCollection([ $post_a, $post_b ]);

        $this->assertCount(2, $posts_a);
        foreach ( $posts_a as $post ) {
            $this->assertInstanceOf(WP_Post::class, $post);
        }

        $posts_b = new PostCollection($posts_a);

        $this->assertCount(2, $posts_b);
        foreach ( $posts_b as $post ) {
            $this->assertInstanceOf(WP_Post::class, $post);
        }
    }

    public function test_it_can_match_on_criteria()
    {
        $factory = new WP_UnitTest_Factory();
        $post_a  = $factory->post->create_and_get();
        $post_b  = $factory->post->create_and_get();
        $post_c  = $factory->post->create_and_get();
        $post_d  = $factory->post->create_and_get();

        $posts = new PostCollection(new NullCriteria());

        $expr     = Criteria::expr();
        $criteria = Criteria::create()
            ->where($expr->eq('ID', $post_b->ID))
            ->orWhere($expr->eq('ID', $post_c->ID));

        $matched_posts = $posts->matching($criteria);

        $this->assertCount(2, $matched_posts);
        $this->assertInstanceOf(WP_Post::class, $matched_posts[0]);
        $this->assertEquals($post_b->ID, $matched_posts[0]->ID);
        $this->assertInstanceOf(WP_Post::class, $matched_posts[1]);
        $this->assertEquals($post_c->ID, $matched_posts[1]->ID);
    }

    public function test_it_can_match_on_hydrated_collection()
    {
        $factory = new WP_UnitTest_Factory();
        $post_a  = $factory->post->create_and_get();
        $post_b  = $factory->post->create_and_get();
        $post_c  = $factory->post->create_and_get();
        $post_d  = $factory->post->create_and_get();

        $posts = new PostCollection(
            [
            $post_a,
            $post_b,
            $post_c,
            $post_d,
            ] 
        );

        $expr     = Criteria::expr();
        $criteria = Criteria::create()
            ->where($expr->eq('ID', $post_b->ID))
            ->orWhere($expr->eq('ID', $post_c->ID));

        $matched_posts = $posts->matching($criteria);

        $this->assertCount(2, $matched_posts);
        $this->assertInstanceOf(WP_Post::class, $matched_posts->first());
        $this->assertEquals($post_b->ID, $matched_posts->first()->ID);
        $this->assertInstanceOf(WP_Post::class, $matched_posts->last());
        $this->assertEquals($post_c->ID, $matched_posts->last()->ID);
    }

    public function test_it_can_match_on_wp_query()
    {
        $factory = new WP_UnitTest_Factory();
        $post_a  = $factory->post->create_and_get();
        $post_b  = $factory->post->create_and_get();
        $post_c  = $factory->post->create_and_get();
        $post_d  = $factory->post->create_and_get();

        $posts = new PostCollection(
            new WP_Query(
                [
                'post_type' => 'post',
                'orderby'   => 'ID',
                'order'     => 'ASC',
                ] 
            )
        );

        $expr     = Criteria::expr();
        $criteria = Criteria::create()
            ->where($expr->eq('ID', $post_b->ID))
            ->orWhere($expr->eq('ID', $post_c->ID));

        $matched_posts = $posts->matching($criteria);

        $this->assertCount(2, $matched_posts);
        $this->assertInstanceOf(WP_Post::class, $matched_posts->first());
        $this->assertEquals($post_b->ID, $matched_posts->first()->ID);
        $this->assertInstanceOf(WP_Post::class, $matched_posts->last());
        $this->assertEquals($post_c->ID, $matched_posts->last()->ID);
    }

    public function test_it_returns_same_references_for_same_ids()
    {
        $factory = new WP_UnitTest_Factory();
        $post_a  = $factory->post->create_and_get();
        $post_b  = $factory->post->create_and_get();
        $post_c  = $factory->post->create_and_get();
        $post_d  = $factory->post->create_and_get();

        $posts_a = new PostCollection(new NullCriteria());
        $posts_b = new PostCollection(new NullCriteria());

        $expr     = Criteria::expr();
        $criteria = Criteria::create()
            ->where($expr->eq('ID', $post_b->ID))
            ->orWhere($expr->eq('ID', $post_c->ID));

        $matched_posts_a = $posts_a->matching($criteria);
        $matched_posts_b = $posts_b->matching($criteria);

        $this->assertSame($matched_posts_a[0], $matched_posts_b[0]);
        $this->assertSame($matched_posts_a[1], $matched_posts_b[1]);
    }

    public function test_it_keeps_collections_immutable()
    {
        $factory = new WP_UnitTest_Factory();
        $post_a  = $factory->post->create_and_get();
        $post_b  = $factory->post->create_and_get();
        $post_c  = $factory->post->create_and_get();
        $post_d  = $factory->post->create_and_get();

        $posts = new PostCollection(
            [
            $post_a,
            $post_b,
            $post_c,
            $post_d,
            ] 
        );

        $expr       = Criteria::expr();
        $criteria_1 = Criteria::create()
            ->where($expr->eq('ID', $post_a->ID))
            ->orWhere($expr->eq('ID', $post_b->ID));
        $criteria_2 = Criteria::create()
            ->where($expr->eq('ID', $post_c->ID))
            ->orWhere($expr->eq('ID', $post_d->ID));

        $matched_posts_1 = $posts->matching($criteria_1);
        $matched_posts_2 = $posts->matching($criteria_2);

        $this->assertCount(2, $matched_posts_1);
        $this->assertInstanceOf(WP_Post::class, $matched_posts_1->first());
        $this->assertEquals($post_a->ID, $matched_posts_1->first()->ID);
        $this->assertInstanceOf(WP_Post::class, $matched_posts_1->last());
        $this->assertEquals($post_b->ID, $matched_posts_1->last()->ID);

        $this->assertCount(2, $matched_posts_2);
        $this->assertInstanceOf(WP_Post::class, $matched_posts_2->first());
        $this->assertEquals($post_c->ID, $matched_posts_2->first()->ID);
        $this->assertInstanceOf(WP_Post::class, $matched_posts_2->last());
        $this->assertEquals($post_d->ID, $matched_posts_2->last()->ID);
    }
}
