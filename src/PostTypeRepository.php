<?php
/**
 * Bright Nucleus Collection Post Collection
 *
 * @package   BrightNucleus\Collection
 * @author    Alain Schlesser <alain.schlesser@gmail.com>
 * @license   MIT+
 * @link      http://www.brightnucleus.com/
 * @copyright 2018 Alain Schlesser, Bright Nucleus
 */

namespace BrightNucleus\Collection;

use BrightNucleus\Exception\RuntimeException;
use RRHub\Directories\Services;
use RRHub\Directories\Taxonomy\TaxonomyTerm;
use RRHub\Directories\Taxonomy\TaxonomyTermCollection;
use RRHub\Directories\Type\Status;
use Exception;
use WP_Post;

/**
 * Class PostCollection
 *
 * @package BrightNucleus\Collection
 * @author  Alain Schlesser <alain.schlesser@gmail.com>
 */
abstract class PostTypeRepository extends WPQueryRepository {

	/**
	 * Find a single element by its ID.
	 *
	 * @param int $id ID of the element to find.
	 * @return WP_Post
	 */
	public function find( $id ) {
		// Override only used for settin g the return type.
		return parent::find( $id );
	}

	/**
	 * Find a single element that fits a given set of criteria.
	 *
	 * @param Criteria $criteria Criteria to qualify the desired result set.
	 * @return WP_Post
	 */
	public function findOneBy( Criteria $criteria ) {
		// Override only used for setting the return type.
		return parent::findOneBy( $criteria );
	}

	/**
	 * Get the post type the repository returns.
	 *
	 * @return string
	 */
	protected static function getPostType(): string {
		/** @var PostTypeCollection $collectionClass */
		$collectionClass = static::getCollectionClass();
		return $collectionClass::getPostType();
	}

	/**
	 * Get the default criteria for the repository.
	 *
	 * @return Criteria
	 */
	protected function getDefaultCriteria(): Criteria {
		return Criteria::create()
		               ->where( Criteria::expr()->eq(
			               new Column( Table::POSTS, 'post_type' ),
			               static::getPostType()
		               ) )
		               ->andWhere( Criteria::expr()->eq(
			               new Column( Table::POSTS, 'post_status' ),
			               Status::PUBLISH
		               ) );
	}

	/**
	 * Persist an entity.
	 *
	 * @param Entity $entity Entity to persist.
	 * @return Entity Persisted entity.
	 * @throws Exception If the entity could not be persisted.
	 */
	public function persist( $entity ) {
		$collection = static::getCollectionClass();
		$collection::assertType( $entity );

		if ( current_filter() !== 'save_post' ) {
			$post_data_changes = $this->getPostDataChanges( $entity );
			$this->updatePostData( $entity, $post_data_changes );

			$taxonomy_changes = $this->getTaxonomiesChanges( $entity );
			$this->updateTaxonomies( $entity, $taxonomy_changes );
		}

		$properties_changes = $this->getPropertiesChanges( $entity );
		$this->updateProperties( $entity, $properties_changes );

		return $entity;
	}

	/**
	 * Get the Post data to persist a given entity.
	 *
	 * @param Entity $entity Entity to retrieve the Post data for.
	 * @return array Associative array of post data.
	 */
	protected function getNewPostData( Entity $entity ): array {
		if ( $entity instanceof WP_Post ) {
			return $this->get_properties_stored_in_post(
				$entity,
				(array) $entity
			);
		}

		if ( is_subclass_of( $entity, PostTypeEntity::class ) ) {
			/** @var PostTypeEntity $entity */
			return $this->get_properties_stored_in_post(
				$entity,
				(array) $entity->get_post_object()
			);
		}

		throw new RuntimeException(
			"Could not retrieve internal Post data to persist the entity with ID '{$entity->getId()}'."
		);
	}

	/**
	 * Filter the new post data to persist an entity with.
	 *
	 * @param array $post_data Post data to filter.
	 * @return array Filtered post data.
	 */
	protected function filterNewPostData( array $post_data ): array {
		$post_data['post_type'] = static::getPostType();
		return $post_data;
	}

