<?php declare(strict_types=1);

namespace BrightNucleus\Collection\Tests\Unit;

use BrightNucleus\Collection\Column;
use BrightNucleus\Collection\Table;
use BrightNucleus\Collection\WPMetaQuerySQLExpressionVisitor;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\Value;
use PHPUnit\Framework\TestCase;

final class WPMetaQuerySQLExpressionVisitorTest extends TestCase
{
    private WPMetaQuerySQLExpressionVisitor $visitor;

    protected function setUp(): void
    {
        $this->visitor = new WPMetaQuerySQLExpressionVisitor('postmeta');
    }

    public function test_it_can_be_instantiated()
    {
        $this->assertInstanceOf(WPMetaQuerySQLExpressionVisitor::class, $this->visitor);
    }

    public function test_it_handles_column_objects_correctly()
    {
        // Create a Column object
        $column = new Column(Table::POSTMETA, 'post_id');
        $value = new Value([1, 2, 3]);
        $comparison = new Comparison($column, Comparison::IN, $value);
        
        // Process the comparison
        $result = $this->visitor->walkComparison($comparison);
        
        // The result should contain "postmeta.post_id" not "postmeta.postmeta.post_id"
        $this->assertStringContainsString('postmeta.post_id', $result);
        $this->assertStringNotContainsString('postmeta.postmeta.post_id', $result);
    }

    public function test_it_handles_string_fields_with_table_prefix()
    {
        // When Doctrine converts Column to string, it becomes "postmeta.post_id"
        $field = 'postmeta.post_id';
        $value = new Value(1);
        $comparison = new Comparison($field, Comparison::EQ, $value);
        
        $result = $this->visitor->walkComparison($comparison);
        
        // Should not double-prefix the field
        $this->assertStringContainsString('postmeta.post_id', $result);
        $this->assertStringNotContainsString('postmeta.postmeta.post_id', $result);
    }

    public function test_it_handles_plain_string_fields()
    {
        $field = 'meta_key';
        $value = new Value('test_key');
        $comparison = new Comparison($field, Comparison::EQ, $value);
        
        $result = $this->visitor->walkComparison($comparison);
        
        // Should add the table prefix
        $this->assertStringContainsString('postmeta.meta_key', $result);
    }

    public function test_it_applies_field_mapping()
    {
        // Test 'key' mapping to 'meta_key'
        $field = 'key';
        $value = new Value('test_key');
        $comparison = new Comparison($field, Comparison::EQ, $value);
        
        $result = $this->visitor->walkComparison($comparison);
        
        $this->assertStringContainsString('postmeta.meta_key', $result);
    }

    public function test_it_applies_id_field_mapping()
    {
        // Test 'id' mapping to 'meta_id'
        $field = 'id';
        $value = new Value(123);
        $comparison = new Comparison($field, Comparison::EQ, $value);
        
        $result = $this->visitor->walkComparison($comparison);
        
        $this->assertStringContainsString('postmeta.meta_id', $result);
    }

    public function test_it_applies_value_field_mapping()
    {
        // Test 'value' mapping to 'meta_value'
        $field = 'value';
        $value = new Value('some_value');
        $comparison = new Comparison($field, Comparison::EQ, $value);
        
        $result = $this->visitor->walkComparison($comparison);
        
        $this->assertStringContainsString('postmeta.meta_value', $result);
    }

    public function test_it_handles_cross_table_column_references()
    {
        // Column from a different table (for potential joins)
        $column = new Column('posts', 'ID');
        $value = new Value(1);
        $comparison = new Comparison($column, Comparison::EQ, $value);
        
        $result = $this->visitor->walkComparison($comparison);
        
        // Should preserve the original table reference
        $this->assertStringContainsString('posts.ID', $result);
        $this->assertStringNotContainsString('postmeta.posts.ID', $result);
    }

    public function test_it_handles_cross_table_string_references()
    {
        // String field with different table prefix (for joins)
        $field = 'posts.ID';
        $value = new Value(1);
        $comparison = new Comparison($field, Comparison::EQ, $value);
        
        $result = $this->visitor->walkComparison($comparison);
        
        // Should preserve the original reference
        $this->assertStringContainsString('posts.ID', $result);
        $this->assertStringNotContainsString('postmeta.posts.ID', $result);
    }

    public function test_it_handles_in_operator_with_column()
    {
        $column = new Column(Table::POSTMETA, 'post_id');
        $value = new Value([4, 5, 6]);
        $comparison = new Comparison($column, Comparison::IN, $value);
        
        $result = $this->visitor->walkComparison($comparison);
        
        $this->assertStringContainsString('postmeta.post_id IN', $result);
        $this->assertStringNotContainsString('postmeta.postmeta.', $result);
    }

    public function test_it_handles_greater_than_with_column()
    {
        $column = new Column(Table::POSTMETA, 'meta_id');
        $value = new Value(100);
        $comparison = new Comparison($column, Comparison::GT, $value);
        
        $result = $this->visitor->walkComparison($comparison);
        
        $this->assertStringContainsString('postmeta.meta_id >', $result);
        $this->assertStringNotContainsString('postmeta.postmeta.', $result);
    }

    public function test_it_handles_contains_operator()
    {
        $field = 'meta_value';
        $value = new Value('search_term');
        $comparison = new Comparison($field, Comparison::CONTAINS, $value);
        
        $result = $this->visitor->walkComparison($comparison);
        
        $this->assertStringContainsString('postmeta.meta_value LIKE', $result);
    }

    public function test_critical_bug_scenario()
    {
        // This is the exact scenario that causes the bug:
        // 1. Column object is created
        $column = new Column(Table::POSTMETA, 'post_id');
        
        // 2. Column's __toString() returns "postmeta.post_id"
        $columnAsString = (string)$column;
        $this->assertEquals('postmeta.post_id', $columnAsString);
        
        // 3. Doctrine Comparison stores it as a string
        $value = new Value([4]);
        $comparison = new Comparison($columnAsString, Comparison::IN, $value);
        
        // 4. The visitor should handle this correctly
        $result = $this->visitor->walkComparison($comparison);
        
        // The critical assertion: no double prefixing
        $this->assertStringContainsString('postmeta.post_id IN', $result);
        $this->assertStringNotContainsString('postmeta.postmeta.post_id', $result);
    }
}