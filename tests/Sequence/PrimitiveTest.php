<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\Sequence;

use Innmind\Immutable\{
    Sequence\Primitive,
    Sequence\Implementation,
    Map,
    Sequence,
    Str,
    Set,
    SideEffect,
};
use PHPUnit\Framework\TestCase;

class PrimitiveTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Implementation::class,
            new Primitive,
        );
    }

    public function testSize()
    {
        $this->assertSame(2, (new Primitive([1, 1]))->size());
        $this->assertSame(2, (new Primitive([1, 1]))->count());
    }

    public function testIterator()
    {
        $this->assertSame(
            [1, 2, 3],
            \iterator_to_array((new Primitive([1, 2, 3]))->iterator()),
        );
    }

    public function testGet()
    {
        $this->assertSame(42, $this->get(new Primitive([1, 42, 3]), 1));
    }

    public function testReturnNothingWhenIndexNotFound()
    {
        $this->assertNull($this->get(new Primitive, 0));
    }

    public function testDiff()
    {
        $a = new Primitive([1, 2]);
        $b = new Primitive([2, 3]);
        $c = $a->diff($b);

        $this->assertSame([1, 2], \iterator_to_array($a->iterator()));
        $this->assertSame([2, 3], \iterator_to_array($b->iterator()));
        $this->assertInstanceOf(Primitive::class, $c);
        $this->assertSame([1], \iterator_to_array($c->iterator()));
    }

    public function testDistinct()
    {
        $a = new Primitive([1, 2, 1]);
        $b = $a->distinct();

        $this->assertSame([1, 2, 1], \iterator_to_array($a->iterator()));
        $this->assertInstanceOf(Primitive::class, $b);
        $this->assertSame([1, 2], \iterator_to_array($b->iterator()));
    }

    public function testDrop()
    {
        $a = new Primitive([1, 2, 3, 4]);
        $b = $a->drop(2);

        $this->assertSame([1, 2, 3, 4], \iterator_to_array($a->iterator()));
        $this->assertInstanceOf(Primitive::class, $b);
        $this->assertSame([3, 4], \iterator_to_array($b->iterator()));
    }

    public function testDropEnd()
    {
        $a = new Primitive([1, 2, 3, 4]);
        $b = $a->dropEnd(2);

        $this->assertSame([1, 2, 3, 4], \iterator_to_array($a->iterator()));
        $this->assertInstanceOf(Primitive::class, $b);
        $this->assertSame([1, 2], \iterator_to_array($b->iterator()));
    }

    public function testEquals()
    {
        $this->assertTrue((new Primitive([1, 2]))->equals(new Primitive([1, 2])));
        $this->assertFalse((new Primitive([1, 2]))->equals(new Primitive([2])));
    }

    public function testFilter()
    {
        $a = new Primitive([1, 2, 3, 4]);
        $b = $a->filter(static fn($i) => $i % 2 === 0);

        $this->assertSame([1, 2, 3, 4], \iterator_to_array($a->iterator()));
        $this->assertInstanceOf(Primitive::class, $b);
        $this->assertSame([2, 4], \iterator_to_array($b->iterator()));
    }

    public function testForeach()
    {
        $sequence = new Primitive([1, 2, 3, 4]);
        $calls = 0;
        $sum = 0;

        $this->assertInstanceOf(
            SideEffect::class,
            $sequence->foreach(static function($i) use (&$calls, &$sum) {
                ++$calls;
                $sum += $i;
            }),
        );
        $this->assertSame(4, $calls);
        $this->assertSame(10, $sum);
    }

    public function testGroupEmptySequence()
    {
        $this->assertTrue(
            (new Primitive)
                ->groupBy(static fn($i) => $i)
                ->equals(Map::of()),
        );
    }

    public function testGroupBy()
    {
        $sequence = new Primitive([1, 2, 3, 4]);
        $groups = $sequence->groupBy(static fn($i) => $i % 2);

        $this->assertSame([1, 2, 3, 4], \iterator_to_array($sequence->iterator()));
        $this->assertInstanceOf(Map::class, $groups);
        $this->assertCount(2, $groups);
        $this->assertSame([2, 4], $this->get($groups, 0)->toList());
        $this->assertSame([1, 3], $this->get($groups, 1)->toList());
    }

    public function testReturnNothingWhenTryingToAccessFirstElementOnEmptySequence()
    {
        $this->assertNull(
            (new Primitive)->first()->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
    }

    public function testReturnNothingWhenTryingToAccessLastElementOnEmptySequence()
    {
        $this->assertNull(
            (new Primitive)->last()->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
    }

    public function testFirst()
    {
        $this->assertSame(
            2,
            (new Primitive([2, 3, 4]))->first()->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
    }

    public function testLast()
    {
        $this->assertSame(
            4,
            (new Primitive([2, 3, 4]))->last()->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
    }

    public function testContains()
    {
        $sequence = new Primitive([1, 2, 3]);

        $this->assertTrue($sequence->contains(2));
        $this->assertFalse($sequence->contains(4));
    }

    public function testIndexOf()
    {
        $sequence = new Primitive([1, 2, 4]);

        $this->assertSame(
            1,
            $sequence->indexOf(2)->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
        $this->assertSame(
            2,
            $sequence->indexOf(4)->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
    }

    public function testReturnNothingWhenTryingToAccessIndexOfUnknownValue()
    {
        $this->assertNull(
            (new Primitive)->indexOf(1)->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
    }

    public function testIndices()
    {
        $a = new Primitive(['1', '2']);
        $b = $a->indices();

        $this->assertSame(['1', '2'], \iterator_to_array($a->iterator()));
        $this->assertInstanceOf(Primitive::class, $b);
        $this->assertSame([0, 1], \iterator_to_array($b->iterator()));
    }

    public function testIndicesOnEmptySequence()
    {
        $a = new Primitive;
        $b = $a->indices();

        $this->assertSame([], \iterator_to_array($a->iterator()));
        $this->assertInstanceOf(Primitive::class, $b);
        $this->assertSame([], \iterator_to_array($b->iterator()));
    }

    public function testMap()
    {
        $a = new Primitive([1, 2, 3]);
        $b = $a->map(static fn($i) => $i * 2);

        $this->assertSame([1, 2, 3], \iterator_to_array($a->iterator()));
        $this->assertInstanceOf(Primitive::class, $b);
        $this->assertSame([2, 4, 6], \iterator_to_array($b->iterator()));
    }

    public function testPad()
    {
        $a = new Primitive([1, 2]);
        $b = $a->pad(4, 0);
        $c = $a->pad(1, 0);

        $this->assertSame([1, 2], \iterator_to_array($a->iterator()));
        $this->assertInstanceOf(Primitive::class, $b);
        $this->assertInstanceOf(Primitive::class, $c);
        $this->assertSame([1, 2, 0, 0], \iterator_to_array($b->iterator()));
        $this->assertSame([1, 2], \iterator_to_array($c->iterator()));
    }

    public function testPartition()
    {
        $sequence = new Primitive([1, 2, 3, 4]);
        $partition = $sequence->partition(static fn($i) => $i % 2 === 0);

        $this->assertSame([1, 2, 3, 4], \iterator_to_array($sequence->iterator()));
        $this->assertInstanceOf(Map::class, $partition);
        $this->assertCount(2, $partition);
        $this->assertSame([2, 4], $this->get($partition, true)->toList());
        $this->assertSame([1, 3], $this->get($partition, false)->toList());
    }

    public function testSlice()
    {
        $a = new Primitive([2, 3, 4, 5]);
        $b = $a->slice(1, 3);

        $this->assertSame([2, 3, 4, 5], \iterator_to_array($a->iterator()));
        $this->assertInstanceOf(Primitive::class, $b);
        $this->assertSame([3, 4], \iterator_to_array($b->iterator()));
    }

    public function testTake()
    {
        $a = new Primitive([2, 3, 4]);
        $b = $a->take(2);

        $this->assertSame([2, 3, 4], \iterator_to_array($a->iterator()));
        $this->assertInstanceOf(Primitive::class, $b);
        $this->assertSame([2, 3], \iterator_to_array($b->iterator()));
    }

    public function testTakeEnd()
    {
        $a = new Primitive([2, 3, 4]);
        $b = $a->takeEnd(2);

        $this->assertSame([2, 3, 4], \iterator_to_array($a->iterator()));
        $this->assertInstanceOf(Primitive::class, $b);
        $this->assertSame([3, 4], \iterator_to_array($b->iterator()));
    }

    public function testAppend()
    {
        $a = new Primitive([1, 2]);
        $b = new Primitive([3, 4]);
        $c = $a->append($b);

        $this->assertSame([1, 2], \iterator_to_array($a->iterator()));
        $this->assertSame([3, 4], \iterator_to_array($b->iterator()));
        $this->assertInstanceOf(Primitive::class, $c);
        $this->assertSame([1, 2, 3, 4], \iterator_to_array($c->iterator()));
    }

    public function testIntersect()
    {
        $a = new Primitive([1, 2]);
        $b = new Primitive([2, 3]);
        $c = $a->intersect($b);

        $this->assertSame([1, 2], \iterator_to_array($a->iterator()));
        $this->assertSame([2, 3], \iterator_to_array($b->iterator()));
        $this->assertInstanceOf(Primitive::class, $c);
        $this->assertSame([2], \iterator_to_array($c->iterator()));
    }

    public function testAdd()
    {
        $a = new Primitive([1]);
        $b = ($a)(2);

        $this->assertSame([1], \iterator_to_array($a->iterator()));
        $this->assertInstanceOf(Primitive::class, $b);
        $this->assertSame([1, 2], \iterator_to_array($b->iterator()));
    }

    public function testSort()
    {
        $a = new Primitive([1, 4, 3, 2]);
        $b = $a->sort(static fn($a, $b) => $a > $b ? 1 : -1);

        $this->assertSame([1, 4, 3, 2], \iterator_to_array($a->iterator()));
        $this->assertInstanceOf(Primitive::class, $b);
        $this->assertSame([1, 2, 3, 4], \iterator_to_array($b->iterator()));
    }

    public function testReduce()
    {
        $sequence = new Primitive([1, 2, 3, 4]);

        $this->assertSame(10, $sequence->reduce(0, static fn($sum, $i) => $sum + $i));
    }

    public function testClear()
    {
        $a = new Primitive([1, 2]);
        $b = $a->clear();

        $this->assertSame([1, 2], \iterator_to_array($a->iterator()));
        $this->assertInstanceOf(Primitive::class, $b);
        $this->assertSame([], \iterator_to_array($b->iterator()));
    }

    public function testReverse()
    {
        $a = new Primitive([1, 2, 3, 4]);
        $b = $a->reverse();

        $this->assertSame([1, 2, 3, 4], \iterator_to_array($a->iterator()));
        $this->assertInstanceOf(Primitive::class, $b);
        $this->assertSame([4, 3, 2, 1], \iterator_to_array($b->iterator()));
    }

    public function testEmpty()
    {
        $this->assertTrue((new Primitive)->empty());
        $this->assertFalse((new Primitive([1]))->empty());
    }

    public function testFind()
    {
        $sequence = new Primitive([1, 2, 3]);

        $this->assertSame(
            1,
            $sequence->find(static fn($i) => $i === 1)->match(
                static fn($i) => $i,
                static fn() => null,
            ),
        );
        $this->assertSame(
            2,
            $sequence->find(static fn($i) => $i === 2)->match(
                static fn($i) => $i,
                static fn() => null,
            ),
        );
        $this->assertSame(
            3,
            $sequence->find(static fn($i) => $i === 3)->match(
                static fn($i) => $i,
                static fn() => null,
            ),
        );

        $this->assertNull(
            $sequence->find(static fn($i) => $i === 0)->match(
                static fn($i) => $i,
                static fn() => null,
            ),
        );
    }

    public function get($map, $index)
    {
        return $map->get($index)->match(
            static fn($value) => $value,
            static fn() => null,
        );
    }
}