	/**
	 * Get the current post data of the entity to be persisted.
	 *
	 * @param Entity $entity Entity to be persisted.
	 * @return array Current post data.
	 */
	protected function getCurrentPostData( Entity $entity ): array {
		$id = $entity->getId();
		if ( null === $id || $id < 1 ) {
			return [];
		}

		clean_post_cache( $entity->getId() );
		$result = get_post( $entity->getId() );

		if ( ! $result instanceof WP_Post ) {
			return [];
		}

		return (array) $result;
	}

	/**
	 * Filter the current post data to persist an entity with.
	 *
	 * @param array $post_data Post data to filter.
	 * @return array Filtered post data.
	 */
	protected function filterCurrentPostData( array $post_data ): array {
		return $post_data;
	}

	/**
	 * Get the new set of properties to persist a given entity.
	 *
	 * @param Entity $entity Entity to retrieve the Post metadata for.
	 * @return array Associative array of post metadata.
	 */
	protected function getNewProperties( Entity $entity ): array {
		$properties = [];
		$mappings   = $this->get_properties_to_postmeta_mappings( $entity );

		$extractor = Services::get( 'HydratorFactory' )->create_for( $entity );
		$data      = $extractor->extract();

		foreach ( $mappings as $property => $postmeta_key ) {
			if ( array_key_exists( $property, $data ) ) {
				$properties[ $property ] = $data[ $property ];
			}
		}

		return $properties;
	}

	/**
	 * Filter the new properties to persist an entity with.
	 *
	 * @param array $properties Properties to filter.
	 * @return array Filtered properties.
	 */
	protected function filterNewProperties( array $properties ): array {
		return $properties;
	}

	/**
	 * Get the current properties of the entity to be persisted.
	 *
	 * @param Entity $entity Entity to be persisted.
	 * @return array Current properties.
	 */
	protected function getCurrentProperties( Entity $entity ): array {
		$id = $entity->getId();

		if ( null === $id || $id < 1 ) {
			return [];
		}

		$current_data = get_post_meta( $entity->getId(), '' );

		if ( false === $current_data ) {
			return [];
		}

		$current_data = array_combine(
			array_keys( $current_data ),
			array_column( $current_data, 0 )
		);

		$properties = [];
		$mappings   = $this->get_properties_to_postmeta_mappings( $entity );

		foreach ( $mappings as $property => $postmeta_key ) {
			if ( array_key_exists( $postmeta_key, $current_data ) ) {
				$properties[ $property ] = $current_data[ $postmeta_key ];
			}
		}

		return $properties;
	}

	/**
	 * Filter the current properties to persist an entity with.
	 *
	 * @param array $properties Properties to filter.
	 * @return array Filtered properties.
	 */
	protected function filterCurrentProperties( array $properties ): array {
		return $properties;
	}

	/**
	 * Get the difference array between the current properties and the
	 * properties to be persisted.
	 *
	 * @param array $current_properties Current properties.
	 * @param array $updated_properties Properties to be persisted.
	 * @return array Difference between the two sets of properties.
	 */
	protected function diffProperties( array $current_properties, array $updated_properties ): array {
		$diff_properties = [];

		foreach ( $updated_properties as $key => $value ) {
			if ( ! array_key_exists( $key, $current_properties ) ) {
				$diff_properties[ $key ] = $value;
				continue;
			}

			if ( is_object( $value )
			     && method_exists( $value, 'equals' ) ) {
				if ( ! $value->equals( $current_properties[ $key ] ) ) {
					$diff_properties[ $key ] = $value;
					continue;
				}
			} elseif ( is_object( $current_properties[ $key ] )
			           && method_exists( $current_properties[ $key ],
					'equals' ) ) {
				if ( ! $current_properties[ $key ]->equals( $value ) ) {
					$diff_properties[ $key ] = $value;
					continue;
				}
			} else {
				if ( $current_properties[ $key ] !== $value ) {
					$diff_properties[ $key ] = $value;
					continue;
				}
			}
		}

		return $diff_properties;
	}

	/**
	 * Get the changes that need to be applied to the post data.
	 *
	 * @param Entity $entity Entity to persist.
	 * @return array Array of changes that need to be persisted.
	 */
	protected function getPostDataChanges( $entity ): array {
		$new_post_data          = $this->getNewPostData( $entity );
		$filtered_new_post_data = $this->filterNewPostData( $new_post_data );

		$current_post_data          = $this->getCurrentPostData( $entity );
		$filtered_current_post_data = $this->filterCurrentPostData( $current_post_data );

		$diff_post_data = $this->diffProperties(
			$filtered_current_post_data,
			$filtered_new_post_data
		);

		return $diff_post_data;
	}

