<?php declare( strict_types=1 );

namespace BrightNucleus\Collection\Tests\Integration;

use BrightNucleus\Collection\Criteria;
use BrightNucleus\Collection\Tests\Fixtures\BlogPost;
use BrightNucleus\Collection\Tests\Fixtures\BlogPostCollection;
use DateTime;
use WP_Post;
use WP_Query;
use WP_UnitTest_Factory;
use WP_UnitTestCase;

/**
 * Integration tests for BlogPostCollection demonstrating real-world usage patterns.
 */
final class BlogPostCollectionTest extends WP_UnitTestCase
{

    protected $factory;
    private array $testPosts = [];
    private array $testAuthors = [];
    private array $testCategories = [];
    private array $testTags = [];

    public function setUp(): void
    {
        parent::setUp();
        $this->factory = new WP_UnitTest_Factory();
        $this->createTestData();
    }

    public function tearDown(): void
    {
        // Clean up test data
        foreach ( $this->testPosts as $postId ) {
            wp_delete_post($postId, true);
        }
        foreach ( $this->testAuthors as $userId ) {
            wp_delete_user($userId);
        }
        parent::tearDown();
    }

    private function createTestData(): void
    {
        // Create test authors
        $this->testAuthors['john'] = $this->factory->user->create(
            [
            'user_login' => 'john_doe',
            'display_name' => 'John Doe'
            ] 
        );
        $this->testAuthors['jane'] = $this->factory->user->create(
            [
            'user_login' => 'jane_smith',
            'display_name' => 'Jane Smith'
            ] 
        );

        // Create test categories
        $this->testCategories['tech'] = $this->factory->category->create(
            [
            'slug' => 'technology',
            'name' => 'Technology'
            ] 
        );
        $this->testCategories['news'] = $this->factory->category->create(
            [
            'slug' => 'news',
            'name' => 'News'
            ] 
        );

        // Create test tags
        $this->testTags['php'] = $this->factory->tag->create(
            [
            'slug' => 'php',
            'name' => 'PHP'
            ] 
        );
        $this->testTags['wordpress'] = $this->factory->tag->create(
            [
            'slug' => 'wordpress',
            'name' => 'WordPress'
            ] 
        );

        // Create test posts with various statuses and metadata
        $this->testPosts[] = $this->factory->post->create(
            [
            'post_title' => 'Published Tech Post',
            'post_status' => 'publish',
            'post_author' => $this->testAuthors['john'],
            'post_category' => [ $this->testCategories['tech'] ],
            'tags_input' => [ 'php' ],
            'post_date' => date('Y-m-d H:i:s', strtotime('-2 days')),
            'comment_status' => 'open'
            ] 
        );

        $this->testPosts[] = $this->factory->post->create(
            [
            'post_title' => 'Draft News Post',
            'post_status' => 'draft',
            'post_author' => $this->testAuthors['jane'],
            'post_category' => [ $this->testCategories['news'] ],
            'post_date' => date('Y-m-d H:i:s', strtotime('-5 days'))
            ] 
        );

        $this->testPosts[] = $this->factory->post->create(
            [
            'post_title' => 'Another Published Post',
            'post_status' => 'publish',
            'post_author' => $this->testAuthors['john'],
            'post_category' => [ $this->testCategories['tech'] ],
            'tags_input' => [ 'wordpress' ],
            'post_date' => date('Y-m-d H:i:s', strtotime('-10 days'))
            ] 
        );

        // Add some comments to test posts
        $this->factory->comment->create_many(
            5, [
            'comment_post_ID' => $this->testPosts[0]
            ] 
        );

        // Add meta data to posts
        update_post_meta($this->testPosts[0], 'view_count', 100);
        update_post_meta($this->testPosts[0], 'featured', 'yes');
        update_post_meta($this->testPosts[2], 'view_count', 50);

        // Set featured image for one post
        $attachmentId = $this->factory->attachment->create_upload_object( 
            DIR_TESTDATA . '/images/test-image.jpg',
            $this->testPosts[0]
        );
        set_post_thumbnail($this->testPosts[0], $attachmentId);

        // Make one post sticky
        stick_post($this->testPosts[0]);
    }

    public function test_it_can_be_instantiated_with_various_arguments()
    {
        // Empty collection
        $collection1 = new BlogPostCollection();
        $this->assertInstanceOf(BlogPostCollection::class, $collection1);

        // With array of posts
        // Create collection with base query for test posts
        $query2 = new WP_Query([ 'post__in' => $this->testPosts, 'posts_per_page' => -1, 'post_status' => 'any' ]);
        $collection2 = new BlogPostCollection($query2);
        $this->assertCount(count($this->testPosts), $collection2);

        // With WP_Query
        $query = new WP_Query([ 'post__in' => $this->testPosts, 'post_status' => 'any' ]);
        $collection3 = new BlogPostCollection($query);
        $this->assertCount(count($this->testPosts), $collection3);

        // With Criteria
        $criteria = Criteria::create()->setMaxResults(2);
        $collection4 = new BlogPostCollection($criteria);
        $this->assertLessThanOrEqual(2, $collection4->count());
    }

