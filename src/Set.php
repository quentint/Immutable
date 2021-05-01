<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

use Innmind\Immutable\Exception\{
    NoElementMatchingPredicateFound,
};

/**
 * @template T
 */
final class Set implements \Countable
{
    /** @var Set\Implementation<T> */
    private Set\Implementation $implementation;

    private function __construct(Set\Implementation $implementation)
    {
        $this->implementation = $implementation;
    }

    /**
     * Add an element to the set
     *
     * Example:
     * <code>
     * Set::of('int')(1)(3)
     * </code>
     *
     * @param T $element
     *
     * @return self<T>
     */
    public function __invoke($element): self
    {
        $self = clone $this;
        $self->implementation = ($this->implementation)($element);

        return $self;
    }

    /**
     * @template V
     * @no-named-arguments
     *
     * @param V $values
     *
     * @return self<V>
     */
    public static function of(...$values): self
    {
        return new self(new Set\Primitive(...$values));
    }

    /**
     * It will load the values inside the generator only upon the first use
     * of the set
     *
     * Use this mode when the amount of data may not fit in memory
     *
     * @template V
     *
     * @param \Generator<V> $generator
     *
     * @return self<V>
     */
    public static function defer(\Generator $generator): self
    {
        return new self(Set\Defer::of($generator));
    }

    /**
     * It will call the given function every time a new operation is done on the
     * set. This means the returned structure may not be truly immutable as
     * between the calls the underlying source may change.
     *
     * Use this mode when calling to an external source (meaning IO bound) such
     * as parsing a file or calling an API
     *
     * @template V
     *
     * @param callable(): \Generator<V> $generator
     *
     * @return self<V>
     */
    public static function lazy(callable $generator): self
    {
        return new self(Set\Lazy::of($generator));
    }

    /**
     * @no-named-arguments
     *
     * @param mixed $values
     *
     * @return self<mixed>
     */
    public static function mixed(...$values): self
    {
        return new self(new Set\Primitive(...$values));
    }

    /**
     * @no-named-arguments
     *
     * @return self<int>
     */
    public static function ints(int ...$values): self
    {
        /** @var self<int> */
        $self = new self(new Set\Primitive(...$values));

        return $self;
    }

    /**
     * @no-named-arguments
     *
     * @return self<float>
     */
    public static function floats(float ...$values): self
    {
        /** @var self<float> */
        $self = new self(new Set\Primitive(...$values));

        return $self;
    }

    /**
     * @no-named-arguments
     *
     * @return self<string>
     */
    public static function strings(string ...$values): self
    {
        /** @var self<string> */
        $self = new self(new Set\Primitive(...$values));

        return $self;
    }

    /**
     * @no-named-arguments
     *
     * @return self<object>
     */
    public static function objects(object ...$values): self
    {
        /** @var self<object> */
        $self = new self(new Set\Primitive(...$values));

        return $self;
    }

    public function size(): int
    {
        return $this->implementation->size();
    }

    public function count(): int
    {
        return $this->implementation->size();
    }

    /**
     * Intersect this set with the given one
     *
     * @param self<T> $set
     *
     * @return self<T>
     */
    public function intersect(self $set): self
    {
        $newSet = clone $this;
        $newSet->implementation = $this->implementation->intersect(
            $set->implementation,
        );

        return $newSet;
    }

    /**
     * Add an element to the set
     *
     * @param T $element
     *
     * @return self<T>
     */
    public function add($element): self
    {
        return ($this)($element);
    }

    /**
     * Check if the set contains the given element
     *
     * @param T $element
     */
    public function contains($element): bool
    {
        return $this->implementation->contains($element);
    }

    /**
     * Remove the element from the set
     *
     * @param T $element
     *
     * @return self<T>
     */
    public function remove($element): self
    {
        $self = clone $this;
        $self->implementation = $this->implementation->remove($element);

        return $self;
    }

