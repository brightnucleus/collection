<?php declare( strict_types=1 );

namespace BrightNucleus\Collection\Tests\Integration;

use BrightNucleus\Collection\Criteria;
use BrightNucleus\Collection\Tests\Fixtures\BlogPost;
use BrightNucleus\Collection\Tests\Fixtures\BlogPostCollection;
use BrightNucleus\Collection\Tests\Fixtures\BlogPostRepository;
use BrightNucleus\Exception\InvalidArgumentException;
use BrightNucleus\Exception\RuntimeException;
use WP_Post;
use WP_UnitTest_Factory;
use WP_UnitTestCase;

/**
 * Integration tests for BlogPostRepository demonstrating repository pattern usage.
 */
final class BlogPostRepositoryTest extends WP_UnitTestCase
{

    protected $factory;
    private BlogPostRepository $repository;
    private array $testPosts = [];
    private array $testAuthors = [];

    public function setUp(): void
    {
        parent::setUp();
        $this->factory = new WP_UnitTest_Factory();
        $this->createTestData();
        
        // Initialize repository with a collection that can query all test posts
        $query = new \WP_Query(
            [
            'posts_per_page' => -1,
            'post_type' => 'post',
            'post_status' => 'any'
            ] 
        );
        $collection = new BlogPostCollection($query);
        $this->repository = new BlogPostRepository($collection);
    }

    public function tearDown(): void
    {
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
        $this->testAuthors[] = $this->factory->user->create(
            [
            'user_login' => 'test_author',
            'display_name' => 'Test Author'
            ] 
        );

        // Create test posts
        $this->testPosts[] = $this->factory->post->create(
            [
            'post_title' => 'Test Post 1',
            'post_status' => 'publish',
            'post_author' => $this->testAuthors[0],
            'post_content' => 'This is test content for post 1.'
            ] 
        );

        $this->testPosts[] = $this->factory->post->create(
            [
            'post_title' => 'Test Post 2',
            'post_status' => 'draft',
            'post_author' => $this->testAuthors[0],
            'post_content' => 'This is test content for post 2.'
            ] 
        );

        $this->testPosts[] = $this->factory->post->create(
            [
            'post_title' => 'Test Post 3',
            'post_status' => 'publish',
            'post_author' => $this->testAuthors[0],
            'post_content' => 'This is test content for post 3.'
            ] 
        );

        // Add metadata
        update_post_meta($this->testPosts[0], 'priority', 'high');
        update_post_meta($this->testPosts[1], 'priority', 'low');
    }

    public function test_it_can_find_post_by_id()
    {
        $post = $this->repository->find($this->testPosts[0]);

        $this->assertInstanceOf(BlogPost::class, $post);
        $this->assertEquals($this->testPosts[0], $post->getId());
        $this->assertEquals('Test Post 1', $post->getTitle());
    }

    public function test_it_returns_null_for_non_existent_post()
    {
        $post = $this->repository->find(999999);
        $this->assertNull($post);
    }

    public function test_it_can_find_all_posts()
    {
        $collection = $this->repository->findAll();

        $this->assertInstanceOf(BlogPostCollection::class, $collection);
        $this->assertGreaterThanOrEqual(3, $collection->count());
    }

    public function test_it_can_find_posts_by_criteria()
    {
        $expr = Criteria::expr();
        $criteria = Criteria::create()
            ->where($expr->eq('post_status', 'publish'));

        $collection = $this->repository->findBy($criteria);

        $this->assertInstanceOf(BlogPostCollection::class, $collection);
        $this->assertGreaterThanOrEqual(2, $collection->count());

        foreach ( $collection as $post ) {
            $this->assertTrue($post->isPublished());
        }
    }

    public function test_it_can_find_one_post_by_criteria()
    {
        $expr = Criteria::expr();
        $criteria = Criteria::create()
            ->where($expr->eq('post_status', 'draft'));

        $post = $this->repository->findOneBy($criteria);

        $this->assertInstanceOf(BlogPost::class, $post);
        $this->assertTrue($post->isDraft());
    }

