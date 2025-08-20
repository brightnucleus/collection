<?php declare( strict_types=1 );

namespace BrightNucleus\Collection\Tests\Fixtures;

use BrightNucleus\Collection\AbstractPropertyCollection;
use BrightNucleus\Collection\PostMetaQueryGenerator;
use BrightNucleus\Collection\Property;
use BrightNucleus\Collection\QueryGenerator;
use BrightNucleus\Collection\Criteria;
use BrightNucleus\Exception\InvalidArgumentException;

/**
 * CustomMetaCollection fixture that demonstrates extending AbstractPropertyCollection
 * for custom metadata handling.
 */
final class CustomMetaCollection extends AbstractPropertyCollection
{

    private string $metaPrefix = '_custom_';
    private array $allowedKeys = [];
    private bool $autoSave = false;
    private array $validators = [];
    private array $transformers = [];

    /**
     * Set a prefix for all meta keys.
     */
    public function setMetaPrefix( string $prefix ): self
    {
        $this->metaPrefix = $prefix;
        return $this;
    }

    /**
     * Set allowed meta keys.
     */
    public function setAllowedKeys( array $keys ): self
    {
        $this->allowedKeys = $keys;
        return $this;
    }

    /**
     * Enable auto-save on changes.
     */
    public function enableAutoSave( bool $enable = true ): self
    {
        $this->autoSave = $enable;
        return $this;
    }

    /**
     * Add a validator for a specific key.
     */
    public function addValidator( string $key, callable $validator ): self
    {
        $this->validators[$key] = $validator;
        return $this;
    }

