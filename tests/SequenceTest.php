<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable;

use Innmind\Immutable\{
    Sequence,
    Str,
    Set,
    Map,
    Monoid\Concat,
};
use PHPUnit\Framework\TestCase;

class SequenceTest extends TestCase
{
    public function testInterface()
    {
        $sequence = Sequence::of();

        $this->assertInstanceOf(\Countable::class, $sequence);
        $this->assertSame([], $sequence->toList());
    }

    public function testOf()
    {
        $this->assertTrue(
            Sequence::of(1, 2, 3)->equals(
                Sequence::of()
                    ->add(1)
                    ->add(2)
                    ->add(3),
            ),
        );
    }

    public function testDefer()
    {
        $loaded = false;
        $sequence = Sequence::defer((static function() use (&$loaded) {
            yield 1;
            yield 2;
            yield 3;
            $loaded = true;
        })());

        $this->assertInstanceOf(Sequence::class, $sequence);
        $this->assertFalse($loaded);
        $this->assertSame([1, 2, 3], $sequence->toList());
        $this->assertTrue($loaded);
    }

    public function testLazy()
    {
        $loaded = false;
        $sequence = Sequence::lazy(static function() use (&$loaded) {
            yield 1;
            yield 2;
            yield 3;
            $loaded = true;
        });

        $this->assertInstanceOf(Sequence::class, $sequence);
        $this->assertFalse($loaded);
        $this->assertSame([1, 2, 3], $sequence->toList());
        $this->assertTrue($loaded);
    }

    public function testMixed()
    {
        $sequence = Sequence::mixed(1, '2', 3);

        $this->assertInstanceOf(Sequence::class, $sequence);
        $this->assertSame([1, '2', 3], $sequence->toList());
    }

    public function testInts()
    {
        $sequence = Sequence::ints(1, 2, 3);

        $this->assertInstanceOf(Sequence::class, $sequence);
        $this->assertSame([1, 2, 3], $sequence->toList());
    }

    public function testFloats()
    {
        $sequence = Sequence::floats(1, 2, 3.2);

        $this->assertInstanceOf(Sequence::class, $sequence);
        $this->assertSame([1.0, 2.0, 3.2], $sequence->toList());
    }

    public function testStrings()
    {
        $sequence = Sequence::strings('1', '2', '3');

        $this->assertInstanceOf(Sequence::class, $sequence);
        $this->assertSame(['1', '2', '3'], $sequence->toList());
    }

    public function testObjects()
    {
        $a = new \stdClass;
        $b = new \stdClass;
        $c = new \stdClass;
        $sequence = Sequence::objects($a, $b, $c);

        $this->assertInstanceOf(Sequence::class, $sequence);
        $this->assertSame([$a, $b, $c], $sequence->toList());
    }

    public function testSize()
    {
        $this->assertSame(
            2,
            Sequence::of()
                ->add(1)
                ->add(2)
                ->size(),
        );
    }

    public function testCount()
    {
        $this->assertCount(
            2,
            Sequence::of()
                ->add(1)
                ->add(2),
        );
    }

    public function testGet()
    {
        $this->assertSame(
            1,
            $this->get(Sequence::of()->add(1), 0),
        );
    }

    public function testReturnNothingWhenGettingUnknownIndex()
    {
        $this->assertNull($this->get(Sequence::of(), 0));
    }

    public function testDiff()
    {
        $a = Sequence::of()
            ->add(1)
            ->add(2)
            ->add(3);
        $b = Sequence::of()
            ->add(3)
            ->add(4)
            ->add(5);
        $c = $a->diff($b);

        $this->assertInstanceOf(Sequence::class, $c);
        $this->assertNotSame($c, $a);
        $this->assertNotSame($c, $b);
        $this->assertSame([1, 2, 3], $a->toList());
        $this->assertSame([3, 4, 5], $b->toList());
        $this->assertSame([1, 2], $c->toList());
    }

