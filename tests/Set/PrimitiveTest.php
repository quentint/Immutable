<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\Set;

use Innmind\Immutable\{
    Set\Primitive,
    Set\Implementation,
    Set,
    Map,
    Str,
    Sequence,
};
use function Innmind\Immutable\unwrap;
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
        $this->assertSame(2, (new Primitive(1, 2))->size());
        $this->assertSame(2, (new Primitive(1, 2))->count());
    }

    public function testIterator()
    {
        $this->assertSame([1, 2], \iterator_to_array((new Primitive(1, 2))->iterator()));
    }

    public function testIntersect()
    {
        $a = new Primitive(1, 2);
        $b = new Primitive(2, 3);
        $c = $a->intersect($b);

        $this->assertSame([1, 2], \iterator_to_array($a->iterator()));
        $this->assertSame([2, 3], \iterator_to_array($b->iterator()));
        $this->assertInstanceOf(Primitive::class, $c);
        $this->assertSame([2], \iterator_to_array($c->iterator()));
    }

    public function testAdd()
    {
        $a = new Primitive(1);
        $b = ($a)(2);

        $this->assertSame([1], \iterator_to_array($a->iterator()));
        $this->assertInstanceOf(Primitive::class, $b);
        $this->assertSame([1, 2], \iterator_to_array($b->iterator()));
        $this->assertSame($b, ($b)(2));
    }

    public function testContains()
    {
        $set = new Primitive(1);

        $this->assertTrue($set->contains(1));
        $this->assertFalse($set->contains(2));
    }

    public function testRemove()
    {
        $a = new Primitive(1, 2, 3, 4);
        $b = $a->remove(3);

        $this->assertSame([1, 2, 3, 4], \iterator_to_array($a->iterator()));
        $this->assertInstanceOf(Primitive::class, $b);
        $this->assertSame([1, 2, 4], \iterator_to_array($b->iterator()));
        $this->assertSame($a, $a->remove(5));
    }

    public function testDiff()
    {
        $a = new Primitive(1, 2, 3);
        $b = new Primitive(2, 4);
        $c = $a->diff($b);

        $this->assertSame([1, 2, 3], \iterator_to_array($a->iterator()));
        $this->assertSame([2, 4], \iterator_to_array($b->iterator()));
        $this->assertInstanceOf(Primitive::class, $c);
        $this->assertSame([1, 3], \iterator_to_array($c->iterator()));
    }

    public function testEquals()
    {
        $this->assertTrue((new Primitive(1, 2))->equals(new Primitive(1, 2)));
        $this->assertFalse((new Primitive(1, 2))->equals(new Primitive(1)));
        $this->assertFalse((new Primitive(1, 2))->equals(new Primitive(1, 2, 3)));
    }

    public function testFilter()
    {
        $a = new Primitive(1, 2, 3, 4);
        $b = $a->filter(static fn($i) => $i % 2 === 0);

        $this->assertSame([1, 2, 3, 4], \iterator_to_array($a->iterator()));
        $this->assertInstanceOf(Primitive::class, $b);
        $this->assertSame([2, 4], \iterator_to_array($b->iterator()));
    }

    public function testForeach()
    {
        $set = new Primitive(1, 2, 3, 4);
        $calls = 0;
        $sum = 0;

        $this->assertNull($set->foreach(static function($i) use (&$calls, &$sum) {
            ++$calls;
            $sum += $i;
        }));

        $this->assertSame(4, $calls);
        $this->assertSame(10, $sum);
    }

    public function testGroupBy()
    {
        $set = new Primitive(1, 2, 3, 4);
        $groups = $set->groupBy(static fn($i) => $i % 2);

        $this->assertSame([1, 2, 3, 4], \iterator_to_array($set->iterator()));
        $this->assertInstanceOf(Map::class, $groups);
        $this->assertCount(2, $groups);
        $this->assertSame([2, 4], unwrap($this->get($groups, 0)));
        $this->assertSame([1, 3], unwrap($this->get($groups, 1)));
    }

    public function testMap()
    {
        $a = new Primitive(1, 2, 3);
        $b = $a->map(static fn($i) => $i * 2);

        $this->assertSame([1, 2, 3], \iterator_to_array($a->iterator()));
        $this->assertInstanceOf(Primitive::class, $b);
        $this->assertSame([2, 4, 6], \iterator_to_array($b->iterator()));
    }

    public function testPartition()
    {
        $set = new Primitive(1, 2, 3, 4);
        $groups = $set->partition(static fn($i) => $i % 2 === 0);

        $this->assertSame([1, 2, 3, 4], \iterator_to_array($set->iterator()));
        $this->assertInstanceOf(Map::class, $groups);
        $this->assertCount(2, $groups);
        $this->assertSame([2, 4], unwrap($this->get($groups, true)));
        $this->assertSame([1, 3], unwrap($this->get($groups, false)));
    }

    public function testSort()
    {
        $set = new Primitive(1, 4, 3, 2);
        $sorted = $set->sort(static fn($a, $b) => $a > $b ? 1 : -1);

        $this->assertSame([1, 4, 3, 2], \iterator_to_array($set->iterator()));
        $this->assertInstanceOf(Sequence::class, $sorted);
        $this->assertSame([1, 2, 3, 4], unwrap($sorted));
    }

    public function testMerge()
    {
        $a = new Primitive(1, 2);
        $b = new Primitive(2, 3);
        $c = $a->merge($b);

        $this->assertSame([1, 2], \iterator_to_array($a->iterator()));
        $this->assertSame([2, 3], \iterator_to_array($b->iterator()));
        $this->assertInstanceOf(Primitive::class, $c);
        $this->assertSame([1, 2, 3], \iterator_to_array($c->iterator()));
    }

    public function testReduce()
    {
        $set = new Primitive(1, 2, 3, 4);

        $this->assertSame(10, $set->reduce(0, static fn($sum, $i) => $sum + $i));
    }

    public function testClear()
    {
        $a = new Primitive(1);
        $b = $a->clear();

        $this->assertSame([1], \iterator_to_array($a->iterator()));
        $this->assertInstanceOf(Primitive::class, $b);
        $this->assertSame([], \iterator_to_array($b->iterator()));
    }

    public function testEmpty()
    {
        $this->assertTrue((new Primitive)->empty());
        $this->assertFalse((new Primitive(1))->empty());
    }

    public function testFind()
    {
        $sequence = new Primitive(1, 2, 3);

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