    /**
     * Add a transformer for a specific key.
     */
    public function addTransformer( string $key, callable $transformer ): self
    {
        $this->transformers[$key] = $transformer;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function add( $element ): bool
    {
        $element = $this->normalizeEntity($element);
        
        if (! $this->isValidKey($element->getKey()) ) {
            throw new InvalidArgumentException(
                "Key '{$element->getKey()}' is not allowed in this collection."
            );
        }

        if (! $this->validateValue($element->getKey(), $element->getValue()) ) {
            throw new InvalidArgumentException(
                "Value for key '{$element->getKey()}' failed validation."
            );
        }

        $transformedValue = $this->transformValue($element->getKey(), $element->getValue());
        $element = new Property(
            [
            'key' => $element->getKey(),
            'value' => $transformedValue
            ] 
        );

        $result = parent::add($element);

        if ($result && $this->autoSave ) {
            $this->save($element->getKey());
        }

        return $result;
    }

    /**
     * Set a meta value with validation and transformation.
     */
    public function setMeta( string $key, $value ): bool
    {
        $fullKey = $this->metaPrefix . $key;
        
        $property = new Property(
            [
            'key' => $fullKey,
            'value' => $value
            ] 
        );

        return $this->add($property);
    }

    /**
     * Get a meta value.
     */
    public function getMeta( string $key, $default = null )
    {
        $fullKey = $this->metaPrefix . $key;
        
        try {
            return $this->get($fullKey);
        } catch ( \Exception $e ) {
            return $default;
        }
    }

    /**
     * Check if a meta key exists.
     */
    public function hasMeta( string $key ): bool
    {
        $fullKey = $this->metaPrefix . $key;
        return $this->containsKey($fullKey);
    }

    /**
     * Remove a meta value.
     */
    public function removeMeta( string $key ): bool
    {
        $fullKey = $this->metaPrefix . $key;
        
        if (! $this->containsKey($fullKey) ) {
            return false;
        }

        $result = $this->remove($fullKey) !== null;

        if ($result && $this->autoSave ) {
            delete_post_meta($this->postID, $fullKey);
        }

        return $result;
    }

    /**
     * Get all meta values as an associative array.
     */
    public function getAllMeta(): array
    {
        $this->hydrate();
        $meta = [];

        foreach ( $this->collection as $key => $property ) {
            if (strpos($key, $this->metaPrefix) === 0 ) {
                $shortKey = substr($key, strlen($this->metaPrefix));
                $meta[$shortKey] = $property->getValue();
            }
        }

        return $meta;
    }

    /**
     * Set multiple meta values at once.
     */
    public function setMultipleMeta( array $values ): bool
    {
        $success = true;

        foreach ( $values as $key => $value ) {
            if (! $this->setMeta($key, $value) ) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Filter meta by value type.
     */
    public function filterByType( string $type ): self
    {
        $this->hydrate();
        $filtered = [];

        foreach ( $this->collection as $key => $property ) {
            $value = $property->getValue();
            
            $matches = false;
            switch ( $type ) {
            case 'string':
                $matches = is_string($value);
                break;
            case 'int':
            case 'integer':
                $matches = is_int($value);
                break;
            case 'float':
            case 'double':
                   $matches = is_float($value);
                break;
            case 'bool':
            case 'boolean':
                $matches = is_bool($value);
                break;
            case 'array':
                $matches = is_array($value);
                break;
            case 'object':
                $matches = is_object($value);
                break;
            case 'null':
                $matches = is_null($value);
                break;
            }

            if ($matches ) {
                $filtered[] = $property;
            }
        }

        return new self($this->postID, $filtered);
    }

    /**
     * Search meta values by pattern.
     */
    public function search( string $pattern ): self
    {
        $expr = Criteria::expr();
        $criteria = Criteria::create()
            ->where($expr->contains('meta_value', $pattern));

        return $this->matching($criteria);
    }

    /**
     * Get meta values that match a regex pattern.
     */
    public function matchPattern( string $pattern ): self
    {
        $this->hydrate();
        $matched = [];

        foreach ( $this->collection as $property ) {
            $value = $property->getValue();
            
            if (is_string($value) && preg_match($pattern, $value) ) {
                $matched[] = $property;
            }
        }

        return new self($this->postID, $matched);
    }

    /**
     * Sort meta by value.
     */
    public function sortByValue( bool $ascending = true ): self
    {
        $this->hydrate();
        $items = $this->collection->toArray();

        usort(
            $items, function ( $a, $b ) use ( $ascending ) {
                $aVal = $a->getValue();
                $bVal = $b->getValue();

                if ($aVal == $bVal ) {
                    return 0;
                }

                $result = $aVal < $bVal ? -1 : 1;
                return $ascending ? $result : -$result;
            } 
        );

        return new self($this->postID, $items);
    }

    /**
     * Get meta statistics.
     */
    public function getStatistics(): array
    {
        $this->hydrate();
        
        $types = [];
        $sizes = [];
        $keys = [];

        foreach ( $this->collection as $property ) {
            $value = $property->getValue();
            $type = gettype($value);
            
            $types[$type] = ( $types[$type] ?? 0 ) + 1;
            $keys[] = $property->getKey();

            if (is_string($value) ) {
                $sizes[] = strlen($value);
            } elseif (is_array($value) ) {
                $sizes[] = count($value);
            }
        }

        return [
        'total_count' => $this->count(),
        'unique_keys' => count(array_unique($keys)),
        'type_distribution' => $types,
        'average_size' => ! empty($sizes) ? array_sum($sizes) / count($sizes) : 0,
        'min_size' => ! empty($sizes) ? min($sizes) : 0,
        'max_size' => ! empty($sizes) ? max($sizes) : 0,
        ];
    }

    /**
     * Export meta as JSON.
     */
    public function toJson( int $options = 0 ): string
    {
        return json_encode($this->getAllMeta(), $options);
    }

    /**
     * Import meta from JSON.
     */
    public function fromJson( string $json ): bool
    {
        $data = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE ) {
            throw new InvalidArgumentException('Invalid JSON provided.');
        }

        return $this->setMultipleMeta($data);
    }

    /**
     * Save a specific key to the database.
     */
    private function save( string $key ): bool
    {
        $property = $this->getProperty($key);
        return update_post_meta($this->postID, $key, $property->getValue());
    }

    /**
     * Check if a key is valid.
     */
    private function isValidKey( string $key ): bool
    {
        if (empty($this->allowedKeys) ) {
            return true;
        }

        // Check if key without prefix is in allowed list
        $shortKey = str_replace($this->metaPrefix, '', $key);
        return in_array($shortKey, $this->allowedKeys, true);
    }

    /**
     * Validate a value for a specific key.
     */
    private function validateValue( string $key, $value ): bool
    {
        if (! isset($this->validators[$key]) ) {
            return true;
        }

        return $this->validators[$key]($value);
    }

    /**
     * Transform a value for a specific key.
     */
    private function transformValue( string $key, $value )
    {
        if (! isset($this->transformers[$key]) ) {
            return $value;
        }

        return $this->transformers[$key]($value);
    }

    /**
     * {@inheritDoc}
     */
    protected function getQueryGenerator(): QueryGenerator
    {
        return new PostMetaQueryGenerator($this->criteria);
    }
}