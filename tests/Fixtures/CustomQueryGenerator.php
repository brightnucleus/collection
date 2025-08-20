<?php declare( strict_types=1 );

namespace BrightNucleus\Collection\Tests\Fixtures;

use BrightNucleus\Collection\Criteria;
use BrightNucleus\Collection\QueryGenerator;
use BrightNucleus\Collection\WPQuerySQLExpressionVisitor;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;

/**
 * CustomQueryGenerator fixture that demonstrates implementing custom SQL generation
 * with advanced features like joins, subqueries, and custom functions.
 */
final class CustomQueryGenerator implements QueryGenerator
{

    private Criteria $criteria;
    private string $table = 'wp_posts';
    private array $joins = [];
    private array $customFilters = [];
    private bool $useDistinct = false;
    private array $aggregates = [];
    private ?string $groupBy = null;
    private ?string $having = null;

    public function __construct( Criteria $criteria )
    {
        $this->criteria = $criteria;
    }

    /**
     * Set the main table to query from.
     */
    public function setTable( string $table ): self
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Add a JOIN clause.
     */
    public function addJoin( string $type, string $table, string $condition ): self
    {
        $this->joins[] = [
        'type' => strtoupper($type),
        'table' => $table,
        'condition' => $condition
        ];
        return $this;
    }

    /**
     * Add a custom filter condition.
     */
    public function addCustomFilter( string $condition ): self
    {
        $this->customFilters[] = $condition;
        return $this;
    }

    /**
     * Enable DISTINCT in SELECT.
     */
    public function distinct( bool $distinct = true ): self
    {
        $this->useDistinct = $distinct;
        return $this;
    }

    /**
     * Add an aggregate function.
     */
    public function addAggregate( string $function, string $field, ?string $alias = null ): self
    {
        $this->aggregates[] = [
        'function' => $function,
        'field' => $field,
        'alias' => $alias
        ];
        return $this;
    }

    /**
     * Set GROUP BY clause.
     */
    public function groupBy( string $field ): self
    {
        $this->groupBy = $field;
        return $this;
    }

