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
}
