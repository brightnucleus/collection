<?php declare( strict_types=1 );

namespace BrightNucleus\Collection\Tests\Fixtures;

use BrightNucleus\Collection\Criteria;
use BrightNucleus\Collection\Entity;
use BrightNucleus\Collection\Repository;
use BrightNucleus\Collection\WPQueryRepository;
use BrightNucleus\Exception\InvalidArgumentException;
use BrightNucleus\Exception\RuntimeException;
use WP_Error;
use WP_Post;

/**
 * BlogPostRepository fixture that demonstrates implementing the Repository pattern
 * for WordPress posts with caching and transaction support.
 */
final class BlogPostRepository implements Repository
{

    private BlogPostCollection $collection;
    private array $cache = [];
    private bool $cacheEnabled = true;
    private array $pendingChanges = [];
    private bool $inTransaction = false;
    private array $hooks = [];

    public function __construct( ?BlogPostCollection $collection = null )
    {
        if ($collection === null) {
            // Initialize with empty collection that will lazily query when needed
            $this->collection = new BlogPostCollection();
        } else {
            $this->collection = $collection;
        }
    }

    /**
     * Find a single post by its ID.
     */
    public function find( $id )
    {
        if ($this->cacheEnabled && isset($this->cache[$id]) ) {
            return $this->cache[$id];
        }

        $post = get_post($id);
        
        if (! $post instanceof WP_Post ) {
            return null;
        }

        $entity = new BlogPost($post);

        if ($this->cacheEnabled ) {
            $this->cache[$id] = $entity;
        }

        return $entity;
    }

    /**
     * Find all posts in the repository.
     */
    public function findAll()
    {
        // Return the collection which will lazily hydrate when accessed
        return $this->collection;
    }

    /**
     * Find posts matching specific criteria.
     */
    public function findBy( Criteria $criteria )
    {
        return $this->collection->matching($criteria);
    }

    /**
     * Find a single post matching specific criteria.
     */
    public function findOneBy( Criteria $criteria )
    {
        $results = $this->findBy($criteria);
        return $results->first() ?: null;
    }

    /**
     * Persist a blog post entity.
     */
    public function persist( $entity )
    {
        if (! $entity instanceof BlogPost ) {
            throw new InvalidArgumentException( 
                'BlogPostRepository can only persist BlogPost entities.'
            );
        }

        $postData = $this->preparePostData($entity);

        if ($this->inTransaction ) {
            $this->pendingChanges[] = [
            'action' => 'persist',
            'entity' => $entity,
            'data' => $postData
            ];
            return $entity;
        }

        return $this->executePersist($entity, $postData);
    }

    /**
     * Delete a blog post.
     */
    public function delete( BlogPost $entity ): bool
    {
        if ($this->inTransaction ) {
            $this->pendingChanges[] = [
            'action' => 'delete',
            'entity' => $entity
            ];
            return true;
        }

        return $this->executeDelete($entity);
    }

    /**
     * Find posts by author.
     */
    public function findByAuthor( int $authorId ): BlogPostCollection
    {
        return $this->collection->byAuthor($authorId);
    }

    /**
     * Find published posts.
     */
    public function findPublished(): BlogPostCollection
    {
        return $this->collection->published();
    }

    /**
     * Find draft posts.
     */
    public function findDrafts(): BlogPostCollection
    {
        return $this->collection->drafts();
    }

    /**
     * Find posts in a specific category.
     */
    public function findByCategory( string $category ): BlogPostCollection
    {
        return $this->collection->inCategory($category);
    }

    /**
     * Find posts with a specific tag.
     */
    public function findByTag( string $tag ): BlogPostCollection
    {
        return $this->collection->withTag($tag);
    }

    /**
     * Search posts by keyword.
     */
    public function search( string $keyword ): BlogPostCollection
    {
        return $this->collection->search($keyword);
    }

    /**
     * Find recent posts.
     */
    public function findRecent( int $days = 7 ): BlogPostCollection
    {
        return $this->collection->recent($days);
    }

    /**
     * Find featured posts.
     */
    public function findFeatured(): BlogPostCollection
    {
        return $this->collection->featured();
    }

    /**
     * Count all posts.
     */
    public function count(): int
    {
        return $this->collection->count();
    }

    /**
     * Count posts matching criteria.
     */
    public function countBy( Criteria $criteria ): int
    {
        return $this->findBy($criteria)->count();
    }

    /**
     * Check if a post exists.
     */
    public function exists( $id ): bool
    {
        return $this->find($id) !== null;
    }