    /**
     * Set HAVING clause.
     */
    public function having( string $condition ): self
    {
        $this->having = $condition;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getQuery(): string
    {
        $parts = array_filter(
            [
            $this->getSelectClause(),
            $this->getFromClause(),
            $this->getJoinClauses(),
            $this->getWhereClause(),
            $this->getGroupByClause(),
            $this->getHavingClause(),
            $this->getOrderByClause(),
            $this->getLimitClause(),
            ] 
        );

        return implode(' ', $parts);
    }

    /**
     * {@inheritDoc}
     */
    public function getSelectClause(): string
    {
        $fields = ['*'];

        if (! empty($this->aggregates) ) {
            $fields = [];
            foreach ( $this->aggregates as $aggregate ) {
                $expr = "{$aggregate['function']}({$aggregate['field']})";
                if ($aggregate['alias'] ) {
                    $expr .= " AS {$aggregate['alias']}";
                }
                $fields[] = $expr;
            }
        }

        $distinct = $this->useDistinct ? 'DISTINCT ' : '';
        return "SELECT {$distinct}" . implode(', ', $fields);
    }

    /**
     * {@inheritDoc}
     */
    public function getFromClause(): string
    {
        global $wpdb;
        return "FROM {$wpdb->prefix}{$this->table}";
    }

    /**
     * Get JOIN clauses.
     */
    public function getJoinClauses(): string
    {
        if (empty($this->joins) ) {
            return '';
        }

        global $wpdb;
        $clauses = [];

        foreach ( $this->joins as $join ) {
            $table = strpos($join['table'], $wpdb->prefix) === 0 
            ? $join['table'] 
            : $wpdb->prefix . $join['table'];
            
            $clauses[] = "{$join['type']} JOIN {$table} ON {$join['condition']}";
        }

        return implode(' ', $clauses);
    }

    /**
     * {@inheritDoc}
     */
    public function getWhereClause(): string
    {
        $conditions = [];

        // Add criteria-based conditions
        if ($this->criteria && $this->criteria->getWhereExpression() ) {
            $visitor = new CustomExpressionVisitor();
            $conditions[] = $visitor->dispatch($this->criteria->getWhereExpression());
        }

        // Add custom filters
        $conditions = array_merge($conditions, $this->customFilters);

        if (empty($conditions) ) {
            return '';
        }

        return 'WHERE ' . implode(' AND ', $conditions);
    }

    /**
     * Get GROUP BY clause.
     */
    public function getGroupByClause(): string
    {
        return $this->groupBy ? "GROUP BY {$this->groupBy}" : '';
    }

    /**
     * Get HAVING clause.
     */
    public function getHavingClause(): string
    {
        return $this->having ? "HAVING {$this->having}" : '';
    }

    /**
     * {@inheritDoc}
     */
    public function getOrderByClause(): string
    {
        $orderings = $this->criteria->getOrderings();

        if (empty($orderings) ) {
            return '';
        }

        $orderBy = [];
        foreach ( $orderings as $field => $direction ) {
            $orderBy[] = "{$field} {$direction}";
        }

        return 'ORDER BY ' . implode(', ', $orderBy);
    }

    /**
     * {@inheritDoc}
     */
    public function getLimitClause(): string
    {
        $maxResults = $this->criteria->getMaxResults();
        $firstResult = $this->criteria->getFirstResult();

        if ($maxResults === null ) {
            return '';
        }

        $limit = "LIMIT {$maxResults}";

        if ($firstResult !== null && $firstResult > 0 ) {
            $limit .= " OFFSET {$firstResult}";
        }

        return $limit;
    }

    /**
     * Build a subquery.
     */
    public function buildSubquery( string $field, string $operator, self $subquery ): string
    {
        $sql = $subquery->getQuery();
        return "{$field} {$operator} ({$sql})";
    }

    /**
     * Build a UNION query.
     */
    public function union( self $other, bool $all = false ): string
    {
        $unionType = $all ? 'UNION ALL' : 'UNION';
        return "({$this->getQuery()}) {$unionType} ({$other->getQuery()})";
    }

    /**
     * Build a CTE (Common Table Expression).
     */
    public function withCTE( string $name, self $cteQuery ): string
    {
        return "WITH {$name} AS ({$cteQuery->getQuery()}) {$this->getQuery()}";
    }

    /**
     * Add full-text search.
     */
    public function fullTextSearch( array $fields, string $query ): self
    {
        $fieldList = implode(', ', $fields);
        $this->addCustomFilter("MATCH({$fieldList}) AGAINST('{$query}' IN BOOLEAN MODE)");
        return $this;
    }

    /**
     * Add a date range filter.
     */
    public function dateRange( string $field, string $start, string $end ): self
    {
        $this->addCustomFilter("{$field} BETWEEN '{$start}' AND '{$end}'");
        return $this;
    }

    /**
     * Add a NULL check.
     */
    public function whereNull( string $field ): self
    {
        $this->addCustomFilter("{$field} IS NULL");
        return $this;
    }

    /**
     * Add a NOT NULL check.
     */
    public function whereNotNull( string $field ): self
    {
        $this->addCustomFilter("{$field} IS NOT NULL");
        return $this;
    }

    /**
     * Add a REGEXP condition.
     */
    public function whereRegexp( string $field, string $pattern ): self
    {
        $this->addCustomFilter("{$field} REGEXP '{$pattern}'");
        return $this;
    }

    /**
     * Add a JSON extraction condition (MySQL 5.7+).
     */
    public function whereJson( string $field, string $path, $value ): self
    {
        $this->addCustomFilter("JSON_EXTRACT({$field}, '{$path}') = '{$value}'");
        return $this;
    }

    /**
     * Get the count query.
     */
    public function getCountQuery(): string
    {
        $generator = clone $this;
        $generator->aggregates = [];
        $generator->addAggregate('COUNT', '*', 'total');
        $generator->criteria = $generator->criteria
            ->setMaxResults(null)
            ->setFirstResult(null);
        
        return $generator->getQuery();
    }

    /**
     * Get optimized query with hints.
     */
    public function getOptimizedQuery(): string
    {
        $query = $this->getQuery();
        
        // Add query hints for optimization
        $hints = [];
        
        if ($this->useDistinct ) {
            $hints[] = 'SQL_CALC_FOUND_ROWS';
        }
        
        if (! empty($this->joins) ) {
            $hints[] = 'SQL_BIG_RESULT';
        }

        if (! empty($hints) ) {
            $query = str_replace('SELECT ', 'SELECT ' . implode(' ', $hints) . ' ', $query);
        }

        return $query;
    }
}

/**
 * Custom expression visitor for advanced SQL generation.
 */
class CustomExpressionVisitor extends WPQuerySQLExpressionVisitor
{