	/**
	 * Update the post for a given entity.
	 *
	 * @param Entity $entity    Entity for which to update the post.
	 * @param array  $post_data CHanges to include in the update.
	 */
	protected function updatePostData( Entity $entity, array $post_data ): void {
		if ( ! empty( $post_data ) ) {
			$post_data = array_filter( $post_data, function ( $value ) {
				return ! empty( $value );
			} );

			if ( array_key_exists( 'id', $post_data )
			     && $post_data['id'] < 1 ) {
				unset( $post_data['id'] );
			}

			$mappings = $this->get_properties_to_post_mappings( $entity );

			foreach ( $post_data as $property => $value ) {
				if ( array_key_exists( $property, $mappings ) ) {
					$post_data[ $mappings[ $property ] ] = $value;
					unset( $post_data[ $property ] );
				}
			}

			$result = wp_insert_post( $post_data, true );

			if ( is_wp_error( $result ) ) {
				throw new RuntimeException(
					"Could not persist entity with ID '{$entity->getId()}'. "
					. "Reason: {$result->get_error_message()}"
				);
			}

			if ( $entity instanceof PostTypeEntity ) {
				$post = get_post( $result );
				$entity->set_post_object( $post );
			}
		}
	}

	/**
	 * Get the changes that need to be applied to the properties.
	 *
	 * @param Entity $entity Entity for which to get the properties changes.
	 * @return array Associative array of properties to change.
	 */
	protected function getPropertiesChanges( Entity $entity ): array {
		$new_properties              = $this->getNewProperties( $entity );
		$filtered_new_properties     = $this->filterNewProperties( $new_properties );
		$current_properties          = $this->getCurrentProperties( $entity );
		$filtered_current_properties = $this->filterCurrentProperties( $current_properties );

		$diff_properties = $this->diffProperties(
			$filtered_current_properties,
			$filtered_new_properties
		);

		return $diff_properties;
	}

	/**
	 * Make the required changes to the properties for a given entity.
	 *
	 * @param Entity $entity     Entity to change the properties for.
	 * @param array  $properties Properties to change.
	 */
	protected function updateProperties( Entity $entity, array $properties ): void {
		$mappings = $this->get_properties_to_postmeta_mappings( $entity );

		foreach ( $properties as $meta_key => $meta_value ) {
			if ( array_key_exists( $meta_key, $mappings ) ) {
				$meta_key = $mappings[ $meta_key ];
			}

			if ( null === $meta_value ) {
				delete_post_meta( $entity->getId(), $meta_key );
			}

			update_post_meta( $entity->getId(), $meta_key, $meta_value );
		}
	}

	/**
	 * Get the properties that are stored in a post.
	 *
	 * @param Entity $entity    Entity to get the properties for.
	 * @param array  $post_data Post data to retrieve the values from.
	 * @return array Associative array of data that is meant to be stored in a
	 *                          post.
	 */
	protected function get_properties_stored_in_post( Entity $entity, array $post_data ): array {
		$data     = [];
		$mappings = $this->get_properties_to_post_mappings( $entity );

		foreach ( $mappings as $property => $post_key ) {
			if ( array_key_exists( $post_key, $post_data ) ) {
				$data[ $property ] = $post_data[ $post_key ];
			}

			$extractor = Services::get( 'HydratorFactory' )
			                     ->create_for( $entity );
			try {
				$data[ $property ] = $extractor->extract_property( $property );
			} catch ( Throwable $exception ) {
				// Do nothing.
			}
		}

		return $data;
	}

	/**
	 * Get the changes that need to be applied to the taxonomies.
	 *
	 * @param Entity $entity Entity to persist.
	 * @return array Array of changes that need to be persisted.
	 */
	protected function getTaxonomiesChanges( $entity ): array {
		$new_taxonomy_terms = $this->getNewTaxonomyTerms( $entity );
		$filtered_new_taxonomy_terms = $this->filterNewTaxonomyTerms( $new_taxonomy_terms );

		$current_taxonomy_terms = $this->getCurrentTaxonomyTerms( $entity );
		$filtered_current_taxonomy_terms = $this->filterCurrentTaxonomyTerms( $current_taxonomy_terms );

		$diff_taxonomy_terms = $this->diffProperties(
			$filtered_current_taxonomy_terms,
			$filtered_new_taxonomy_terms
		);

		return $diff_taxonomy_terms;
	}

