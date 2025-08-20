<?php declare( strict_types=1 );

namespace BrightNucleus\Collection\Tests\Unit;

use BrightNucleus\Collection\Criteria;
use BrightNucleus\Collection\NullCriteria;
use Doctrine\Common\Collections\ExpressionBuilder;
use PHPUnit\Framework\TestCase;

final class CriteriaTest extends TestCase
{

    /**
     * @covers \BrightNucleus\Collection\Criteria::merge
     * @covers \BrightNucleus\Collection\Criteria::from
     */
    public function test_it_can_merge_null_criteria()
    {
        $null_criteria     = new NullCriteria();
        $regular_criteria  = new Criteria();
        $merged_criteria   = $regular_criteria->merge($null_criteria);
        $merged_criteria_2 = $null_criteria->merge($regular_criteria);

        $this->assertInstanceOf(
            get_class($regular_criteria),
            $merged_criteria
        );
        $this->assertNotInstanceOf(NullCriteria::class, $merged_criteria);

        $this->assertInstanceOf(
            get_class($regular_criteria),
            $merged_criteria_2
        );
        $this->assertNotInstanceOf(NullCriteria::class, $merged_criteria_2);
    }

    /**
     * @covers \BrightNucleus\Collection\Criteria::merge
     * @covers \BrightNucleus\Collection\Criteria::andWhere
     */
    public function test_it_can_merge_expressions()
    {
        $expression_builder  = new ExpressionBuilder();
        $expression_a        = $expression_builder->eq(
            'some_field',
            'some_value'
        );
        $expression_b        = $expression_builder->eq(
            'some_field',
            'some_value'
        );
        $expected_expression = $expression_builder->andX(
            $expression_a,
            $expression_b
        );
        $criteria_a          = new Criteria($expression_a);
        $criteria_b          = new Criteria($expression_b);

        $merged_criteria = $criteria_a->merge($criteria_b);

        $this->assertInstanceOf(
            get_class($expected_expression),
            $merged_criteria->getWhereExpression()
        );
        $this->assertEquals(
            $expected_expression->getType(),
            $merged_criteria->getWhereExpression()->getType()
        );
        $this->assertEquals(
            $expected_expression->getExpressionList(),
            $merged_criteria->getWhereExpression()->getExpressionList()
        );
    }

    /**
     * @covers \BrightNucleus\Collection\Criteria::merge
     * @covers \BrightNucleus\Collection\Criteria::orderBy
     */
    public function test_it_can_merge_orderings()
    {
        $criteria_a = ( new Criteria() )
        ->orderBy([ 'name' => Criteria::DESC ]);
        $criteria_b = ( new Criteria() )
        ->orderBy([ 'date' => Criteria::ASC ]);

        $merged_criteria = $criteria_a->merge($criteria_b);

        $this->assertEquals(
            [
            'name' => Criteria::DESC,
            'date' => Criteria::ASC,
            ], $merged_criteria->getOrderings() 
        );
    }

    /**
     * @covers \BrightNucleus\Collection\Criteria::merge
     * @covers \BrightNucleus\Collection\Criteria::orderBy
     */
    public function test_it_uses_the_latest_orderings_for_conflicts()
    {
        $criteria_a = ( new Criteria() )
        ->orderBy([ 'name' => Criteria::DESC ]);
        $criteria_b = ( new Criteria() )
        ->orderBy([ 'name' => Criteria::ASC ]);

        $merged_criteria = $criteria_a->merge($criteria_b);

        $this->assertEquals(
            [
            'name' => Criteria::ASC,
            ], $merged_criteria->getOrderings() 
        );
    }

    /**
     * @covers \BrightNucleus\Collection\Criteria::merge
     * @covers \BrightNucleus\Collection\Criteria::setFirstResult
     */
    public function test_it_can_merge_first_result()
    {
        $criteria_a = ( new Criteria() )
        ->setFirstResult(42);
        $criteria_b = new Criteria();

        $merged_criteria_a = $criteria_a->merge($criteria_b);
        $merged_criteria_b = $criteria_b->merge($criteria_a);

        $this->assertEquals(42, $merged_criteria_a->getFirstResult());
        $this->assertEquals(42, $merged_criteria_b->getFirstResult());
    }

    /**
     * @covers \BrightNucleus\Collection\Criteria::merge
     * @covers \BrightNucleus\Collection\Criteria::setFirstResult
     */
    public function test_it_overrides_first_result()
    {
        $criteria_a = ( new Criteria() )
        ->setFirstResult(42);
        $criteria_b = ( new Criteria() )
        ->setFirstResult(7);

        $merged_criteria = $criteria_a->merge($criteria_b);
        $this->assertEquals(7, $merged_criteria->getFirstResult());
    }

    /**
     * @covers \BrightNucleus\Collection\Criteria::merge
     * @covers \BrightNucleus\Collection\Criteria::setMaxResults
     */
    public function test_it_can_merge_max_results()
    {
        $criteria_a = ( new Criteria() )
        ->setMaxResults(42);
        $criteria_b = new Criteria();

        $merged_criteria_a = $criteria_a->merge($criteria_b);
        $merged_criteria_b = $criteria_b->merge($criteria_a);

        $this->assertEquals(42, $merged_criteria_a->getMaxResults());
        $this->assertEquals(42, $merged_criteria_b->getMaxResults());
    }

    /**
     * @covers \BrightNucleus\Collection\Criteria::merge
     * @covers \BrightNucleus\Collection\Criteria::setMaxResults
     */
    public function test_it_overrides_max_results()
    {
        $criteria_a = ( new Criteria() )
        ->setMaxResults(42);
        $criteria_b = ( new Criteria() )
        ->setMaxResults(7);

        $merged_criteria = $criteria_a->merge($criteria_b);
        $this->assertEquals(7, $merged_criteria->getMaxResults());
    }
}
