<?php declare( strict_types=1 );

namespace BrightNucleus\Collection\Tests\Fixtures;

use BrightNucleus\Collection\Entity;
use BrightNucleus\Collection\HasProperties;
use BrightNucleus\Collection\PropertyCollection;
use BrightNucleus\Collection\PostMetaPropertyCollection;
use DateTime;
use WP_Post;

/**
 * BlogPost entity fixture that simulates a real-world custom entity
 * wrapping WordPress posts with additional business logic.
 */
final class BlogPost implements Entity, HasProperties
{

    private WP_Post $post;
    private ?PropertyCollection $properties = null;
    private ?DateTime $publishedDate = null;
    private array $tags = [];
    private array $categories = [];

    public function __construct( WP_Post $post )
    {
        $this->post = $post;
    }

    /**
     * Magic getter to expose WP_Post properties for Doctrine Criteria filtering.
     */
    public function __get( string $name )
    {
        // First check if it's a property of the WP_Post object
        if (property_exists($this->post, $name) ) {
            return $this->post->$name;
        }
        
        // Handle special meta property access
        if ($name === 'meta' ) {
            // Return a proxy object that handles meta property access
            return new class( $this ) {
                private $post;
                
                public function __construct( $post )
                {
                    $this->post = $post;
                }
                
                public function __get( $key )
                {
                    return $this->post->getMetaValue($key);
                }
                
                public function __isset( $key )
                {
                    $value = $this->post->getMetaValue($key);
                    return $value !== null && $value !== '';
                }
                
                // Allow direct property access for Doctrine
                public function __set( $key, $value )
                {
                    // Read-only
                }
            };
        }
        
        if (strpos($name, 'meta.') === 0 ) {
            $key = substr($name, 5);
            return $this->getMetaValue($key);
        }
        
        // Handle computed properties
        switch ( $name ) {
        case 'ID':
            return $this->post->ID;
        case 'post_status':
            return $this->post->post_status;
        case 'post_author':
            return $this->post->post_author;
        case 'post_date':
            return $this->post->post_date;
        case 'post_title':
            return $this->post->post_title;
        case 'comment_count':
            return $this->post->comment_count;
        case 'category_name':
            $categories = $this->getCategories();
            return ! empty($categories) ? implode(',', $categories) : '';
        case 'tag':
            $tags = $this->getTags();
            return ! empty($tags) ? implode(',', $tags) : '';
        case 's':
            // For search, return concatenated searchable content
            return $this->post->post_title . ' ' . $this->post->post_content;
        }
        
        trigger_error('Undefined property: ' . __CLASS__ . '::$' . $name, E_USER_NOTICE);
        return null;
    }

    /**
     * Magic isset to check if WP_Post properties exist.
     */
    public function __isset( string $name ): bool
    {
        return property_exists($this->post, $name) || 
               strpos($name, 'meta.') === 0 ||
               in_array($name, [ 'category_name', 'tag', 's' ], true);
    }

    public function getId()
    {
        return $this->post->ID;
    }

    public function getTitle(): string
    {
        return $this->post->post_title;
    }

    public function getContent(): string
    {
        return $this->post->post_content;
    }

    public function getExcerpt(): string
    {
        return $this->post->post_excerpt ?: wp_trim_words($this->post->post_content, 55);
    }

    public function getSlug(): string
    {
        return $this->post->post_name;
    }

    public function getStatus(): string
    {
        return $this->post->post_status;
    }

    public function isPublished(): bool
    {
        return $this->post->post_status === 'publish';
    }

    public function isDraft(): bool
    {
        return $this->post->post_status === 'draft';
    }

    public function getAuthorId(): int
    {
        return (int) $this->post->post_author;
    }

    public function getAuthorName(): string
    {
        $author = get_userdata($this->post->post_author);
        return $author ? $author->display_name : 'Unknown';
    }

    public function getPublishedDate(): DateTime
    {
        if ($this->publishedDate === null ) {
            $this->publishedDate = new DateTime($this->post->post_date);
        }
        return $this->publishedDate;
    }

    public function getModifiedDate(): DateTime
    {
        return new DateTime($this->post->post_modified);
    }