	/**
	 * Get the new set of taxonomy terms to persist a given entity.
	 *
	 * @param Entity $entity Entity to retrieve the taxonomy terms for.
	 * @return array Associative array of post metadata.
	 */
	protected function getNewTaxonomyTerms( Entity $entity ): array {
		$taxonomies = [];
		$mappings   = $this->get_properties_to_taxonomies_mappings( $entity );

		foreach ( $mappings as $property => $taxonomy_id ) {
			$get_method                 = "get_{$property}";
			$taxonomies[ $taxonomy_id ] = $entity->$get_method();
		}

		return $taxonomies;
	}

	/**
	 * Filter the new taxonomy terms to persist an entity with.
	 *
	 * @param array $taxonomy_terms Taxonomy terms to filter.
	 * @return array Filtered taxonomy terms.
	 */
	protected function filterNewTaxonomyTerms( array $taxonomy_terms ): array {
		return $taxonomy_terms;
	}

	/**
	 * Get the current taxonomy terms of the entity to be persisted.
	 *
	 * @param Entity $entity Entity to be persisted.
	 * @return array Current taxonomy terms.
	 */
	protected function getCurrentTaxonomyTerms( Entity $entity ): array {
		$id = $entity->getId();

		if ( null === $id || $id < 1 ) {
			return [];
		}

		$mappings = $this->get_properties_to_taxonomies_mappings( $entity );

		$current_data = wp_get_object_terms(
			$entity->getId(),
			array_values( $mappings )
		);

		if ( false === $current_data ) {
			return [];
		}

		$taxonomy_terms = [];

		foreach ( $mappings as $property => $taxonomy_id ) {
			if ( array_key_exists( $taxonomy_id, $current_data ) ) {
				$taxonomy_terms[ $taxonomy_id ] = $current_data[ $taxonomy_id ];
			}
		}

		return $taxonomy_terms;
	}

	/**
	 * Make the required changes to the taxonomies for a given entity.
	 *
	 * @param Entity $entity     Entity to change the taxonomies for.
	 * @param array  $taxonomies Taxonomies to change.
	 */
	protected function updateTaxonomies( Entity $entity, array $taxonomies ): void {
		foreach ( $taxonomies as $taxonomy_id => $taxonomy_terms ) {
			if ( $taxonomy_terms instanceof TaxonomyTermCollection ) {
				$taxonomy_terms = $taxonomy_terms->toArray();
			}

			if ( is_array( $taxonomy_terms ) ) {
				$taxonomy_term_ids = array_map( function ( TaxonomyTerm $term ) {
					return $term->get_id();
				}, (array) $taxonomy_terms );
			}

			if ( $taxonomy_terms instanceof TaxonomyTerm ) {
				$taxonomy_term_ids = [ $taxonomy_terms->get_id() ];
			}

			$result = wp_set_object_terms(
				$entity->getId(),
				$taxonomy_term_ids,
				$taxonomy_id,
				false
			);
		}
	}

	/**
	 * Filter the current taxonomy terms to persist an entity with.
	 *
	 * @param array $taxonomy_terms Taxonomy terms to filter.
	 * @return array Filtered taxonomy terms.
	 */
	protected function filterCurrentTaxonomyTerms( array $taxonomy_terms ): array {
		return $taxonomy_terms;
	}

	/**
	 * Get the properties to post data key mappings.
	 *
	 * @param Entity $entity Entity to get the mappings for.
	 * @return array Associative array of $property => $post_key mappings.
	 */
	abstract protected function get_properties_to_post_mappings( Entity $entity ): array;

	/**
	 * Get the properties to post metadata key mappings.
	 *
	 * @param Entity $entity Entity to get the mappings for.
	 * @return array Associative array of $property => $postmeta_key mappings.
	 */
	abstract protected function get_properties_to_postmeta_mappings( Entity $entity ): array;

	/**
	 * Get the properties to taxonomies key mappings.
	 *
	 * @param Entity $entity Entity to get the mappings for.
	 * @return array Associative array of $property => $taxonomy_id mappings.
	 */
	abstract protected function get_properties_to_taxonomies_mappings( Entity $entity ): array;
}