    public function test_it_wraps_posts_as_blog_post_entities()
    {
        $collection = new BlogPostCollection([ get_post($this->testPosts[0]) ]);
        
        foreach ( $collection as $post ) {
            $this->assertInstanceOf(BlogPost::class, $post);
            $this->assertIsInt($post->getId());
            $this->assertIsString($post->getTitle());
            $this->assertInstanceOf(DateTime::class, $post->getPublishedDate());
        }
    }

    public function test_it_can_filter_by_status()
    {
        // Create collection with base query for test posts
        $query = new WP_Query([ 
            'post__in' => $this->testPosts, 
            'posts_per_page' => -1,
            'post_status' => 'any'  // Include all statuses
        ]);
        $collection = new BlogPostCollection($query);

        // Test published filter
        $published = $collection->published();
        $this->assertCount(2, $published);
        foreach ( $published as $post ) {
            $this->assertTrue($post->isPublished());
        }

        // Test drafts filter
        $drafts = $collection->drafts();
        $this->assertCount(1, $drafts);
        foreach ( $drafts as $post ) {
            $this->assertTrue($post->isDraft());
        }
    }

    public function test_it_can_filter_by_author()
    {
        // Create collection with base query for test posts
        $query = new WP_Query([ 
            'post__in' => $this->testPosts, 
            'posts_per_page' => -1,
            'post_status' => 'any'  // Include all statuses
        ]);
        $collection = new BlogPostCollection($query);

        $johnsPosts = $collection->byAuthor($this->testAuthors['john']);
        $this->assertCount(2, $johnsPosts);
        foreach ( $johnsPosts as $post ) {
            $this->assertEquals($this->testAuthors['john'], $post->getAuthorId());
        }

        $janesPosts = $collection->byAuthor($this->testAuthors['jane']);
        $this->assertCount(1, $janesPosts);
        foreach ( $janesPosts as $post ) {
            $this->assertEquals($this->testAuthors['jane'], $post->getAuthorId());
        }
    }

    public function test_it_can_filter_by_date_range()
    {
        // Create collection with base query for test posts
        $query = new WP_Query([ 
            'post__in' => $this->testPosts, 
            'posts_per_page' => -1,
            'post_status' => 'any'  // Include all statuses
        ]);
        $collection = new BlogPostCollection($query);

        // Test recent posts (last 7 days)
        $recent = $collection->recent(7);
        $this->assertCount(2, $recent);

        // Test specific date range
        $start = new DateTime('-6 days');
        $end = new DateTime('-1 day');
        $ranged = $collection->betweenDates($start, $end);
        $this->assertCount(2, $ranged);
    }

    public function test_it_can_filter_by_category()
    {
        // Create collection with base query for test posts
        $query = new WP_Query([ 
            'post__in' => $this->testPosts, 
            'posts_per_page' => -1,
            'post_status' => 'any'  // Include all statuses
        ]);
        $collection = new BlogPostCollection($query);

        $techPosts = $collection->inCategory('Technology');
        $this->assertCount(2, $techPosts);

        $newsPosts = $collection->inCategory('News');
        $this->assertCount(1, $newsPosts);
    }

    public function test_it_can_filter_by_tag()
    {
        // Create collection with base query for test posts
        $query = new WP_Query([ 
            'post__in' => $this->testPosts, 
            'posts_per_page' => -1,
            'post_status' => 'any'  // Include all statuses
        ]);
        $collection = new BlogPostCollection($query);

        $phpPosts = $collection->withTag('PHP');
        $this->assertCount(1, $phpPosts);

        $wpPosts = $collection->withTag('WordPress');
        $this->assertCount(1, $wpPosts);
    }

    public function test_it_can_search_posts()
    {
        // Create collection with base query for test posts
        $query = new WP_Query([ 
            'post__in' => $this->testPosts, 
            'posts_per_page' => -1,
            'post_status' => 'any'  // Include all statuses
        ]);
        $collection = new BlogPostCollection($query);

        $techResults = $collection->search('Tech');
        $this->assertCount(1, $techResults);

        $publishedResults = $collection->search('Published');
        $this->assertCount(2, $publishedResults);
    }

