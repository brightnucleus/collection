<?php declare( strict_types=1 );

namespace BrightNucleus\Collection\Tests\Fixtures;

use BrightNucleus\Collection\AbstractWPQueryCollection;
use BrightNucleus\Collection\Criteria;
use BrightNucleus\Collection\HasEntityWrapper;
use BrightNucleus\Collection\PostTypeQueryGenerator;
use BrightNucleus\Collection\QueryGenerator;
use BrightNucleus\Collection\Scopeable;
use BrightNucleus\Collection\Scope;
use BrightNucleus\Exception\InvalidArgumentException;
use DateTime;

/**
 * BlogPostCollection fixture that demonstrates extending AbstractWPQueryCollection
 * with custom business logic and filtering methods.
 */
final class BlogPostCollection extends AbstractWPQueryCollection implements HasEntityWrapper, Scopeable
{

    private ?Scope $scope = null;
    private array $allowedStatuses = [ 'publish', 'draft', 'pending', 'private' ];

    public static function getEntityWrapperClass(): string
    {
        return BlogPost::class;
    }

    public static function assertType( $element ): void
    {
        if (! $element instanceof BlogPost ) {
            throw new InvalidArgumentException(
                sprintf(
                    'BlogPostCollection can only contain BlogPost instances, %s given.',
                    is_object($element) ? get_class($element) : gettype($element)
                )
            );
        }
    }

    protected function getQueryGenerator(): QueryGenerator
    {
        return new PostTypeQueryGenerator($this->criteria);
    }

    protected function getIdentityMapType(): string
    {
        return 'blog_posts';
    }

    /**
     * Filter collection to only published posts.
     */
    public function published(): self
    {
        $expr = Criteria::expr();
        $criteria = Criteria::create()
            ->where($expr->eq('post_status', 'publish'));

        return $this->matching($criteria);
    }

    /**
     * Filter collection to only draft posts.
     */
    public function drafts(): self
    {
        $expr = Criteria::expr();
        $criteria = Criteria::create()
            ->where($expr->eq('post_status', 'draft'));

        return $this->matching($criteria);
    }

    /**
     * Filter collection by author.
     */
    public function byAuthor( int $authorId ): self
    {
        $expr = Criteria::expr();
        // WordPress stores post_author as a string, so we need to cast for comparison
        $criteria = Criteria::create()
            ->where($expr->eq('post_author', (string)$authorId));

        return $this->matching($criteria);
    }

    /**
     * Filter collection by date range.
     */
    public function betweenDates( DateTime $start, DateTime $end ): self
    {
        $expr = Criteria::expr();
        $criteria = Criteria::create()
            ->where($expr->gte('post_date', $start->format('Y-m-d H:i:s')))
            ->andWhere($expr->lte('post_date', $end->format('Y-m-d H:i:s')));

        return $this->matching($criteria);
    }

    /**
     * Filter collection to posts from the last N days.
     */
    public function recent( int $days = 7 ): self
    {
        $start = new DateTime("-{$days} days");
        $end = new DateTime();

        return $this->betweenDates($start, $end);
    }

    /**
     * Filter collection by category.
     */
    public function inCategory( string $category ): self
    {
        $expr = Criteria::expr();
        $criteria = Criteria::create()
            ->where($expr->contains('category_name', $category));

        return $this->matching($criteria);
    }

    /**
     * Filter collection by tag.
     */
    public function withTag( string $tag ): self
    {
        $expr = Criteria::expr();
        $criteria = Criteria::create()
            ->where($expr->contains('tag', $tag));

        return $this->matching($criteria);
    }

    /**
     * Search posts by keyword in title or content.
     */
    public function search( string $keyword ): self
    {
        $expr = Criteria::expr();
        $criteria = Criteria::create()
            ->where($expr->contains('s', $keyword));

        return $this->matching($criteria);
    }

    /**
     * Filter to featured posts (sticky).
     */
    public function featured(): self
    {
        $stickyPosts = get_option('sticky_posts', []);
        
        if (empty($stickyPosts) ) {
            // Return empty collection
            return new self([]);
        }

        $expr = Criteria::expr();
        $criteria = Criteria::create()
            ->where($expr->in('ID', $stickyPosts));

        return $this->matching($criteria);
    }

    /**
     * Filter posts with comments.
     */
    public function withComments(): self
    {
        $expr = Criteria::expr();
        $criteria = Criteria::create()
            ->where($expr->gt('comment_count', 0));

        return $this->matching($criteria);
    }

    /**
     * Order by most commented.
     */
    public function popular(): self
    {
        $criteria = Criteria::create()
        ->orderBy([ 'comment_count' => Criteria::DESC ]);

        return $this->matching($criteria);
    }

    /**
     * Order by most recent.
     */
    public function latest(): self
    {
        $criteria = Criteria::create()
        ->orderBy([ 'post_date' => Criteria::DESC ]);

        return $this->matching($criteria);
    }

    /**
     * Order by title alphabetically.
     */
    public function alphabetical(): self
    {
        $criteria = Criteria::create()
        ->orderBy([ 'post_title' => Criteria::ASC ]);

        return $this->matching($criteria);
    }

    /**
     * Limit results.
     */
    public function limit( int $limit ): self
    {
        $criteria = Criteria::create()
        ->setMaxResults($limit);

        return $this->matching($criteria);
    }

    /**
     * Paginate results.
     */
    public function paginate( int $page, int $perPage = 10 ): self
    {
        $offset = ( $page - 1 ) * $perPage;
        
        $criteria = Criteria::create()
            ->setFirstResult($offset)
            ->setMaxResults($perPage);

        return $this->matching($criteria);
    }

