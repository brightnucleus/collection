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

use BrightNucleus\Exception\InvalidArgumentException;
use BrightNucleus\Exception\RuntimeException;
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
			               'post_type',
			               static::getPostType()
		               ) )
		               ->andWhere( Criteria::expr()->eq(
			               'post_status',
			               'publish'

		               ) );
	}

	/**
	 * Persist an entity.
	 *
	 * @param WP_Post $entity Entity to persist.
	 * @return WP_Post Persisted entity.
	 * @throws Exception If the entity could not be persisted.
	 */
	public function persist( $entity ) {
		$collection = static::getCollectionClass();
		$collection::assertType( $entity );

		$result = wp_update_post( $this->get_post_data( $entity ), true );

		if ( is_wp_error( $result ) ) {
			throw new RuntimeException(
				"Could not persist entity with ID '{$entity->getId()}'. "
				. "Reason: {$result->get_error_message()}"
			);
		}

		$postmeta_data = $this->get_postmeta_data( $entity );

		foreach ( $postmeta_data as $meta_key => $meta_value ) {
			update_post_meta( $result, $meta_key, $meta_value, true );
		}

		return $this->find( $result );
	}

	/**
	 * Get the Post data to persist a given entity.
	 *
	 * @param Entity $entity Entity to retrieve the Post data for.
	 * @return array Associative array of post data.
	 */
	protected function get_post_data( $entity ) {
		if ( $entity instanceof WP_Post ) {
			return (array) $entity;
		}

		if ( is_subclass_of( $collection, PostTypeEntity::class ) ) {
			/** @var PostTypeEntity $entity */
			return (array) $entity->get_post_object();
		}

		throw new RuntimeException(
			"Could not retrieve internal Post data to persist the entity with ID '{$entity->getId()}."
		);
	}

	/**
	 * Get the Post metadata to persist a given entity.
	 *
	 * @param Entity $entity Entity to retrieve the Post metadata for.
	 * @return array Associative array of post metadata.
	 */
	protected function get_postmeta_data( $entity ) {
		return [];
	}
}