    public function test_it_can_get_featured_posts()
    {
        // Create collection with base query for test posts
        $query = new WP_Query([ 
            'post__in' => $this->testPosts, 
            'posts_per_page' => -1,
            'post_status' => 'any'  // Include all statuses
        ]);
        $collection = new BlogPostCollection($query);

        $featured = $collection->featured();
        $this->assertCount(1, $featured);
        $this->assertTrue($featured->first()->isSticky());
    }

    public function test_it_can_filter_posts_with_comments()
    {
        // Create collection with base query for test posts
        $query = new WP_Query([ 
            'post__in' => $this->testPosts, 
            'posts_per_page' => -1,
            'post_status' => 'any'  // Include all statuses
        ]);
        $collection = new BlogPostCollection($query);

        $withComments = $collection->withComments();
        $this->assertCount(1, $withComments);
        $this->assertGreaterThan(0, $withComments->first()->getCommentCount());
    }

    public function test_it_can_sort_posts()
    {
        // Create collection with base query for test posts
        $query = new WP_Query([ 
            'post__in' => $this->testPosts, 
            'posts_per_page' => -1,
            'post_status' => 'any'  // Include all statuses
        ]);
        $collection = new BlogPostCollection($query);

        // Test sorting by most commented
        $popular = $collection->popular();
        $first = $popular->first();
        $this->assertGreaterThan(0, $first->getCommentCount());

        // Test sorting by most recent
        $latest = $collection->latest();
        $dates = [];
        foreach ( $latest as $post ) {
            $dates[] = $post->getPublishedDate()->getTimestamp();
        }
        $sortedDates = $dates;
        sort($sortedDates);
        $this->assertEquals($dates, array_reverse($sortedDates));

        // Test alphabetical sorting
        $alphabetical = $collection->alphabetical();
        $titles = [];
        foreach ( $alphabetical as $post ) {
            $titles[] = $post->getTitle();
        }
        $sortedTitles = $titles;
        sort($sortedTitles);
        $this->assertEquals($sortedTitles, $titles);
    }

    public function test_it_can_limit_and_paginate_results()
    {
        // Create collection with base query for test posts
        $query = new WP_Query([ 
            'post__in' => $this->testPosts, 
            'posts_per_page' => -1,
            'post_status' => 'any'  // Include all statuses
        ]);
        $collection = new BlogPostCollection($query);

        // Test limit
        $limited = $collection->limit(2);
        $this->assertCount(2, $limited);

        // Test pagination
        $page1 = $collection->paginate(1, 2);
        $this->assertCount(2, $page1);

        $page2 = $collection->paginate(2, 2);
        $this->assertLessThanOrEqual(2, $page2->count());
    }

    public function test_it_can_filter_by_meta_values()
    {
        // Create collection with base query for test posts
        $query = new WP_Query([ 
            'post__in' => $this->testPosts, 
            'posts_per_page' => -1,
            'post_status' => 'any'  // Include all statuses
        ]);
        $collection = new BlogPostCollection($query);

        // Test filtering by meta value
        $featured = $collection->withMeta('featured', 'yes');
        $this->assertCount(1, $featured);

        // Test filtering by meta comparison
        $highViews = $collection->withMeta('view_count', 75, '>');
        $this->assertCount(1, $highViews);

        // Test posts with featured images
        $withImages = $collection->withFeaturedImage();
        $this->assertCount(1, $withImages);
        $this->assertNotNull($withImages->first()->getFeaturedImageUrl());
    }

    public function test_it_can_get_statistics()
    {
        // Create collection with base query for test posts
        $query = new WP_Query([ 
            'post__in' => $this->testPosts, 
            'posts_per_page' => -1,
            'post_status' => 'any'  // Include all statuses
        ]);
        $collection = new BlogPostCollection($query);

        $stats = $collection->getStatistics();

        $this->assertArrayHasKey('total_posts', $stats);
        $this->assertArrayHasKey('total_words', $stats);
        $this->assertArrayHasKey('average_words', $stats);
        $this->assertArrayHasKey('total_comments', $stats);
        $this->assertArrayHasKey('average_comments', $stats);
        $this->assertArrayHasKey('unique_authors', $stats);
        $this->assertArrayHasKey('unique_categories', $stats);
        $this->assertArrayHasKey('unique_tags', $stats);
        $this->assertArrayHasKey('average_reading_time', $stats);

        $this->assertEquals(count($this->testPosts), $stats['total_posts']);
        $this->assertEquals(2, $stats['unique_authors']);
    }

