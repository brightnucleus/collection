<?php declare( strict_types=1 );

namespace BrightNucleus\Collection\Tests\Unit;

use BrightNucleus\Collection\Criteria;
use BrightNucleus\Collection\LazilyHydratedCollection;
use BrightNucleus\Collection\QueryGenerator;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for LazilyHydratedCollection demonstrating lazy loading behavior.
 */
final class LazilyHydratedCollectionTest extends TestCase
{

    /**
     * @covers \BrightNucleus\Collection\LazilyHydratedCollection::hydrate
     * @covers \BrightNucleus\Collection\LazilyHydratedCollection::isHydrated
     * @covers \BrightNucleus\Collection\LazilyHydratedCollection::count
     */
    public function test_it_delays_hydration_until_needed()
    {
        $collection = new TestLazilyHydratedCollection();

        // Collection should not be hydrated yet
        $this->assertFalse($collection->isHydrated());

        // Accessing count should trigger hydration
        $count = $collection->count();
        $this->assertTrue($collection->isHydrated());
        $this->assertEquals(3, $count);
    }

    /**
     * @covers \BrightNucleus\Collection\LazilyHydratedCollection::hydrate
     * @covers \BrightNucleus\Collection\LazilyHydratedCollection::isHydrated
     * @covers \BrightNucleus\Collection\LazilyHydratedCollection::getIterator
     */
    public function test_it_hydrates_on_iteration()
    {
        $collection = new TestLazilyHydratedCollection();

        $this->assertFalse($collection->isHydrated());

        // Iteration should trigger hydration
        $items = [];
        foreach ( $collection as $item ) {
            $items[] = $item;
        }

        $this->assertTrue($collection->isHydrated());
        $this->assertCount(3, $items);
    }

    /**
     * @covers \BrightNucleus\Collection\LazilyHydratedCollection::hydrate
     * @covers \BrightNucleus\Collection\LazilyHydratedCollection::isHydrated
     * @covers \BrightNucleus\Collection\LazilyHydratedCollection::first
     */
    public function test_it_hydrates_on_element_access()
    {
        $collection = new TestLazilyHydratedCollection();

        $this->assertFalse($collection->isHydrated());

        // Accessing an element should trigger hydration
        $first = $collection->first();

        $this->assertTrue($collection->isHydrated());
        $this->assertEquals('Item 1', $first);
    }

    /**
     * @covers \BrightNucleus\Collection\LazilyHydratedCollection::hydrate
     * @covers \BrightNucleus\Collection\LazilyHydratedCollection::isHydrated
     * @covers \BrightNucleus\Collection\LazilyHydratedCollection::count
     * @covers \BrightNucleus\Collection\LazilyHydratedCollection::first
     * @covers \BrightNucleus\Collection\LazilyHydratedCollection::last
     */
    public function test_it_only_hydrates_once()
    {
        $collection = new TestLazilyHydratedCollection();

        $this->assertFalse($collection->isHydrated());

        // First access triggers hydration
        $collection->count();
        $this->assertTrue($collection->isHydrated());
        $this->assertEquals(1, $collection->getHydrationCount());

        // Subsequent accesses should not re-hydrate
        $collection->count();
        $collection->first();
        $collection->last();
        $this->assertEquals(1, $collection->getHydrationCount());
    }

    /**
     * @covers \BrightNucleus\Collection\LazilyHydratedCollection::add
     * @covers \BrightNucleus\Collection\LazilyHydratedCollection::contains
     * @covers \BrightNucleus\Collection\LazilyHydratedCollection::remove
     * @covers \BrightNucleus\Collection\LazilyHydratedCollection::removeElement
     * @covers \BrightNucleus\Collection\LazilyHydratedCollection::clear
     * @covers \BrightNucleus\Collection\LazilyHydratedCollection::isEmpty
     */
    public function test_it_supports_all_collection_operations()
    {
        $collection = new TestLazilyHydratedCollection();

        // Test add
        $this->assertTrue($collection->add('Item 4'));
        $this->assertCount(4, $collection);
        $this->assertTrue($collection->contains('Item 4'));

        // Test remove
        $removed = $collection->remove(0);
        $this->assertEquals('Item 1', $removed);
        $this->assertCount(3, $collection);

        // Test removeElement
        $this->assertTrue($collection->removeElement('Item 2'));
        $this->assertCount(2, $collection);

        // Test clear
        $collection->clear();
        $this->assertCount(0, $collection);
        $this->assertTrue($collection->isEmpty());
    }