    /**
     * Return the diff between this set and the given one
     *
     * @param self<T> $set
     *
     * @return self<T>
     */
    public function diff(self $set): self
    {
        $self = clone $this;
        $self->implementation = $this->implementation->diff(
            $set->implementation,
        );

        return $self;
    }

    /**
     * Check if the given set is identical to this one
     *
     * @param self<T> $set
     */
    public function equals(self $set): bool
    {
        return $this->implementation->equals($set->implementation);
    }

    /**
     * Return all elements that satisfy the given predicate
     *
     * @param callable(T): bool $predicate
     *
     * @return self<T>
     */
    public function filter(callable $predicate): self
    {
        $set = clone $this;
        $set->implementation = $this->implementation->filter($predicate);

        return $set;
    }

    /**
     * Apply the given function to all elements of the set
     *
     * @param callable(T): void $function
     */
    public function foreach(callable $function): void
    {
        $this->implementation->foreach($function);
    }

    /**
     * Return a new map of pairs grouped by keys determined with the given
     * discriminator function
     *
     * @template D
     *
     * @param callable(T): D $discriminator
     *
     * @return Map<D, self<T>>
     */
    public function groupBy(callable $discriminator): Map
    {
        return $this->implementation->groupBy($discriminator);
    }

    /**
     * Return a new set by applying the given function to all elements
     *
     * @template S
     *
     * @param callable(T): S $function
     *
     * @return self<S>
     */
    public function map(callable $function): self
    {
        return new self($this->implementation->map($function));
    }

    /**
     * Merge all sets created by each value from the original set
     *
     * @template S
     *
     * @param callable(T): self<S> $map
     *
     * @return self<S>
     */
    public function flatMap(callable $map): self
    {
        /**
         * @psalm-suppress InvalidArgument
         * @psalm-suppress MixedArgument
         */
        return $this->reduce(
            self::of(),
            static fn(self $carry, $value) => $carry->merge($map($value)),
        );
    }

    /**
     * Return a sequence of 2 sets partitioned according to the given predicate
     *
     * @param callable(T): bool $predicate
     *
     * @return Map<bool, self<T>>
     */
    public function partition(callable $predicate): Map
    {
        return $this->implementation->partition($predicate);
    }

    /**
     * Return a sequence sorted with the given function
     *
     * @param callable(T, T): int $function
     *
     * @return Sequence<T>
     */
    public function sort(callable $function): Sequence
    {
        return $this->implementation->sort($function);
    }

    /**
     * Create a new set with elements of both sets
     *
     * @param self<T> $set
     *
     * @return self<T>
     */
    public function merge(self $set): self
    {
        $self = clone $this;
        $self->implementation = $this->implementation->merge(
            $set->implementation,
        );

        return $self;
    }

    /**
     * Reduce the set to a single value
     *
     * @template R
     *
     * @param R $carry
     * @param callable(R, T): R $reducer
     *
     * @return R
     */
    public function reduce($carry, callable $reducer)
    {
        return $this->implementation->reduce($carry, $reducer);
    }

    /**
     * Return a set of the same type but without any value
     *
     * @return self<T>
     */
    public function clear(): self
    {
        $self = clone $this;
        $self->implementation = $this->implementation->clear();

        return $self;
    }

    public function empty(): bool
    {
        return $this->implementation->empty();
    }

    /**
     * @param callable(T): bool $predicate
     *
     * @throws NoElementMatchingPredicateFound
     *
     * @return T
     */
    public function find(callable $predicate)
    {
        /** @var T */
        return $this->implementation->find($predicate);
    }

    /**
     * @param callable(T): bool $predicate
     */
    public function matches(callable $predicate): bool
    {
        /**
         * @psalm-suppress MixedArgument
         * @psalm-suppress MissingClosureParamType
         */
        return $this->reduce(
            true,
            static fn(bool $matches, $value): bool => $matches && $predicate($value),
        );
    }

    /**
     * @param callable(T): bool $predicate
     */
    public function any(callable $predicate): bool
    {
        try {
            $this->find($predicate);

            return true;
        } catch (NoElementMatchingPredicateFound $e) {
            return false;
        }
    }
}