    public function test_it_can_persist_new_post()
    {
        $newPost = new WP_Post(
            (object) [
            'post_title' => 'New Test Post',
            'post_content' => 'New content',
            'post_status' => 'draft',
            'post_author' => $this->testAuthors[0]
            ] 
        );

        $blogPost = new BlogPost($newPost);
        $persisted = $this->repository->persist($blogPost);

        $this->assertInstanceOf(BlogPost::class, $persisted);
        $this->assertNotNull($persisted->getId());
        $this->assertEquals('New Test Post', $persisted->getTitle());

        // Verify it was saved to database
        $fromDb = get_post($persisted->getId());
        $this->assertInstanceOf(WP_Post::class, $fromDb);
        $this->assertEquals('New Test Post', $fromDb->post_title);

        // Clean up
        wp_delete_post($persisted->getId(), true);
    }

    public function test_it_can_update_existing_post()
    {
        $post = $this->repository->find($this->testPosts[0]);
        $originalTitle = $post->getTitle();

        // Modify the post
        $wpPost = $post->getWrappedPost();
        $wpPost->post_title = 'Updated Title';
        $updatedPost = new BlogPost($wpPost);

        $persisted = $this->repository->persist($updatedPost);

        $this->assertEquals($this->testPosts[0], $persisted->getId());
        $this->assertEquals('Updated Title', $persisted->getTitle());

        // Verify it was updated in database
        $fromDb = get_post($this->testPosts[0]);
        $this->assertEquals('Updated Title', $fromDb->post_title);

        // Restore original title
        $wpPost->post_title = $originalTitle;
        $this->repository->persist(new BlogPost($wpPost));
    }

    public function test_it_can_delete_post()
    {
        // Create a post to delete
        $postId = $this->factory->post->create(
            [
            'post_title' => 'Post to Delete'
            ] 
        );

        $post = $this->repository->find($postId);
        $this->assertNotNull($post);

        // Delete the post
        $result = $this->repository->delete($post);
        $this->assertTrue($result);

        // Verify it was deleted
        $deleted = $this->repository->find($postId);
        $this->assertNull($deleted);
    }

    public function test_it_throws_exception_for_invalid_entity()
    {
        $this->expectException(InvalidArgumentException::class);
        
        $invalidEntity = new \stdClass();
        $this->repository->persist($invalidEntity);
    }

    public function test_it_can_find_by_specific_methods()
    {
        // Test findByAuthor
        $byAuthor = $this->repository->findByAuthor($this->testAuthors[0]);
        $this->assertGreaterThanOrEqual(3, $byAuthor->count());

        // Test findPublished
        $published = $this->repository->findPublished();
        $this->assertGreaterThanOrEqual(2, $published->count());

        // Test findDrafts
        $drafts = $this->repository->findDrafts();
        $this->assertGreaterThanOrEqual(1, $drafts->count());

        // Test findRecent
        $recent = $this->repository->findRecent(30);
        $this->assertGreaterThanOrEqual(3, $recent->count());
    }

    public function test_it_can_count_posts()
    {
        $totalCount = $this->repository->count();
        $this->assertGreaterThanOrEqual(3, $totalCount);

        // Count by criteria
        $expr = Criteria::expr();
        $criteria = Criteria::create()
            ->where($expr->eq('post_status', 'publish'));

        $publishedCount = $this->repository->countBy($criteria);
        $this->assertGreaterThanOrEqual(2, $publishedCount);
        $this->assertLessThanOrEqual($totalCount, $publishedCount);
    }

    public function test_it_can_check_existence()
    {
        $this->assertTrue($this->repository->exists($this->testPosts[0]));
        $this->assertFalse($this->repository->exists(999999));
    }

    public function test_it_supports_caching()
    {
        // Enable caching
        $this->repository->setCacheEnabled(true);

        // First access should load from database
        $post1 = $this->repository->find($this->testPosts[0]);
        $this->assertInstanceOf(BlogPost::class, $post1);

        // Second access should use cache (same object reference)
        $post2 = $this->repository->find($this->testPosts[0]);
        $this->assertSame($post1, $post2);

        // Clear cache
        $this->repository->clearCache();

        // After clearing, should get new object
        $post3 = $this->repository->find($this->testPosts[0]);
        $this->assertNotSame($post1, $post3);
        $this->assertEquals($post1->getId(), $post3->getId());

        // Disable caching
        $this->repository->setCacheEnabled(false);

        // Should always get new objects
        $post4 = $this->repository->find($this->testPosts[0]);
        $post5 = $this->repository->find($this->testPosts[0]);
        $this->assertNotSame($post4, $post5);
    }

