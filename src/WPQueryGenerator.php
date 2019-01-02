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

use Doctrine\Common\Collections\Expr\ExpressionVisitor;

abstract class WPQueryGenerator implements QueryGenerator {

	/** @var Criteria */
	protected $criteria;

	/**
	 * Instantiate a WPQueryGenerator object.
	 *
	 * @param Criteria $criteria Criteria to use for the query generation.
	 */
	public function __construct( Criteria $criteria ) {
		$this->criteria = $criteria;
	}

	/**
	 * Get the query generated from the individual clauses.
	 *
	 * @return string
	 */
	public function getQuery(): string {
		return implode( ' ', array_filter( [
			$this->getSelectClause(),
			$this->getFromClause(),
			$this->getWhereClause(),
			$this->getOrderByClause(),
			$this->getLimitClause(),
		] ) );
	}

	/**
	 * Get SELECT clause.
	 *
	 * @return string SELECT clause.
	 */
	public function getSelectClause(): string {
		$fields = [ '*' ];
		return 'SELECT ' . implode( ',', $fields );
	}

	/**
	 * Get the FROM clause.
	 *
	 * @return string FROM clause.
	 */
	public function getFromClause(): string {
		global $wpdb;
		$from = $wpdb->get_blog_prefix() . $this->getTableName();
		return "FROM {$from}";
	}

	/**
	 * Get the WHERE clause.
	 *
	 * Can be an empty string.
	 *
	 * @return string WHERE clause.
	 */
	public function getWhereClause(): string {
		if ( $this->criteria instanceof NullCriteria ) {
			return '';
		}

		$expression = $this->criteria->getWhereExpression();

		if ( null === $expression ) {
			return '';
		}

		$visitor = $this->getVisitor();
		return 'WHERE ' . $visitor->dispatch( $expression );
	}

	/**
	 * Get the ORDER BY clause.
	 *
	 * Can be an empty string.
	 *
	 * @return string ORDER BY clause.
	 */
	public function getOrderByClause(): string {
		$orderings = $this->criteria->getOrderings();

		if ( empty( $orderings ) ) {
			return '';
		}

		$strings = [];
		foreach ( $orderings as $field => $order ) {
			$strings [] = "{$field} {$order}";
		}

		return 'ORDER BY ' . implode( ', ', $strings );
	}

	/**
	 * Get the LIMIT clause.
	 *
	 * Can be an empty string.
	 *
	 * @return string LIMIT clause.
	 */
	public function getLimitClause(): string {
		$offset = $this->criteria->getFirstResult();
		$limit  = $this->criteria->getMaxResults();

		if ( $limit === PHP_INT_MAX || $limit === null ) {
			return '';
		}

		if ( $offset === 0 || $offset === null ) {
			return "LIMIT {$limit}";
		}

		return "LIMIT {$offset}, {$limit}";
	}

	/**
	 * Get the expression visitor instance to use.
	 *
	 * @return ExpressionVisitor
	 */
	protected function getVisitor(): ExpressionVisitor {
		return new WPQuerySQLExpressionVisitor( $this->getTableName() );
	}

	/**
	 * Get the name of the table to query against.
	 *
	 * @return string Name of the table to query against.
	 */
	abstract protected function getTableName(): string;
}
