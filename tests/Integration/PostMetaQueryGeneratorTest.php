<?php declare(strict_types=1);

namespace BrightNucleus\Collection\Tests\Integration;

use BrightNucleus\Collection\Column;
use BrightNucleus\Collection\Criteria;
use BrightNucleus\Collection\PostMetaQueryGenerator;
use BrightNucleus\Collection\Table;
use WP_UnitTestCase;

/**
 * Integration tests for PostMetaQueryGenerator
 * 
 * These tests require WordPress to be loaded and demonstrate the SQL generation bug
 * where Column objects cause double table prefixing.
 */
final class PostMetaQueryGeneratorTest extends WP_UnitTestCase
{
    public function test_it_generates_correct_sql_for_column_objects()
    {
        global $wpdb;
        
        $expr = Criteria::expr();
        
        // Create a Column object - this is the problematic case
        $column = new Column(Table::POSTMETA, 'post_id');
        
        // Column's __toString() returns "postmeta.post_id"
        $this->assertEquals('postmeta.post_id', (string)$column);
        
        // Doctrine's ExpressionBuilder requires strings, so we convert
        $criteria = Criteria::create()
            ->where($expr->in((string)$column, [1, 2, 3]));
        
        $generator = new PostMetaQueryGenerator($criteria);
        $query = $generator->getQuery();
        
        // Debug output to see the actual query
        $this->assertNotEmpty($query, 'Query should not be empty');
        
        // The query should NOT have double prefixing like "postmeta.postmeta.post_id"
        // It should be "{$wpdb->postmeta}.post_id"
        $expectedTable = $wpdb->postmeta;
        
        // Check that we don't have double prefixing
        $this->assertStringNotContainsString('postmeta.postmeta.post_id', $query, 
            "Query has double prefixing bug! Query: $query");
        
        // Check the correct format is present
        // The query uses 'postmeta' as an alias, not the full table name
        $this->assertStringContainsString('postmeta.post_id IN (1,2,3)', $query,
            "Query should contain correct table.column format. Query: $query");
    }

    public function test_it_generates_correct_sql_for_string_fields()
    {
        global $wpdb;
        
        $expr = Criteria::expr();
        $criteria = Criteria::create()
            ->where($expr->eq('meta_key', 'test_key'));
        
        $generator = new PostMetaQueryGenerator($criteria);
        $query = $generator->getQuery();
        
        // The query uses 'postmeta' as an alias
        $this->assertStringContainsString("postmeta.meta_key = 'test_key'", $query);
    }

    public function test_it_handles_field_mapping()
    {
        global $wpdb;
        
        $expr = Criteria::expr();
        $criteria = Criteria::create()
            ->where($expr->eq('key', 'test_key'));
        
        $generator = new PostMetaQueryGenerator($criteria);
        $query = $generator->getQuery();
        
        // 'key' should be mapped to 'meta_key'
        // The query uses 'postmeta' as an alias
        $this->assertStringContainsString("postmeta.meta_key = 'test_key'", $query);
    }

    public function test_it_handles_id_field_mapping()
    {
        global $wpdb;
        
        $expr = Criteria::expr();
        $criteria = Criteria::create()
            ->where($expr->eq('id', 123));
        
        $generator = new PostMetaQueryGenerator($criteria);
        $query = $generator->getQuery();
        
        // 'id' should be mapped to 'meta_id'
        // The query uses 'postmeta' as an alias
        $this->assertStringContainsString("postmeta.meta_id = '123'", $query);
    }

    public function test_it_handles_value_field_mapping()
    {
        global $wpdb;
        
        $expr = Criteria::expr();
        $criteria = Criteria::create()
            ->where($expr->eq('value', 'test_value'));
        
        $generator = new PostMetaQueryGenerator($criteria);
        $query = $generator->getQuery();
        
        // 'value' should be mapped to 'meta_value'
        // The query uses 'postmeta' as an alias
        $this->assertStringContainsString("postmeta.meta_value = 'test_value'", $query);
    }

