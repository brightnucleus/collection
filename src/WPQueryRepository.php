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

use RuntimeException;

/**
 * Class PostCollection
 *
 * @package BrightNucleus\Collection
 * @author  Alain Schlesser <alain.schlesser@gmail.com>
 */
abstract class WPQueryRepository implements Repository {

	/**
	 * Find all elements the repository contains.
	 *
	 * @return WPQueryCollection
	 */
	public function findAll() {
		return $this->findBy( new NullCriteria );
	}

	/**
	 * Find a single element by its ID.
	 *
	 * @param int $id ID of the element to find.
	 * @return mixed
	 */
	public function find( $id ) {
		$class    = static::getCollectionClass();
		$criteria = ( clone $this->getDefaultCriteria() )
			->where( Criteria::expr()->eq( 'ID', $id ) )
			->setFirstResult( 0 )
			->setMaxResults( 1 );

		/** @var Collection $collection */
		$collection = new $class( $criteria );
		if ( empty( $collection ) ) {
			throw new RuntimeException( "Element with ID {$id} was not found." );
		}

		return $collection->first();
	}

	/**
	 * Find a collection of elements that fit a given set of criteria.
	 *
	 * @param Criteria $criteria Criteria to qualify the desired result set.
	 * @return WPQueryCollection
	 */
	public function findBy( Criteria $criteria ) {
		$class    = static::getCollectionClass();
		$criteria = ( clone $this->getFallbackCriteria() )
			->merge( clone $this->getDefaultCriteria() )
			->setMaxResults( PHP_INT_MAX )
			->merge( $criteria );

		return new $class( $criteria );
	}

	/**
	 * Find a single element that fits a given set of criteria.
	 *
	 * @param Criteria $criteria Criteria to qualify the desired result set.
	 * @return mixed
	 */
	public function findOneBy( Criteria $criteria ) {
		$criteria = clone $criteria;
		$criteria = ( clone $this->getDefaultCriteria() )
			->setMaxResults( 1 )
			->merge( $criteria );

		return $this->findBy( $criteria )->first();
	}

	/**
	 * Get the class that represents the collection the repository returns.
	 *
	 * @return string
	 */
	abstract protected static function getCollectionClass(): string;

	/**
	 * Get the default criteria for the repository.
	 *
	 * @return Criteria
	 */
	abstract protected function getDefaultCriteria(): Criteria;

	/**
	 * Get the fallback criteria to use.
	 *
	 * @return Criteria
	 */
	protected function getFallbackCriteria(): Criteria {
		return Criteria::create()
		               ->setFirstResult( 0 )
		               ->setMaxResults( 10 );
	}
}