    public function testDistinct()
    {
        $a = Sequence::of()
            ->add(1)
            ->add(1)
            ->add(1);
        $b = $a->distinct();

        $this->assertInstanceOf(Sequence::class, $b);
        $this->assertNotSame($a, $b);
        $this->assertSame([1, 1, 1], $a->toList());
        $this->assertSame([1], $b->toList());
    }

    public function testDrop()
    {
        $a = Sequence::of()
            ->add(1)
            ->add(3)
            ->add(5);
        $b = $a->drop(2);

        $this->assertInstanceOf(Sequence::class, $b);
        $this->assertNotSame($a, $b);
        $this->assertSame([1, 3, 5], $a->toList());
        $this->assertSame([5], $b->toList());
    }

    public function testDropEnd()
    {
        $a = Sequence::of()
            ->add(1)
            ->add(3)
            ->add(5);
        $b = $a->dropEnd(2);

        $this->assertInstanceOf(Sequence::class, $b);
        $this->assertNotSame($a, $b);
        $this->assertSame([1, 3, 5], $a->toList());
        $this->assertSame([1], $b->toList());
    }

    public function testEquals()
    {
        $a = Sequence::of()
            ->add(1)
            ->add(3)
            ->add(5);
        $b = Sequence::of()
            ->add(1)
            ->add(5);
        $c = Sequence::of()
            ->add(1)
            ->add(3)
            ->add(5);

        $this->assertTrue($a->equals($c));
        $this->assertTrue($a->equals($a));
        $this->assertFalse($a->equals($b));
    }

    public function testFilter()
    {
        $a = Sequence::of()
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);
        $b = $a->filter(static function(int $value): bool {
            return $value % 2 === 0;
        });

