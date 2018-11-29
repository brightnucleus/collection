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

use Doctrine\Common\Collections\Criteria as DoctrineCommonCriteria;
use Doctrine\Common\Collections\Expr\Expression;
use RuntimeException;

class Criteria extends DoctrineCommonCriteria {

	/**
	 * Creates an instance of the class.
	 *
	 * @return Criteria
	 */
	public static function create(): Criteria {
		return new static;
	}

	/**
	 * Sets the where expression to evaluate when this Criteria is searched for.
	 *
	 * @param Expression $expression
	 *
	 * @return Criteria
	 */
	public function where( Expression $expression ): Criteria {
		return parent::where( $expression );
	}

	/**
	 * Appends the where expression to evaluate when this Criteria is searched
	 * for using an AND with previous expression.
	 *
	 * @param Expression $expression
	 *
	 * @return Criteria
	 */
	public function andWhere( Expression $expression ): Criteria {
		return parent::andWhere( $expression );
	}

	/**
	 * Appends the where expression to evaluate when this Criteria is searched
	 * for using an OR with previous expression.
	 *
	 * @param Expression $expression
	 *
	 * @return Criteria
	 */
	public function orWhere( Expression $expression ): Criteria {
		return parent::orWhere( $expression );
	}

	/**
	 * Sets the ordering of the result of this Criteria.
	 *
	 * Keys are field and values are the order, being either ASC or DESC.
	 *
	 * @see Criteria::ASC
	 * @see Criteria::DESC
	 *
	 * @param string[] $orderings
	 *
	 * @return Criteria
	 */
	public function orderBy( array $orderings ): Criteria {
		return parent::orderBy( $orderings );
	}

	/**
	 * Set the number of first result that this Criteria should return.
	 *
	 * @param int|null $firstResult The value to set.
	 *
	 * @return Criteria
	 */
	public function setFirstResult( $firstResult ): Criteria {
		return parent::setFirstResult( $firstResult );
	}

	/**
	 * Sets maxResults.
	 *
	 * @param int|null $maxResults The value to set.
	 *
	 * @return Criteria
	 */
	public function setMaxResults( $maxResults ): Criteria {
		return parent::setMaxResults( $maxResults );
	}

	/**
	 * Merge two separate sets of criteria to create a composite construct.
	 *
	 * @param Criteria $that Second set of criteria to merge.
	 * @return Criteria Resulting set of criteria.
	 */
	public function merge( Criteria $that ): Criteria {
		// Nothing to merge, just return non-null criteria.
		// If both are NullCriteria, a NullCriteria will be returned.
		if ( $this instanceof NullCriteria ) {
			return clone $that;
		}
		if ( $that instanceof NullCriteria ) {
			return clone $this;
		}

		// Make sure we don't change criteria in other places of the code.
		$criteria = clone $this;

		// We are defaulting to AND relationships between criteria expressions.
		$thatExpression = $that->getWhereExpression();
		if ( $thatExpression instanceof Expression ) {
			$criteria = $criteria->andWhere( $thatExpression );
		}

		// Orderings are arrays, so we can just merge them.
		// TODO: This might need additional validation.
		$criteria->orderBy( array_filter(
			array_merge( $this->getOrderings(), $that->getOrderings() )
		) );

		// Offsets throw an exception if both are set to conflicting values.
		$thisFirst = $this->getFirstResult();
		$thatFirst = $that->getFirstResult();
		if ( $thisFirst !== $thatFirst ) {
			if ( $thisFirst !== null && $thatFirst !== null ) {
				$criteria->setFirstResult( $thatFirst );
			} else {
				$criteria->setFirstResult( $thisFirst ?? $thatFirst );
			}
		} else {
			$criteria->setFirstResult( $thisFirst );
		}

		// Limits throw an exception if both are set to conflicting values.
		$thisMax = $this->getMaxResults();
		$thatMax = $that->getMaxResults();
		if ( $thisMax !== $thatMax ) {
			if ( $thisMax !== null && $thatMax !== null ) {
				$criteria->setMaxResults( $thatMax );
			} else {
				$criteria->setMaxResults( $thisMax ?? $thatMax );
			}
		} else {
			$criteria->setMaxResults( $thisMax );
		}

		return $criteria;
	}
}