    /**
     * Begin a transaction.
     */
    public function beginTransaction(): void
    {
        if ($this->inTransaction ) {
            throw new RuntimeException('Transaction already in progress.');
        }

        $this->inTransaction = true;
        $this->pendingChanges = [];
    }

    /**
     * Commit the transaction.
     */
    public function commit(): bool
    {
        if (! $this->inTransaction ) {
            throw new RuntimeException('No transaction in progress.');
        }

        $success = true;

        foreach ( $this->pendingChanges as $change ) {
            switch ( $change['action'] ) {
            case 'persist':
                $result = $this->executePersist($change['entity'], $change['data']);
                if (! $result ) {
                          $success = false;
                }
                break;

            case 'delete':
                $result = $this->executeDelete($change['entity']);
                if (! $result ) {
                    $success = false;
                }
                break;
            }

            if (! $success ) {
                $this->rollback();
                return false;
            }
        }

        $this->inTransaction = false;
        $this->pendingChanges = [];

        return true;
    }

    /**
     * Rollback the transaction.
     */
    public function rollback(): void
    {
        if (! $this->inTransaction ) {
            throw new RuntimeException('No transaction in progress.');
        }

        $this->inTransaction = false;
        $this->pendingChanges = [];
    }

    /**
     * Enable or disable caching.
     */
    public function setCacheEnabled( bool $enabled ): void
    {
        $this->cacheEnabled = $enabled;
        
        if (! $enabled ) {
            $this->clearCache();
        }
    }

    /**
     * Clear the cache.
     */
    public function clearCache(): void
    {
        $this->cache = [];
    }

    /**
     * Warm the cache with specific posts.
     */
    public function warmCache( array $ids ): void
    {
        foreach ( $ids as $id ) {
            $this->find($id);
        }
    }

    /**
     * Register a hook for repository events.
     */
    public function registerHook( string $event, callable $callback ): void
    {
        if (! isset($this->hooks[$event]) ) {
            $this->hooks[$event] = [];
        }

        $this->hooks[$event][] = $callback;
    }

    /**
     * Trigger hooks for an event.
     */
    private function triggerHooks( string $event, $data = null ): void
    {
        if (! isset($this->hooks[$event]) ) {
            return;
        }

        foreach ( $this->hooks[$event] as $callback ) {
            $callback($data);
        }
    }

    /**
     * Prepare post data for persistence.
     */
    private function preparePostData( BlogPost $entity ): array
    {
        $post = $entity->getWrappedPost();

        return [
        'ID' => $entity->getId() ?: 0,
        'post_title' => $entity->getTitle(),
        'post_content' => $entity->getContent(),
        'post_excerpt' => $entity->getExcerpt(),
        'post_status' => $entity->getStatus(),
        'post_author' => $entity->getAuthorId(),
        'post_name' => $entity->getSlug(),
        'post_type' => 'post',
        'comment_status' => $post->comment_status ?? 'open',
        'ping_status' => $post->ping_status ?? 'open',
        ];
    }

    /**
     * Execute the persist operation.
     */
    private function executePersist( BlogPost $entity, array $postData )
    {
        $this->triggerHooks('before_persist', $entity);

        if ($entity->getId() ) {
            $result = wp_update_post($postData, true);
        } else {
            $result = wp_insert_post($postData, true);
        }

        if (is_wp_error($result) ) {
            throw new RuntimeException(
                'Failed to persist post: ' . $result->get_error_message()
            );
        }

        // Refresh the entity with the updated post
        $updatedPost = get_post($result);
        $updatedEntity = new BlogPost($updatedPost);

        if ($this->cacheEnabled ) {
            $this->cache[$result] = $updatedEntity;
        }

        $this->triggerHooks('after_persist', $updatedEntity);

        return $updatedEntity;
    }

    /**
     * Execute the delete operation.
     */
    private function executeDelete( BlogPost $entity ): bool
    {
        $this->triggerHooks('before_delete', $entity);

        $result = wp_delete_post($entity->getId(), true);

        if (! $result ) {
            return false;
        }

        unset($this->cache[$entity->getId()]);

        $this->triggerHooks('after_delete', $entity);

        return true;
    }

    /**
     * Create a new query builder for complex queries.
     */
    public function createQueryBuilder(): BlogPostQueryBuilder
    {
        return new BlogPostQueryBuilder($this);
    }

    /**
     * Get repository statistics.
     */
    public function getStatistics(): array
    {
        return [
        'total_posts' => $this->count(),
        'published' => $this->findPublished()->count(),
        'drafts' => $this->findDrafts()->count(),
        'cache_size' => count($this->cache),
        'in_transaction' => $this->inTransaction,
        'pending_changes' => count($this->pendingChanges),
        ];
    }
}