    /**
     * {@inheritDoc}
     */
    public function walkComparison( Comparison $comparison )
    {
        $field = $comparison->getField();
        $value = $comparison->getValue()->getValue();
        $operator = $comparison->getOperator();

        // Handle special field prefixes
        if (strpos($field, 'meta.') === 0 ) {
            return $this->buildMetaQuery(substr($field, 5), $operator, $value);
        }

        if (strpos($field, 'tax.') === 0 ) {
            return $this->buildTaxonomyQuery(substr($field, 4), $operator, $value);
        }

        if (strpos($field, 'json.') === 0 ) {
            return $this->buildJsonQuery(substr($field, 5), $operator, $value);
        }

        // Default handling
        return parent::walkComparison($comparison);
    }

    /**
     * Build a meta query condition.
     */
    private function buildMetaQuery( string $key, string $operator, $value ): string
    {
        global $wpdb;
        
        $metaTable = $wpdb->postmeta;
        $condition = "EXISTS (
			SELECT 1 FROM {$metaTable} 
			WHERE {$metaTable}.post_id = {$wpdb->posts}.ID 
			AND {$metaTable}.meta_key = '{$key}'";

        switch ( $operator ) {
        case '=':
            $condition .= " AND {$metaTable}.meta_value = '{$value}'";
            break;
        case '!=':
            $condition .= " AND {$metaTable}.meta_value != '{$value}'";
            break;
        case '>':
            $condition .= " AND CAST({$metaTable}.meta_value AS SIGNED) > {$value}";
            break;
        case '<':
            $condition .= " AND CAST({$metaTable}.meta_value AS SIGNED) < {$value}";
            break;
        case 'CONTAINS':
            $condition .= " AND {$metaTable}.meta_value LIKE '%{$value}%'";
            break;
        }

        $condition .= ")";
        return $condition;
    }

    /**
     * Build a taxonomy query condition.
     */
    private function buildTaxonomyQuery( string $taxonomy, string $operator, $value ): string
    {
        global $wpdb;
        
        $termTable = $wpdb->terms;
        $termTaxTable = $wpdb->term_taxonomy;
        $termRelTable = $wpdb->term_relationships;

        return "EXISTS (
			SELECT 1 FROM {$termRelTable}
			INNER JOIN {$termTaxTable} ON {$termRelTable}.term_taxonomy_id = {$termTaxTable}.term_taxonomy_id
			INNER JOIN {$termTable} ON {$termTaxTable}.term_id = {$termTable}.term_id
			WHERE {$termRelTable}.object_id = {$wpdb->posts}.ID
			AND {$termTaxTable}.taxonomy = '{$taxonomy}'
			AND {$termTable}.slug = '{$value}'
		)";
    }

    /**
     * Build a JSON query condition.
     */
    private function buildJsonQuery( string $path, string $operator, $value ): string
    {
        global $wpdb;
        
        $field = "{$wpdb->posts}.post_content";
        $jsonPath = '$.' . str_replace('.', '.', $path);

        switch ( $operator ) {
        case '=':
            return "JSON_EXTRACT({$field}, '{$jsonPath}') = '{$value}'";
        case '!=':
            return "JSON_EXTRACT({$field}, '{$jsonPath}') != '{$value}'";
        case 'CONTAINS':
            return "JSON_CONTAINS({$field}, '\"{$value}\"', '{$jsonPath}')";
        default:
            return "JSON_EXTRACT({$field}, '{$jsonPath}') {$operator} '{$value}'";
        }
    }
}