    /**
     * @covers \BrightNucleus\Collection\LazilyHydratedCollection::containsKey
     * @covers \BrightNucleus\Collection\LazilyHydratedCollection::get
     * @covers \BrightNucleus\Collection\LazilyHydratedCollection::set
     * @covers \BrightNucleus\Collection\LazilyHydratedCollection::getKeys
     * @covers \BrightNucleus\Collection\LazilyHydratedCollection::getValues
     */
    public function test_it_supports_key_operations()
    {
        $collection = new TestLazilyHydratedCollection();

        // Test containsKey
        $this->assertTrue($collection->containsKey(0));
        $this->assertTrue($collection->containsKey(1));
        $this->assertFalse($collection->containsKey(10));

        // Test get
        $this->assertEquals('Item 1', $collection->get(0));
        $this->assertEquals('Item 2', $collection->get(1));
        $this->assertNull($collection->get(10));

        // Test set
        $collection->set(5, 'Item 5');
        $this->assertEquals('Item 5', $collection->get(5));

        // Test getKeys
        $keys = $collection->getKeys();
        $this->assertContains(0, $keys);
        $this->assertContains(1, $keys);
        $this->assertContains(5, $keys);

        // Test getValues
        $values = $collection->getValues();
        $this->assertContains('Item 1', $values);
        $this->assertContains('Item 3', $values);
        $this->assertContains('Item 5', $values);
    }

    /**
     * @covers \BrightNucleus\Collection\LazilyHydratedCollection::offsetExists
     * @covers \BrightNucleus\Collection\LazilyHydratedCollection::offsetGet
     * @covers \BrightNucleus\Collection\LazilyHydratedCollection::offsetSet
     * @covers \BrightNucleus\Collection\LazilyHydratedCollection::offsetUnset
     */
    public function test_it_supports_array_access()
    {
        $collection = new TestLazilyHydratedCollection();

        // Test offsetExists
        $this->assertTrue(isset($collection[0]));
        $this->assertFalse(isset($collection[10]));

        // Test offsetGet
        $this->assertEquals('Item 1', $collection[0]);
        $this->assertEquals('Item 2', $collection[1]);

        // Test offsetSet
        $collection[3] = 'Item 4';
        $this->assertEquals('Item 4', $collection[3]);

        // Test offsetUnset
        unset($collection[0]);
        $this->assertFalse(isset($collection[0]));
    }

    /**
     * @covers \BrightNucleus\Collection\LazilyHydratedCollection::filter
     * @covers \BrightNucleus\Collection\LazilyHydratedCollection::map
     * @covers \BrightNucleus\Collection\LazilyHydratedCollection::exists
     * @covers \BrightNucleus\Collection\LazilyHydratedCollection::forAll
     * @covers \BrightNucleus\Collection\LazilyHydratedCollection::partition
     */
    public function test_it_supports_functional_operations()
    {
        $collection = new TestLazilyHydratedCollection();

        // Test filter
        $filtered = $collection->filter(
            function ( $item ) {
                return strpos($item, '2') !== false;
            } 
        );
        $this->assertCount(1, $filtered);
        $this->assertContains('Item 2', $filtered);

        // Test map
        $mapped = $collection->map(
            function ( $item ) {
                return strtoupper($item);
            } 
        );
        $this->assertContains('ITEM 1', $mapped);
        $this->assertContains('ITEM 2', $mapped);

        // Test exists
        $exists = $collection->exists(
            function ( $key, $item ) {
                return $item === 'Item 2';
            } 
        );
        $this->assertTrue($exists);

        // Test forAll
        $allItems = $collection->forAll(
            function ( $key, $item ) {
                return strpos($item, 'Item') === 0;
            } 
        );
        $this->assertTrue($allItems);

        // Test partition
        [ $matching, $notMatching ] = $collection->partition(
            function ( $key, $item ) {
                return $key % 2 === 0;
            } 
        );
        $this->assertCount(2, $matching);
        $this->assertCount(1, $notMatching);
    }