    public function getTags(): array
    {
        if (empty($this->tags) ) {
            $terms = wp_get_post_tags($this->post->ID);
            $this->tags = array_map(
                function ( $term ) {
                    return $term->name;
                }, $terms 
            );
        }
        return $this->tags;
    }

    public function hasTag( string $tag ): bool
    {
        return in_array($tag, $this->getTags(), true);
    }

    public function getCategories(): array
    {
        if (empty($this->categories) ) {
            $terms = wp_get_post_categories($this->post->ID, [ 'fields' => 'all' ]);
            $this->categories = array_map(
                function ( $term ) {
                    return $term->name;
                }, $terms 
            );
        }
        return $this->categories;
    }

    public function inCategory( string $category ): bool
    {
        return in_array($category, $this->getCategories(), true);
    }

    public function getCommentCount(): int
    {
        return (int) $this->post->comment_count;
    }

    public function hasComments(): bool
    {
        return $this->getCommentCount() > 0;
    }

    public function getPermalink(): string
    {
        return get_permalink($this->post->ID);
    }

    public function getFeaturedImageUrl( string $size = 'full' ): ?string
    {
        $thumbnail_id = get_post_thumbnail_id($this->post->ID);
        if (! $thumbnail_id ) {
            return null;
        }
        
        $image = wp_get_attachment_image_src($thumbnail_id, $size);
        return $image ? $image[0] : null;
    }

    public function getMetaValue( string $key, $default = null )
    {
        $value = get_post_meta($this->post->ID, $key, true);
        // For thumbnail_id, we should return empty string not null
        // This ensures != '' comparisons work correctly
        if ($value === '' && $default === null) {
            // Special case for _thumbnail_id - return empty string
            if ($key === '_thumbnail_id') {
                return '';
            }
            return $default;
        }
        if ($value === '') {
            return $default;
        }
        // Return numeric values as integers/floats for proper comparison
        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? (float)$value : (int)$value;
        }
        return $value;
    }

    public function setMetaValue( string $key, $value ): bool
    {
        return (bool) update_post_meta($this->post->ID, $key, $value);
    }

    public function getProperties(): PropertyCollection
    {
        if ($this->properties === null ) {
            $this->properties = new PostMetaPropertyCollection([ 'post_id' => $this->post->ID ]);
        }
        return $this->properties;
    }

    public function getWordCount(): int
    {
        return str_word_count(strip_tags($this->post->post_content));
    }

    public function getReadingTime(): int
    {
        // Average reading speed is 200-250 words per minute
        $words_per_minute = 225;
        return max(1, (int) ceil($this->getWordCount() / $words_per_minute));
    }

    public function isSticky(): bool
    {
        return is_sticky($this->post->ID);
    }

    public function getRelatedPosts( int $limit = 5 ): array
    {
        $tags = wp_get_post_tags($this->post->ID, [ 'fields' => 'ids' ]);
        
        if (empty($tags) ) {
            return [];
        }

        $args = [
        'tag__in' => $tags,
        'post__not_in' => [ $this->post->ID ],
        'posts_per_page' => $limit,
        'post_status' => 'publish',
        'orderby' => 'rand'
        ];

        $query = new \WP_Query($args);
        $related = [];
        
        foreach ( $query->posts as $post ) {
            $related[] = new self($post);
        }

        return $related;
    }

    public function toArray(): array
    {
        return [
        'id' => $this->getId(),
        'title' => $this->getTitle(),
        'content' => $this->getContent(),
        'excerpt' => $this->getExcerpt(),
        'slug' => $this->getSlug(),
        'status' => $this->getStatus(),
        'author_id' => $this->getAuthorId(),
        'author_name' => $this->getAuthorName(),
        'published_date' => $this->getPublishedDate()->format('Y-m-d H:i:s'),
        'modified_date' => $this->getModifiedDate()->format('Y-m-d H:i:s'),
        'tags' => $this->getTags(),
        'categories' => $this->getCategories(),
        'comment_count' => $this->getCommentCount(),
        'permalink' => $this->getPermalink(),
        'featured_image' => $this->getFeaturedImageUrl(),
        'word_count' => $this->getWordCount(),
        'reading_time' => $this->getReadingTime(),
        'is_sticky' => $this->isSticky(),
        ];
    }

    public function getWrappedPost(): WP_Post
    {
        return $this->post;
    }
}