    public function test_it_can_warm_cache()
    {
        $this->repository->setCacheEnabled(true);
        $this->repository->clearCache();

        // Warm cache with specific IDs
        $this->repository->warmCache($this->testPosts);

        // All warmed posts should be cached
        foreach ( $this->testPosts as $postId ) {
            $post = $this->repository->find($postId);
            $this->assertInstanceOf(BlogPost::class, $post);
        }
    }

    public function test_it_supports_transactions()
    {
        // Create a post for transaction testing
        $postId = $this->factory->post->create(
            [
            'post_title' => 'Transaction Test Post',
            'post_status' => 'draft'
            ] 
        );

        $post = $this->repository->find($postId);

        // Begin transaction
        $this->repository->beginTransaction();

        // Make changes within transaction
        $wpPost = $post->getWrappedPost();
        $wpPost->post_title = 'Modified in Transaction';
        $wpPost->post_status = 'publish';
        $this->repository->persist(new BlogPost($wpPost));

        // Changes should be pending, not yet applied
        $fromDb = get_post($postId);
        $this->assertEquals('Transaction Test Post', $fromDb->post_title);
        $this->assertEquals('draft', $fromDb->post_status);

        // Commit transaction
        $result = $this->repository->commit();
        $this->assertTrue($result);

        // Now changes should be applied
        $fromDb = get_post($postId);
        $this->assertEquals('Modified in Transaction', $fromDb->post_title);
        $this->assertEquals('publish', $fromDb->post_status);

        // Clean up
        wp_delete_post($postId, true);
    }

    public function test_it_can_rollback_transaction()
    {
        // Create a post for transaction testing
        $postId = $this->factory->post->create(
            [
            'post_title' => 'Rollback Test Post',
            'post_status' => 'draft'
            ] 
        );

        $post = $this->repository->find($postId);

        // Begin transaction
        $this->repository->beginTransaction();

        // Make changes within transaction
        $wpPost = $post->getWrappedPost();
        $wpPost->post_title = 'Should be Rolled Back';
        $this->repository->persist(new BlogPost($wpPost));

        // Rollback transaction
        $this->repository->rollback();

        // Changes should not be applied
        $fromDb = get_post($postId);
        $this->assertEquals('Rollback Test Post', $fromDb->post_title);

        // Clean up
        wp_delete_post($postId, true);
    }

    public function test_it_throws_exception_for_invalid_transaction_operations()
    {
        // Test commit without transaction
        $this->expectException(RuntimeException::class);
        $this->repository->commit();
    }

    public function test_it_supports_hooks()
    {
        $beforePersistCalled = false;
        $afterPersistCalled = false;
        $persistedEntity = null;

        // Register hooks
        $this->repository->registerHook(
            'before_persist', function ( $entity ) use ( &$beforePersistCalled ) {
                $beforePersistCalled = true;
            } 
        );

        $this->repository->registerHook(
            'after_persist', function ( $entity ) use ( &$afterPersistCalled, &$persistedEntity ) {
                $afterPersistCalled = true;
                $persistedEntity = $entity;
            } 
        );

        // Create and persist a post
        $newPost = new WP_Post(
            (object) [
            'post_title' => 'Hook Test Post',
            'post_content' => 'Testing hooks',
            'post_status' => 'draft',
            'post_author' => $this->testAuthors[0]
            ] 
        );

        $blogPost = new BlogPost($newPost);
        $persisted = $this->repository->persist($blogPost);

        // Verify hooks were called
        $this->assertTrue($beforePersistCalled);
        $this->assertTrue($afterPersistCalled);
        $this->assertInstanceOf(BlogPost::class, $persistedEntity);
        $this->assertEquals('Hook Test Post', $persistedEntity->getTitle());

        // Clean up
        wp_delete_post($persisted->getId(), true);
    }

    public function test_it_provides_statistics()
    {
        $stats = $this->repository->getStatistics();

        $this->assertArrayHasKey('total_posts', $stats);
        $this->assertArrayHasKey('published', $stats);
        $this->assertArrayHasKey('drafts', $stats);
        $this->assertArrayHasKey('cache_size', $stats);
        $this->assertArrayHasKey('in_transaction', $stats);
        $this->assertArrayHasKey('pending_changes', $stats);

        $this->assertGreaterThanOrEqual(3, $stats['total_posts']);
        $this->assertGreaterThanOrEqual(2, $stats['published']);
        $this->assertGreaterThanOrEqual(1, $stats['drafts']);
        $this->assertFalse($stats['in_transaction']);
        $this->assertEquals(0, $stats['pending_changes']);
    }
}