    public function test_it_generates_correct_sql_for_multiple_conditions_with_column()
    {
        global $wpdb;
        
        $expr = Criteria::expr();
        $column = new Column(Table::POSTMETA, 'post_id');
        
        $criteria = Criteria::create()
            ->where($expr->eq((string)$column, 1))
            ->andWhere($expr->eq('meta_key', 'test_key'));
        
        $generator = new PostMetaQueryGenerator($criteria);
        $query = $generator->getQuery();
        
        // Both conditions should be properly formatted
        // The query uses 'postmeta' as an alias
        $this->assertStringContainsString("postmeta.post_id = '1'", $query);
        $this->assertStringContainsString("postmeta.meta_key = 'test_key'", $query);
        $this->assertStringContainsString(' AND ', $query);
        
        // Should not have double prefixing
        $this->assertStringNotContainsString('postmeta.postmeta.', $query);
    }

    public function test_it_handles_comparison_operators_with_column()
    {
        global $wpdb;
        
        $expr = Criteria::expr();
        $column = new Column(Table::POSTMETA, 'meta_id');
        
        $criteria = Criteria::create()
            ->where($expr->gt((string)$column, 100));
        
        $generator = new PostMetaQueryGenerator($criteria);
        $query = $generator->getQuery();
        
        // The query uses 'postmeta' as an alias
        $this->assertStringContainsString("postmeta.meta_id > '100'", $query);
        $this->assertStringNotContainsString('postmeta.postmeta.', $query);
    }

    public function test_full_query_structure_matches_bug_report()
    {
        global $wpdb;
        
        $expr = Criteria::expr();
        $column = new Column(Table::POSTMETA, 'post_id');
        
        // This replicates the exact scenario from the bug report
        $criteria = Criteria::create()
            ->where($expr->in((string)$column, [4]));
        
        $generator = new PostMetaQueryGenerator($criteria);
        $query = $generator->getQuery();
        
        $expectedTable = $wpdb->postmeta;
        
        // Test the full query structure as described in the bug report
        // The query uses 'postmeta' as an alias for the actual table
        $this->assertStringContainsString("SELECT DISTINCT postmeta.*", $query);
        $this->assertStringContainsString("FROM $expectedTable postmeta", $query);
        $this->assertStringContainsString('WHERE', $query);
        
        // The critical test: should be "postmeta.post_id" not "postmeta.postmeta.post_id"
        $this->assertStringContainsString('postmeta.post_id IN (4)', $query,
            "Query should have correct column reference. Actual query: $query");
        
        $this->assertDoesNotMatchRegularExpression(
            '/postmeta\.postmeta\./',
            $query,
            "Query should not have double table prefixing. Actual query: $query"
        );
    }

    public function test_cross_table_references_are_preserved()
    {
        global $wpdb;
        
        // This tests that references to other tables are not incorrectly prefixed
        $expr = Criteria::expr();
        
        // Simulate a cross-table reference (though not actually used in PostMeta context)
        $criteria = Criteria::create()
            ->where($expr->eq('posts.ID', 1));
        
        $generator = new PostMetaQueryGenerator($criteria);
        $query = $generator->getQuery();
        
        // The 'posts.ID' should be treated as-is since it already has a table prefix
        // but different from our context table
        // Note: The current implementation might handle this incorrectly
        // This test documents the expected behavior
        $this->assertStringNotContainsString("postmeta.posts.ID", $query,
            "Cross-table references should not get our table prefix added");
    }

    public function test_prefixed_field_handling()
    {
        global $wpdb;
        
        // Test what happens when we pass an already-prefixed field
        // This simulates what happens after Column->__toString()
        $expr = Criteria::expr();
        $criteria = Criteria::create()
            ->where($expr->eq('postmeta.post_id', 1));
        
        $generator = new PostMetaQueryGenerator($criteria);
        $query = $generator->getQuery();
        
        // The field already has 'postmeta.' prefix
        // It should not get double-prefixed
        $this->assertStringNotContainsString('postmeta.postmeta.post_id', $query,
            "Already-prefixed fields should not get double-prefixed. Query: $query");
        
        // Should contain the correct format
        $this->assertStringContainsString("postmeta.post_id = '1'", $query,
            "Query should contain correctly formatted column. Query: $query");
    }
}