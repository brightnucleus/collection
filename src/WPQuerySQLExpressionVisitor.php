<?php
/**
 * Bright Nucleus WP_Query Collection
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
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Value;
use RuntimeException;

/**
 * Class AbstractWPQueryCollection
 *
 * @package BrightNucleus\Collection
 * @author  Alain Schlesser <alain.schlesser@gmail.com>
 */
class WPQuerySQLExpressionVisitor extends ExpressionVisitor {

	/** @var string */
	protected $tableName;

	/**
	 * @var array
	 */
	static protected $comparisonMap = [
		Comparison::EQ          => '= %s',
		Comparison::IS          => '= %s',
		Comparison::NEQ         => '!= %s',
		Comparison::GT          => '> %s',
		Comparison::GTE         => '>= %s',
		Comparison::LT          => '< %s',
		Comparison::LTE         => '<= %s',
		Comparison::IN          => 'IN (%s)',
		Comparison::NIN         => 'NOT IN (%s)',
		Comparison::CONTAINS    => 'LIKE %s',
		Comparison::STARTS_WITH => 'LIKE %s',
		Comparison::ENDS_WITH   => 'LIKE %s',
	];

	/**
	 * Instantiate a WPQuerySQLExpressionVisitor object.
	 *
	 * @param string $tableName Name of the table to generate SQL for.
	 */
	public function __construct( string $tableName ) {
		$this->tableName = $tableName;
	}

	/**
	 * Converts a comparison expression into the target query language output.
	 *
	 * @param Comparison $comparison
	 *
	 * @return mixed
	 */
	public function walkComparison( Comparison $comparison ) {
		$field = $comparison->getField();
		$value = $comparison->getValue()
		                    ->getValue();

		if ( isset( $this->classMetadata->associationMappings[ $field ] )
		     && $value !== null
		     && ! is_object( $value )
		     && ! \in_array(
				$comparison->getOperator(),
				[ Comparison::IN, Comparison::NIN ],
				$strict = true
			)
		) {

			throw new RuntimeException( "Unsupported operator in expression: {$comparison->getOperator()}" );
		}

		return $this->getSelectConditionStatementSQL(
			$field,
			$value,
			null,
			$comparison->getOperator()
		);
	}

	/**
	 * Converts a value expression into the target query language part.
	 *
	 * @param Value $value
	 *
	 * @return mixed
	 */
	public function walkValue( Value $value ) {
		// TODO: Check how the value should be passed with wpdb.
		return '?';
	}

	/**
	 * Converts a composite expression into the target query language output.
	 *
	 * @param CompositeExpression $expr
	 *
	 * @return mixed
	 *
	 * @throws RuntimeException
	 */
	public function walkCompositeExpression( CompositeExpression $expr ) {
		$expressionList = [];

		foreach ( $expr->getExpressionList() as $child ) {
			$expressionList[] = $this->dispatch( $child );
		}

		switch ( $expr->getType() ) {
			case CompositeExpression::TYPE_AND:
				return '(' . implode( ' AND ', $expressionList ) . ')';

			case CompositeExpression::TYPE_OR:
				return '(' . implode( ' OR ', $expressionList ) . ')';

			default:
				throw new RuntimeException( "Unknown composite {$expr->getType()}" );
		}
	}

	/**
	 * Get the SQL statement for a single condition.
	 *
	 * @param string $field
	 * @param        $value
	 * @param null   $assoc
	 * @param null   $comparison
	 * @return string
	 */
	protected function getSelectConditionStatementSQL( $field, $value, $assoc = null, $comparison = null ) {
		global $wpdb;

		$selectedColumns = [];
		$columns         = $this->getSelectConditionStatementColumnSQL(
			$field,
			$assoc
		);

		if ( count( $columns ) > 1 && $comparison === Comparison::IN ) {
			throw new RuntimeException( 'Multi-column IN expressions are not supported.' );
		}

		foreach ( $columns as $column ) {
			$placeholder = '?';

			// TODO: This is simplified for now. We just use the value as-is
			// when we don't detect a placeholder. We probably need to add
			// conversion of values to make it work across types.
			if ( ! \is_string( $value )
			     || ( $value !== '?' && strpos( $value, ':' ) !== 0 ) ) {
				// TODO: Quote according to type.
				if ( \is_array( $value ) ) {
					$placeholder = implode( ',', array_map( function ( $element ) {
						return \is_numeric( $element ) ? "{$element}" : "'{$element}'";
					}, $value ) );
				} else {
					$placeholder = in_array( $comparison, [
						Comparison::CONTAINS,
						Comparison::STARTS_WITH,
						Comparison::ENDS_WITH,
					], true )
						? $value
						: "'{$value}'";
				}
			}

			if ( null !== $comparison ) {
				// special case null value handling
				if ( ( $comparison === Comparison::EQ || $comparison === Comparison::IS ) && null === $value ) {
					$selectedColumns[] = $column . ' IS NULL';

					continue;
				}

				if ( $comparison === Comparison::NEQ && null === $value ) {
					$selectedColumns[] = $column . ' IS NOT NULL';

					continue;
				}

				if ( $comparison === Comparison::CONTAINS ) {
					$likeTerm = $wpdb->esc_like( $placeholder );
					$selectedColumns[] = $column . " LIKE '%{$likeTerm}%'";

					continue;
				}

				if ( $comparison === Comparison::STARTS_WITH ) {
					$likeTerm = $wpdb->esc_like( $placeholder );
					$selectedColumns[] = $column . " LIKE '{$likeTerm}%'";

					continue;
				}

				if ( $comparison === Comparison::ENDS_WITH ) {
					$likeTerm = $wpdb->esc_like( $placeholder );
					$selectedColumns[] = $column . " LIKE '%{$likeTerm}'";

					continue;
				}

				$selectedColumns[] = $column . ' ' . sprintf(
						self::$comparisonMap[ $comparison ],
						$placeholder
					);

				continue;
			}

			if ( \is_array( $value ) ) {
				$in = sprintf( '%s IN (%s)', $column, $placeholder );

				if ( \in_array( null, $value, true ) ) {
					$selectedColumns[] = sprintf(
						'(%s OR %s IS NULL)',
						$in,
						$column
					);

					continue;
				}

				$selectedColumns[] = $in;

				continue;
			}

			if ( null === $value ) {
				$selectedColumns[] = sprintf( '%s IS NULL', $column );

				continue;
			}

			$selectedColumns[] = sprintf( '%s = %s', $column, $placeholder );
		}

		return implode( ' AND ', $selectedColumns );
	}

	/**
	 * Builds the left-hand-side of a where condition statement.
	 *
	 * @param string     $field
	 * @param array|null $assoc
	 *
	 * @return string[]
	 */
	protected function getSelectConditionStatementColumnSQL( $field, $assoc = null ) {
		global $wpdb;

		return [ "{$wpdb->get_blog_prefix()}{$this->tableName}.{$field}" ];
	}
}