    public function test_it_can_group_posts()
    {
        // Create collection with base query for test posts
        $query = new WP_Query([ 
            'post__in' => $this->testPosts, 
            'posts_per_page' => -1,
            'post_status' => 'any'  // Include all statuses
        ]);
        $collection = new BlogPostCollection($query);

        // Group by author
        $byAuthor = $collection->groupBy('author');
        $this->assertCount(2, $byAuthor);
        $this->assertArrayHasKey('John Doe', $byAuthor);
        $this->assertArrayHasKey('Jane Smith', $byAuthor);

        // Group by status
        $byStatus = $collection->groupBy('status');
        $this->assertArrayHasKey('publish', $byStatus);
        $this->assertArrayHasKey('draft', $byStatus);
        $this->assertCount(2, $byStatus['publish']);
        $this->assertCount(1, $byStatus['draft']);

        // Group by year
        $byYear = $collection->groupBy('year');
        $this->assertCount(1, $byYear); // All posts are from current year
    }

    public function test_it_supports_fluent_chaining()
    {
        // Create collection with base query for test posts
        $query = new WP_Query([ 
            'post__in' => $this->testPosts, 
            'posts_per_page' => -1,
            'post_status' => 'any'  // Include all statuses
        ]);
        $collection = new BlogPostCollection($query);

        $filtered = $collection
            ->published()
            ->byAuthor($this->testAuthors['john'])
            ->latest()
            ->limit(1);

        $this->assertCount(1, $filtered);
        $post = $filtered->first();
        $this->assertTrue($post->isPublished());
        $this->assertEquals($this->testAuthors['john'], $post->getAuthorId());
    }

    public function test_it_maintains_immutability()
    {
        // Create collection with base query for test posts
        $query = new WP_Query([ 'post__in' => $this->testPosts, 'posts_per_page' => -1, 'post_status' => 'any' ]);
        $original = new BlogPostCollection($query);
        $originalCount = $original->count();

        $filtered = $original->published();
        $filteredCount = $filtered->count();

        // Original collection should remain unchanged
        $this->assertEquals($originalCount, $original->count());
        $this->assertNotEquals($originalCount, $filteredCount);

        // Multiple filters should not affect each other
        $drafts = $original->drafts();
        $published = $original->published();

        $this->assertNotEquals($drafts->count(), $published->count());
    }

    public function test_it_uses_identity_mapping()
    {
        // Create collections with base query for test posts
        $query1 = new WP_Query([ 
            'post__in' => $this->testPosts, 
            'posts_per_page' => -1,
            'post_status' => 'any'
        ]);
        $collection1 = new BlogPostCollection($query1);
        $query2 = new WP_Query([ 
            'post__in' => $this->testPosts, 
            'posts_per_page' => -1,
            'post_status' => 'any'
        ]);
        $collection2 = new BlogPostCollection($query2);

        // Same post ID should return same object reference
        $post1 = $collection1->first();
        $post2 = $collection2->first();

        $this->assertSame($post1, $post2);
    }

    public function test_it_handles_empty_collections_gracefully()
    {
        $empty = new BlogPostCollection([]);

        $this->assertCount(0, $empty);
        $this->assertFalse($empty->first());
        $this->assertFalse($empty->last());

        $stats = $empty->getStatistics();
        $this->assertEquals(0, $stats['total_posts']);
        $this->assertEquals(0, $stats['average_words']);

        $grouped = $empty->groupBy('author');
        $this->assertEmpty($grouped);
    }

    public function test_it_can_use_complex_criteria()
    {
        // Create collection with base query for test posts
        $query = new WP_Query([ 
            'post__in' => $this->testPosts, 
            'posts_per_page' => -1,
            'post_status' => 'any'  // Include all statuses
        ]);
        $collection = new BlogPostCollection($query);

        $expr = Criteria::expr();
        $criteria = Criteria::create()
            ->where($expr->eq('post_status', 'publish'))
            ->andWhere($expr->eq('post_author', (string)$this->testAuthors['john']))
            ->orderBy([ 'post_date' => Criteria::DESC ])
            ->setMaxResults(1);

        $filtered = $collection->matching($criteria);

        $this->assertCount(1, $filtered);
        $post = $filtered->first();
        $this->assertTrue($post->isPublished());
        $this->assertEquals($this->testAuthors['john'], $post->getAuthorId());
    }

    public function test_it_can_use_where_callback()
    {
        // Create collection with base query for test posts
        $query = new WP_Query([ 
            'post__in' => $this->testPosts, 
            'posts_per_page' => -1,
            'post_status' => 'any'  // Include all statuses
        ]);
        $collection = new BlogPostCollection($query);

        $filtered = $collection->where(
            function ( $query ) {
                return $query
                    ->published()
                    ->recent(3)
                    ->limit(1);
            } 
        );

        $this->assertCount(1, $filtered);
        $this->assertTrue($filtered->first()->isPublished());
    }
}