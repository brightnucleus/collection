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

interface QueryGenerator {

	/**
	 * Get the query generated from the individual clauses.
	 *
	 * @return string
	 */
	public function getQuery(): string;

	/**
	 * Get SELECT clause.
	 *
	 * @return string SELECT clause.
	 */
	public function getSelectClause(): string;

	/**
	 * Get the FROM clause.
	 *
	 * @return string FROM clause.
	 */
	public function getFromClause(): string;

	/**
	 * Get the WHERE clause.
	 *
	 * Can be an empty string.
	 *
	 * @return string WHERE clause.
	 */
	public function getWhereClause(): string;

	/**
	 * Get the ORDER BY clause.
	 *
	 * Can be an empty string.
	 *
	 * @return string ORDER BY clause.
	 */
	public function getOrderByClause(): string;

	/**
	 * Get the LIMIT clause.
	 *
	 * Can be an empty string.
	 *
	 * @return string LIMIT clause.
	 */
	public function getLimitClause(): string;
}
