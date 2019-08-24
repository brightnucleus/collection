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

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Expression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Value;

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
		$fields = [ "{$this->getTableName()}.*" ];
		return 'SELECT DISTINCT ' . implode( ',', $fields );
	}

	/**
	 * Get the FROM clause.
	 *
	 * @return string FROM clause.
	 */
	public function getFromClause(): string {
		global $wpdb;

		$from = [];

		$from[] = "{$wpdb->get_blog_prefix()}{$this->getTableName()} {$this->getTableName()}";

		if ( $this->uses_postmeta() ) {
			$from[] = "{$wpdb->get_blog_prefix()}postmeta postmeta";
		}

		if ( $this->uses_taxonomies() ) {
			$from[] = "{$wpdb->get_blog_prefix()}term_relationships term_relationships";
			$from[] = "{$wpdb->get_blog_prefix()}term_taxonomy term_taxonomy";
			$from[] = "{$wpdb->get_blog_prefix()}terms terms";
		}

		return 'FROM ' . implode( ', ', array_unique( $from ) );
	}

	/**
	 * Get the WHERE clause.
	 *
	 * Can be an empty string.
	 *
	 * @return string WHERE clause.
	 */
	public function getWhereClause(): string {
		$criteria = clone $this->criteria;

		if ( $this->uses_postmeta() && $this->getTableName() !== 'postmeta' ) {
			$criteria->andWhere(
				Criteria::expr()->eq(
					new Column( Table::POSTMETA, 'post_id' ),
					new Column( Table::POSTS, 'ID' )
				)
			);
		}

		if ( $criteria instanceof NullCriteria ) {
			return '';
		}

		$expression = $criteria->getWhereExpression();

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
	 * Check the criteria to see whether the query uses postmeta.
	 *
	 * @return bool Whether the query uses postmeta.
	 */
	protected function uses_postmeta(): bool {
		return $this->criteria_contains_table( Table::POSTMETA );
	}

	/**
	 * Check the criteria to see whether the query uses taxonomies.
	 *
	 * @return bool Whether the query uses taxonomies.
	 */
	protected function uses_taxonomies(): bool {
		return $this->criteria_contains_table( Table::TERM_TAXONOMY );
	}

	protected function criteria_contains_table( string $table_name ) {
		foreach ( $this->criteria->getOrderings() as $column => $direction ) {
			if ( 0 === stripos( $column, "{$table_name}." ) ) {
				return true;
			}
		}

		return $this->expression_contains_table(
			$this->criteria->getWhereExpression(),
			$table_name
		);
	}

	protected function expression_contains_table(
		Expression $expression,
		string $table_name
	) {
		if ( $expression instanceof Comparison ) {
			foreach (
				[
					$expression->getField(),
					$expression->getValue()->getValue(),
				] as $column
			) {
				if ( is_string( $column )
				     && 0 === stripos( $column, "{$table_name}." ) ) {
					return true;
				}

				if ( $column instanceof Column && $table_name === $column->getTableName() ) {
					return true;
				}
			}
		} else if ( $expression instanceof Value ) {
			$value = $expression->getValue();
			if ( is_string( $value )
			     && 0 === stripos( $value, "{$table_name}." ) ) {
				return true;
			}

			if ( $value instanceof Column
			     && $table_name === $value->getTableName() ) {
				return true;
			}
		} else if ( $expression instanceof CompositeExpression ) {
			foreach ( $expression->getExpressionList() as $nested_expression ) {
				if ( $this->expression_contains_table( $nested_expression,
					$table_name ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Get the name of the main table to query against.
	 *
	 * @return string Name of the table to query against.
	 */
	abstract protected function getTableName(): string;
}