        $this->assertInstanceOf(Sequence::class, $b);
        $this->assertNotSame($a, $b);
        $this->assertSame([1, 2, 3, 4], $a->toList());
        $this->assertSame([2, 4], $b->toList());
    }

    public function testForeach()
    {
        $sum = 0;
        $sequence = Sequence::of()
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4)
            ->foreach(static function(int $value) use (&$sum) {
                $sum += $value;
            });

        $this->assertSame(10, $sum);
    }

    public function testGroupBy()
    {
        $sequence = Sequence::of()
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);
        $map = $sequence->groupBy(static function(int $value): int {
            return $value % 3;
        });

        $this->assertInstanceOf(Map::class, $map);
        $this->assertCount(3, $map);
        $this->assertSame([3], $this->get($map, 0)->toList());
        $this->assertSame([1, 4], $this->get($map, 1)->toList());
        $this->assertSame([2], $this->get($map, 2)->toList());
    }

    public function testGroupEmptySequence()
    {
        $this->assertTrue(
            Sequence::of()
                ->groupBy(static function() {})
                ->equals(Map::of()),
        );
    }

    public function testFirst()
    {
        $sequence = Sequence::of()
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);

        $this->assertSame(
            1,
            $sequence->first()->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
    }

    public function testLast()
    {
        $sequence = Sequence::of()
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);

        $this->assertSame(
            4,
            $sequence->last()->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
    }

    public function testContains()
    {
        $sequence = Sequence::of()
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);

        $this->assertTrue($sequence->contains(2));
        $this->assertFalse($sequence->contains(5));
    }

    public function testIndexOf()
    {
        $sequence = Sequence::of()
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);

        $this->assertSame(
            0,
            $sequence->indexOf(1)->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
        $this->assertSame(
            3,
            $sequence->indexOf(4)->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
    }

    public function testIndices()
    {
        $sequence = Sequence::of()
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);
        $indices = $sequence->indices();

        $this->assertInstanceOf(Sequence::class, $indices);
        $this->assertSame([0, 1, 2, 3], $indices->toList());
    }

    public function testEmptyIndices()
    {
        $sequence = Sequence::of();
        $indices = $sequence->indices();

        $this->assertInstanceOf(Sequence::class, $indices);
        $this->assertSame([], $indices->toList());
    }

    public function testMap()
    {
        $a = Sequence::of()
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);
        $b = $a->map(static function(int $value): int {
            return $value**2;
        });

        $this->assertInstanceOf(Sequence::class, $b);
        $this->assertNotSame($a, $b);
        $this->assertSame([1, 2, 3, 4], $a->toList());
        $this->assertSame([1, 4, 9, 16], $b->toList());
    }

    public function testFlatMap()
    {
        $sequence = Sequence::of(1, 2, 3, 4);
        $sequence2 = $sequence->flatMap(static fn($i) => Sequence::of($i, $i));

        $this->assertNotSame($sequence, $sequence2);
        $this->assertSame([1, 2, 3, 4], $sequence->toList());
        $this->assertSame([1, 1, 2, 2, 3, 3, 4, 4], $sequence2->toList());
    }

    public function testLazyFlatMap()
    {
        $loaded = false;
        $a = Sequence::lazy(static function() use (&$loaded) {
            yield 1;
            yield 2;
            yield 3;
            yield 4;
            $loaded = true;
        });
        $b = $a->flatMap(static fn($i) => Sequence::of($i, $i*2));

        $this->assertFalse($loaded);
        $this->assertSame([1, 2, 2, 4, 3, 6, 4, 8], $b->toList());
        $this->assertTrue($loaded);
        $this->assertSame([1, 2, 3, 4], $a->toList());
    }

    public function testDeferFlatMap()
    {
        $loaded = false;
        $a = Sequence::defer((static function() use (&$loaded) {
            yield 1;
            yield 2;
            yield 3;
            yield 4;
            $loaded = true;
        })());
        $b = $a->flatMap(static fn($i) => Sequence::of($i, $i*2));

        $this->assertFalse($loaded);
        $this->assertSame([1, 2, 2, 4, 3, 6, 4, 8], $b->toList());
        $this->assertTrue($loaded);
        $this->assertSame([1, 2, 3, 4], $a->toList());
    }

    public function testPad()
    {
        $a = Sequence::of()
            ->add(1)
            ->add(2);
        $b = $a->pad(4, 0);

        $this->assertInstanceOf(Sequence::class, $b);
        $this->assertNotSame($a, $b);
        $this->assertSame([1, 2], $a->toList());
        $this->assertSame([1, 2, 0, 0], $b->toList());
    }

    public function testPartition()
    {
        $map = Sequence::of()
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4)
            ->partition(static function(int $value): bool {
                return $value % 2 === 0;
            });

        $this->assertInstanceOf(Map::class, $map);
        $this->assertSame([2, 4], $this->get($map, true)->toList());
        $this->assertSame([1, 3], $this->get($map, false)->toList());
    }

    public function testSlice()
    {
        $a = Sequence::of()
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);
        $b = $a->slice(1, 3);

        $this->assertInstanceOf(Sequence::class, $b);
        $this->assertNotSame($a, $b);
        $this->assertSame([1, 2, 3, 4], $a->toList());
        $this->assertSame([2, 3], $b->toList());
    }

    public function testTake()
    {
        $a = Sequence::of()
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);
        $b = $a->take(2);

        $this->assertInstanceOf(Sequence::class, $b);
        $this->assertNotSame($a, $b);
        $this->assertSame([1, 2, 3, 4], $a->toList());
        $this->assertSame([1, 2], $b->toList());
    }

    public function testTakeEnd()
    {
        $a = Sequence::of()
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);
        $b = $a->takeEnd(2);

        $this->assertInstanceOf(Sequence::class, $b);
        $this->assertNotSame($a, $b);
        $this->assertSame([1, 2, 3, 4], $a->toList());
        $this->assertSame([3, 4], $b->toList());
    }

    public function testAppend()
    {
        $a = Sequence::of()
            ->add(1)
            ->add(2);
        $b = Sequence::of()
            ->add(3)
            ->add(4);
        $c = $b->append($a);

        $this->assertInstanceOf(Sequence::class, $c);
        $this->assertNotSame($c, $a);
        $this->assertNotSame($c, $b);
        $this->assertSame([1, 2], $a->toList());
        $this->assertSame([3, 4], $b->toList());
        $this->assertSame([3, 4, 1, 2], $c->toList());
    }

    public function testIntersect()
    {
        $a = Sequence::of()
            ->add(1)
            ->add(2);
        $b = Sequence::of()
            ->add(2)
            ->add(3);
        $c = $b->intersect($a);

        $this->assertInstanceOf(Sequence::class, $c);
        $this->assertNotSame($c, $a);
        $this->assertNotSame($c, $b);
        $this->assertSame([1, 2], $a->toList());
        $this->assertSame([2, 3], $b->toList());
        $this->assertSame([2], $c->toList());
    }

    public function testAdd()
    {
        $a = Sequence::of();
        $b = $a->add(1);

        $this->assertInstanceOf(Sequence::class, $b);
        $this->assertNotSame($a, $b);
        $this->assertSame([], $a->toList());
        $this->assertSame([1], $b->toList());

        $this->assertSame(
            [1, 2, 3],
            Sequence::ints(1)(2)(3)->toList(),
        );
    }

    public function testSort()
    {
        $a = Sequence::of()
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(3)
            ->add(4);
        $b = $a->sort(static function(int $a, int $b): int {
            return ($b > $a) ? 1 : -1;
        });

        $this->assertInstanceOf(Sequence::class, $b);
        $this->assertNotSame($a, $b);
        $this->assertSame([1, 2, 3, 3, 4], $a->toList());
        $this->assertSame([4, 3, 3, 2, 1], $b->toList());
    }

    public function testReduce()
    {
        $value = Sequence::of()
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4)
            ->reduce(
                0,
                static function(int $carry, int $value): int {
                    return $carry + $value;
                },
            );

        $this->assertSame(10, $value);
    }

    public function testClear()
    {
        $sequence = Sequence::of()
            ->add(1)
            ->add(2)
            ->add(3);
        $sequence2 = $sequence->clear();

        $this->assertNotSame($sequence, $sequence2);
        $this->assertSame([1, 2, 3], $sequence->toList());
        $this->assertSame([], $sequence2->toList());
    }

    public function testReverse()
    {
        $sequence = Sequence::of()
            ->add(1)
            ->add(3)
            ->add(4)
            ->add(2);
        $reverse = $sequence->reverse();

        $this->assertInstanceOf(Sequence::class, $reverse);
        $this->assertNotSame($sequence, $reverse);
        $this->assertSame([1, 3, 4, 2], $sequence->toList());
        $this->assertSame([2, 4, 3, 1], $reverse->toList());
    }

    public function testEmpty()
    {
        $this->assertTrue(Sequence::of()->empty());
        $this->assertFalse(Sequence::of(1)->empty());
    }

    public function testToList()
    {
        $this->assertSame(
            [1, 2, 3],
            Sequence::ints(1, 2, 3)->toList(),
        );
    }

    public function testFind()
    {
        $sequence = Sequence::ints(1, 2, 3);

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

    public function testMatches()
    {
        $sequence = Sequence::ints(1, 2, 3);

        $this->assertTrue($sequence->matches(static fn($i) => $i % 1 === 0));
        $this->assertFalse($sequence->matches(static fn($i) => $i % 2 === 0));
    }

    public function testAny()
    {
        $sequence = Sequence::ints(1, 2, 3);

        $this->assertTrue($sequence->any(static fn($i) => $i === 2));
        $this->assertFalse($sequence->any(static fn($i) => $i === 0));
    }

    public function testPossibilityToCleanupResourcesWhenGeneratorStoppedBeforeEnd()
    {
        $cleanupCalled = false;
        $endReached = false;
        $started = 0;
        $sequence = Sequence::lazy(static function($registerCleanup) use (&$cleanupCalled, &$endReached, &$started) {
            ++$started;
            $file = \fopen(__FILE__, 'r');
            $registerCleanup(static function() use ($file, &$cleanupCalled) {
                \fclose($file);
                $cleanupCalled = true;
            });

            while (!\feof($file)) {
                $line = \fgets($file);

                yield $line;
            }

            $endReached = true;
            \fclose($file);
        });

        $line = $sequence
            ->map(static fn($line) => \trim($line))
            ->filter(static fn($line) => $line !== '')
            ->find(static fn($line) => \substr($line, -2) === '()')
            ->match(
                static fn($line) => $line,
                static fn() => null,
            );

        $this->assertSame('public function testInterface()', $line);
        $this->assertSame(1, $started);
        $this->assertTrue($cleanupCalled);
        $this->assertFalse($endReached);
    }

    public function testCleanupIsNotCalledWhenReachingTheEndOfTheGenerator()
    {
        $cleanupCalled = false;
        $endReached = false;
        $started = 0;
        $sequence = Sequence::lazy(static function($registerCleanup) use (&$cleanupCalled, &$endReached, &$started) {
            ++$started;
            $file = \fopen(__FILE__, 'r');
            $registerCleanup(static function() use ($file, &$cleanupCalled) {
                \fclose($file);
                $cleanupCalled = true;
            });

            while (!\feof($file)) {
                $line = \fgets($file);

                yield $line;
            }

            $endReached = true;
            \fclose($file);
        });

        $line = $sequence
            ->filter(static fn($line) => \is_string($line))
            ->map(static fn($line) => \trim($line))
            ->filter(static fn($line) => $line !== '')
            ->find(static fn($line) => $line === 'unknown')
            ->match(
                static fn($line) => $line,
                static fn() => null,
            );

        $this->assertNull($line);
        $this->assertSame(1, $started);
        $this->assertFalse($cleanupCalled);
        $this->assertTrue($endReached);
    }

    public function testMatch()
    {
        $sequence = Sequence::of(1, 2, 3, 4);
        [$head, $tail] = $sequence->match(
            static fn($head, $tail) => [$head, $tail],
            static fn() => [null, null],
        );

        $this->assertSame(1, $head);
        $this->assertTrue($tail->equals(Sequence::of(2, 3, 4)));
        $this->assertSame([1, 2, 3, 4], $sequence->toList());
    }

    public function testDeferredMatch()
    {
        $sequence = Sequence::defer((static function() {
            yield 1;
            yield 2;
            yield 3;
            yield 4;
        })());
        [$head, $tail] = $sequence->match(
            static fn($head, $tail) => [$head, $tail],
            static fn() => [null, null],
        );

        $this->assertSame(1, $head);
        $this->assertTrue($tail->equals(Sequence::of(2, 3, 4)));
        $this->assertSame([1, 2, 3, 4], $sequence->toList());
    }

    public function testLazyMatch()
    {
        $started = 0;
        $sequence = Sequence::lazy(static function() use (&$started) {
            ++$started;
            yield 1;
            yield 2;
            yield 3;
            yield 4;
        });
        [$head, $tail] = $sequence->match(
            static fn($head, $tail) => [$head, $tail],
            static fn() => [null, null],
        );

        $this->assertSame(1, $head);
        $this->assertTrue($tail->equals(Sequence::of(2, 3, 4)));
        $this->assertSame(1, $started);
        $this->assertSame([1, 2, 3, 4], $sequence->toList());
    }

    public function testFold()
    {
        $str = Sequence::of(Str::of('foo'), Str::of('bar'), Str::of('baz'))->fold(new Concat);

        $this->assertInstanceOf(Str::class, $str);
        $this->assertSame('foobarbaz', $str->toString());

        $str = Sequence::of(Str::of('baz'), Str::of('foo'), Str::of('bar'))->fold(new Concat);

        $this->assertInstanceOf(Str::class, $str);
        $this->assertSame('bazfoobar', $str->toString());
    }

    public function get($map, $index)
    {
        return $map->get($index)->match(
            static fn($value) => $value,
            static fn() => null,
        );
    }
}
