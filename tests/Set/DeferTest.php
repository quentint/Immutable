<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\Set;

use Innmind\Immutable\{
    Set\Defer,
    Set\Implementation,
    Set,
    Map,
    Str,
    Sequence,
    SideEffect,
};
use PHPUnit\Framework\TestCase;

class DeferTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Implementation::class,
            Defer::of((static function() {
                yield;
            })()),
        );
    }

    public function testSize()
    {
        $set = Defer::of((static function() {
            yield 1;
            yield 2;
        })());

        $this->assertSame(2, $set->size());
        $this->assertSame(2, $set->count());
    }

    public function testIterator()
    {
        $set = Defer::of((static function() {
            yield 1;
            yield 2;
        })());

        $this->assertSame([1, 2], \iterator_to_array($set->iterator()));
    }

    public function testIntersect()
    {
        $aLoaded = false;
        $bLoaded = false;
        $a = Defer::of((static function() use (&$aLoaded) {
            yield 1;
            yield 2;
            $aLoaded = true;
        })());
        $b = Defer::of((static function() use (&$bLoaded) {
            yield 2;
            yield 3;
            $bLoaded = true;
        })());
        $c = $a->intersect($b);

        $this->assertFalse($aLoaded);
        $this->assertFalse($bLoaded);
        $this->assertSame([1, 2], \iterator_to_array($a->iterator()));
        $this->assertSame([2, 3], \iterator_to_array($b->iterator()));
        $this->assertInstanceOf(Defer::class, $c);
        $this->assertSame([2], \iterator_to_array($c->iterator()));
        $this->assertTrue($aLoaded);
        $this->assertTrue($bLoaded);
    }

    public function testAdd()
    {
        $loaded = false;
        $a = Defer::of((static function() use (&$loaded) {
            yield 1;
            $loaded = true;
        })());
        $b = ($a)(2);

        $this->assertFalse($loaded);
        $this->assertSame([1], \iterator_to_array($a->iterator()));
        $this->assertInstanceOf(Defer::class, $b);
        $this->assertSame([1, 2], \iterator_to_array($b->iterator()));
        $this->assertSame([1, 2], \iterator_to_array(($b)(2)->iterator()));
        $this->assertTrue($loaded);
    }

    public function testContains()
    {
        $set = Defer::of((static function() {
            yield 1;
        })());

        $this->assertTrue($set->contains(1));
        $this->assertFalse($set->contains(2));
    }

    public function testRemove()
    {
        $a = Defer::of((static function() {
            yield 1;
            yield 2;
            yield 3;
            yield 4;
        })());
        $b = $a->remove(3);

        $this->assertSame([1, 2, 3, 4], \iterator_to_array($a->iterator()));
        $this->assertInstanceOf(Defer::class, $b);
        $this->assertSame([1, 2, 4], \iterator_to_array($b->iterator()));
        $this->assertSame($a, $a->remove(5));
    }

    public function testDiff()
    {
        $aLoaded = false;
        $a = Defer::of((static function() use (&$aLoaded) {
            yield 1;
            yield 2;
            yield 3;
            $aLoaded = true;
        })());
        $bLoaded = false;
        $b = Defer::of((static function() use (&$bLoaded) {
            yield 2;
            yield 4;
            $bLoaded = true;
        })());
        $c = $a->diff($b);

        $this->assertFalse($aLoaded);
        $this->assertFalse($bLoaded);
        $this->assertSame([1, 2, 3], \iterator_to_array($a->iterator()));
        $this->assertSame([2, 4], \iterator_to_array($b->iterator()));
        $this->assertInstanceOf(Defer::class, $c);
        $this->assertSame([1, 3], \iterator_to_array($c->iterator()));
        $this->assertTrue($aLoaded);
        $this->assertTrue($bLoaded);
    }

    public function testEquals()
    {
        $a = Defer::of((static function() {
            yield 1;
            yield 2;
        })());
        $aBis = Defer::of((static function() {
            yield 1;
            yield 2;
        })());
        $b = Defer::of((static function() {
            yield 1;
        })());
        $c = Defer::of((static function() {
            yield 1;
            yield 2;
            yield 3;
        })());

        $this->assertTrue($a->equals($a));
        $this->assertTrue($a->equals($aBis));
        $this->assertFalse($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    public function testFilter()
    {
        $loaded = false;
        $a = Defer::of((static function() use (&$loaded) {
            yield 1;
            yield 2;
            yield 3;
            yield 4;
            $loaded = true;
        })());
        $b = $a->filter(static fn($i) => $i % 2 === 0);

        $this->assertFalse($loaded);
        $this->assertSame([1, 2, 3, 4], \iterator_to_array($a->iterator()));
        $this->assertInstanceOf(Defer::class, $b);
        $this->assertSame([2, 4], \iterator_to_array($b->iterator()));
        $this->assertTrue($loaded);
    }

    public function testForeach()
    {
        $set = Defer::of((static function() {
            yield 1;
            yield 2;
            yield 3;
            yield 4;
        })());
        $calls = 0;
        $sum = 0;

        $this->assertInstanceOf(
            SideEffect::class,
            $set->foreach(static function($i) use (&$calls, &$sum) {
                ++$calls;
                $sum += $i;
            }),
        );

        $this->assertSame(4, $calls);
        $this->assertSame(10, $sum);
    }

    public function testGroupBy()
    {
        $set = Defer::of((static function() {
            yield 1;
            yield 2;
            yield 3;
            yield 4;
        })());
        $groups = $set->groupBy(static fn($i) => $i % 2);

        $this->assertSame([1, 2, 3, 4], \iterator_to_array($set->iterator()));
        $this->assertInstanceOf(Map::class, $groups);
        $this->assertCount(2, $groups);
        $this->assertSame([2, 4], $this->get($groups, 0)->toList());
        $this->assertSame([1, 3], $this->get($groups, 1)->toList());
    }

    public function testMap()
    {
        $loaded = false;
        $a = Defer::of((static function() use (&$loaded) {
            yield 1;
            yield 2;
            yield 3;
            $loaded = true;
        })());
        $b = $a->map(static fn($i) => $i * 2);

        $this->assertFalse($loaded);
        $this->assertSame([1, 2, 3], \iterator_to_array($a->iterator()));
        $this->assertInstanceOf(Defer::class, $b);
        $this->assertSame([2, 4, 6], \iterator_to_array($b->iterator()));
        $this->assertTrue($loaded);
    }

    public function testMapDoesntIntroduceDuplicates()
    {
        $set = Defer::of((static function() {
            yield 1;
            yield 2;
            yield 3;
        })());

        $this->assertSame(
            [1],
            \iterator_to_array($set->map(static fn() => 1)->iterator()),
        );
    }

    public function testPartition()
    {
        $set = Defer::of((static function() {
            yield 1;
            yield 2;
            yield 3;
            yield 4;
        })());
        $groups = $set->partition(static fn($i) => $i % 2 === 0);

        $this->assertSame([1, 2, 3, 4], \iterator_to_array($set->iterator()));
        $this->assertInstanceOf(Map::class, $groups);
        $this->assertCount(2, $groups);
        $this->assertSame([2, 4], $this->get($groups, true)->toList());
        $this->assertSame([1, 3], $this->get($groups, false)->toList());
    }

    public function testSort()
    {
        $loaded = false;
        $set = Defer::of((static function() use (&$loaded) {
            yield 1;
            yield 4;
            yield 3;
            yield 2;
            $loaded = true;
        })());
        $sorted = $set->sort(static fn($a, $b) => $a > $b ? 1 : -1);

        $this->assertFalse($loaded);
        $this->assertSame([1, 4, 3, 2], \iterator_to_array($set->iterator()));
        $this->assertInstanceOf(Sequence::class, $sorted);
        $this->assertSame([1, 2, 3, 4], $sorted->toList());
        $this->assertTrue($loaded);
    }

    public function testMerge()
    {
        $aLoaded = false;
        $a = Defer::of((static function() use (&$aLoaded) {
            yield 1;
            yield 2;
            $aLoaded = true;
        })());
        $bLoaded = false;
        $b = Defer::of((static function() use (&$bLoaded) {
            yield 2;
            yield 3;
            $bLoaded = true;
        })());
        $c = $a->merge($b);

        $this->assertFalse($aLoaded);
        $this->assertFalse($bLoaded);
        $this->assertSame([1, 2], \iterator_to_array($a->iterator()));
        $this->assertSame([2, 3], \iterator_to_array($b->iterator()));
        $this->assertInstanceOf(Defer::class, $c);
        $this->assertSame([1, 2, 3], \iterator_to_array($c->iterator()));
        $this->assertTrue($aLoaded);
        $this->assertTrue($bLoaded);
    }

    public function testReduce()
    {
        $set = Defer::of((static function() {
            yield 1;
            yield 2;
            yield 3;
            yield 4;
        })());

        $this->assertSame(10, $set->reduce(0, static fn($sum, $i) => $sum + $i));
    }

    public function testClear()
    {
        $a = Defer::of((static function() {
            yield 1;
        })());
        $b = $a->clear();

        $this->assertSame([1], \iterator_to_array($a->iterator()));
        $this->assertInstanceOf(Implementation::class, $b);
        $this->assertSame([], \iterator_to_array($b->iterator()));
    }

    public function testEmpty()
    {
        $a = Defer::of((static function() {
            yield 1;
        })());
        $b = Defer::of((static function() {
            if (false) {
                yield 1;
            }
        })());

        $this->assertTrue($b->empty());
        $this->assertFalse($a->empty());
    }

    public function testFind()
    {
        $count = 0;
        $sequence = Defer::of((static function() use (&$count) {
            ++$count;
            yield 1;
            ++$count;
            yield 2;
            ++$count;
            yield 3;
        })());

        $this->assertSame(
            1,
            $sequence->find(static fn($i) => $i === 1)->match(
                static fn($i) => $i,
                static fn() => null,
            ),
        );
        $this->assertSame(1, $count);
        $this->assertSame(
            2,
            $sequence->find(static fn($i) => $i === 2)->match(
                static fn($i) => $i,
                static fn() => null,
            ),
        );
        $this->assertSame(2, $count);
        $this->assertSame(
            3,
            $sequence->find(static fn($i) => $i === 3)->match(
                static fn($i) => $i,
                static fn() => null,
            ),
        );
        $this->assertSame(3, $count);

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