    /**
     * Get posts by specific IDs.
     */
    public function withIds( array $ids ): self
    {
        $expr = Criteria::expr();
        $criteria = Criteria::create()
            ->where($expr->in('ID', $ids));

        return $this->matching($criteria);
    }

    /**
     * Exclude posts by IDs.
     */
    public function exclude( array $ids ): self
    {
        $expr = Criteria::expr();
        $criteria = Criteria::create()
            ->where($expr->notIn('ID', $ids));

        return $this->matching($criteria);
    }

    /**
     * Filter by meta value.
     */
    public function withMeta( string $key, $value, string $compare = '=' ): self
    {
        $expr = Criteria::expr();
        
        switch ( $compare ) {
        case '=':
            $comparison = $expr->eq("meta.{$key}", $value);
            break;
        case '!=':
            $comparison = $expr->neq("meta.{$key}", $value);
            break;
        case '>':
            // Convert to numeric for numeric comparisons
            if (is_numeric($value)) {
                $comparison = $expr->gt("meta.{$key}", (int)$value);
            } else {
                $comparison = $expr->gt("meta.{$key}", $value);
            }
            break;
        case '>=':
            $comparison = $expr->gte("meta.{$key}", $value);
            break;
        case '<':
            $comparison = $expr->lt("meta.{$key}", $value);
            break;
        case '<=':
            $comparison = $expr->lte("meta.{$key}", $value);
            break;
        case 'LIKE':
            $comparison = $expr->contains("meta.{$key}", $value);
            break;
        case 'IN':
            $comparison = $expr->in("meta.{$key}", (array) $value);
            break;
        case 'NOT IN':
            $comparison = $expr->notIn("meta.{$key}", (array) $value);
            break;
        default:
            throw new InvalidArgumentException("Invalid comparison operator: {$compare}");
        }

        $criteria = Criteria::create()->where($comparison);

        return $this->matching($criteria);
    }

    /**
     * Get only posts with featured images.
     */
    public function withFeaturedImage(): self
    {
        return $this->withMeta('_thumbnail_id', '', '!=');
    }

    /**
     * Apply a scope to the collection.
     */
    public function withScope( Scope $scope ): self
    {
        $collection = clone $this;
        $collection->scope = $scope;
        
        // Apply scope-specific criteria
        if (method_exists($scope, 'getCriteria') ) {
            $collection->criteria = $collection->criteria->merge($scope->getCriteria());
        }

        return $collection;
    }

    /**
     * Get the current scope.
     */
    public function getScope(): Scope
    {
        if ($this->scope === null ) {
            $this->scope = new \BrightNucleus\Collection\Status(\BrightNucleus\Collection\Status::PUBLISH);
        }
        return $this->scope;
    }

    /**
     * Add a specific scope for the current Scopeable.
     */
    public function addScope( Scope $scope ): self
    {
        $collection = clone $this;
        // If there's an existing scope of the same type, extend it
        if ($collection->scope !== null && get_class($collection->scope) === get_class($scope) ) {
            // Merge the scopes (implementation depends on your scope types)
            // For now, we just replace
            $collection->scope = $scope;
        } else {
            $collection->scope = $scope;
        }
        
        // Apply scope-specific criteria
        if (method_exists($scope, 'getCriteria') ) {
            $collection->criteria = $collection->criteria->merge($scope->getCriteria());
        }
        
        return $collection;
    }

    /**
     * Get statistics about the collection.
     */
    public function getStatistics(): array
    {
        $this->hydrate();
        
        $totalWords = 0;
        $totalComments = 0;
        $authors = [];
        $categories = [];
        $tags = [];

        foreach ( $this->collection as $post ) {
            $totalWords += $post->getWordCount();
            $totalComments += $post->getCommentCount();
            $authors[] = $post->getAuthorId();
            $categories = array_merge($categories, $post->getCategories());
            $tags = array_merge($tags, $post->getTags());
        }

        return [
        'total_posts' => $this->count(),
        'total_words' => $totalWords,
        'average_words' => $this->count() > 0 ? round($totalWords / $this->count()) : 0,
        'total_comments' => $totalComments,
        'average_comments' => $this->count() > 0 ? round($totalComments / $this->count(), 2) : 0,
        'unique_authors' => count(array_unique($authors)),
        'unique_categories' => count(array_unique($categories)),
        'unique_tags' => count(array_unique($tags)),
        'average_reading_time' => $this->count() > 0 
        ? round(
            array_sum(
                array_map(
                    function ( $p ) {
                            return $p->getReadingTime(); 
                    }, $this->toArray()
                )
            ) / $this->count(), 1
        )
        : 0
        ];
    }

    /**
     * Group posts by a specific field.
     */
    public function groupBy( string $field ): array
    {
        $this->hydrate();
        $grouped = [];

        foreach ( $this->collection as $post ) {
            switch ( $field ) {
            case 'author':
                $key = $post->getAuthorName();
                break;
            case 'status':
                $key = $post->getStatus();
                break;
            case 'year':
                   $key = $post->getPublishedDate()->format('Y');
                break;
            case 'month':
                $key = $post->getPublishedDate()->format('Y-m');
                break;
            case 'category':
                $categories = $post->getCategories();
                $key = ! empty($categories) ? $categories[0] : 'Uncategorized';
                break;
            default:
                throw new InvalidArgumentException("Cannot group by field: {$field}");
            }

            if (! isset($grouped[$key]) ) {
                $grouped[$key] = new self([]);
            }

            $grouped[$key]->add($post);
        }

        return $grouped;
    }

    /**
     * Chain multiple filters using a fluent interface.
     */
    public function where( callable $callback ): self
    {
        $query = clone $this;
        return $callback($query);
    }
}