    /**
     * @covers \BrightNucleus\Collection\LazilyHydratedCollection::slice
     * @covers \BrightNucleus\Collection\LazilyHydratedCollection::indexOf
     */
    public function test_it_supports_slicing()
    {
        $collection = new TestLazilyHydratedCollection();

        // Test slice
        $sliced = $collection->slice(1, 2);
        $this->assertCount(2, $sliced);
        $this->assertEquals([ 1 => 'Item 2', 2 => 'Item 3' ], $sliced);

        // Test indexOf
        $this->assertEquals(0, $collection->indexOf('Item 1'));
        $this->assertEquals(1, $collection->indexOf('Item 2'));
        $this->assertFalse($collection->indexOf('Item 99'));
    }

    /**
     * @covers \BrightNucleus\Collection\LazilyHydratedCollection::toArray
     */
    public function test_it_can_be_converted_to_array()
    {
        $collection = new TestLazilyHydratedCollection();

        $array = $collection->toArray();
        $this->assertIsArray($array);
        $this->assertCount(3, $array);
        $this->assertEquals([ 'Item 1', 'Item 2', 'Item 3' ], $array);
    }

    /**
     * @covers \BrightNucleus\Collection\LazilyHydratedCollection::clear
     * @covers \BrightNucleus\Collection\LazilyHydratedCollection::isHydrated
     */
    public function test_clear_marks_as_hydrated()
    {
        $collection = new TestLazilyHydratedCollection();

        $this->assertFalse($collection->isHydrated());

        $collection->clear();

        $this->assertTrue($collection->isHydrated());
        $this->assertCount(0, $collection);
    }

    /**
     * @covers \BrightNucleus\Collection\LazilyHydratedCollection::findFirst
     */
    public function test_it_supports_findFirst()
    {
        $collection = new TestLazilyHydratedCollection();

        $found = $collection->findFirst(
            function ( $key, $value ) {
                return strpos($value, '2') !== false;
            } 
        );

        $this->assertEquals('Item 2', $found);

        $notFound = $collection->findFirst(
            function ( $key, $value ) {
                return $value === 'Non-existent';
            } 
        );

        $this->assertNull($notFound);
    }

    /**
     * @covers \BrightNucleus\Collection\LazilyHydratedCollection::reduce
     */
    public function test_it_supports_reduce()
    {
        $collection = new TestLazilyHydratedCollection();

        $concatenated = $collection->reduce(
            function ( $carry, $item ) {
                return $carry . $item . ';';
            }, '' 
        );

        $this->assertEquals('Item 1;Item 2;Item 3;', $concatenated);

        $count = $collection->reduce(
            function ( $carry, $item ) {
                return $carry + 1;
            }, 0 
        );

        $this->assertEquals(3, $count);
    }
}

/**
 * Test implementation of LazilyHydratedCollection for unit testing.
 */
class TestLazilyHydratedCollection extends LazilyHydratedCollection
{

    private int $hydrationCount = 0;
    private array $testData = [ 'Item 1', 'Item 2', 'Item 3' ];

    protected function doHydrate(): void
    {
        $this->hydrationCount++;
        $this->collection = new ArrayCollection($this->testData);
    }

    public function getHydrationCount(): int
    {
        return $this->hydrationCount;
    }

    public function matching( Criteria $criteria )
    {
        $this->hydrate();
        return $this->collection->matching($criteria);
    }
}