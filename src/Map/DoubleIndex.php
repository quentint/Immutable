<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Map;

use Innmind\Immutable\{
    MapInterface,
    Map,
    Type,
    Str,
    Stream,
    StreamInterface,
    SetInterface,
    Set,
    Pair,
    Exception\InvalidArgumentException,
    Exception\LogicException,
    Exception\ElementNotFoundException,
    Exception\GroupEmptyMapException
};

/**
 * {@inheritdoc}
 */
final class DoubleIndex implements MapInterface
{
    private $keyType;
    private $valueType;
    private $keySpecification;
    private $valueSpecification;
    private $keys;
    private $values;
    private $pairs;

    /**
     * {@inheritdoc}
     */
    public function __construct(string $keyType, string $valueType)
    {
        $this->keySpecification = Type::of($keyType);
        $this->valueSpecification = Type::of($valueType);
        $this->keyType = new Str($keyType);
        $this->valueType = new Str($valueType);
        $this->keys = new Stream($keyType);
        $this->values = new Stream($valueType);
        $this->pairs = new Stream(Pair::class);
    }

    /**
     * {@inheritdoc}
     */
    public function keyType(): Str
    {
        return $this->keyType;
    }

    /**
     * {@inheritdoc}
     */
    public function valueType(): Str
    {
        return $this->valueType;
    }

    /**
     * {@inheritdoc}
     */
    public function size(): int
    {
        return $this->keys->size();
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return $this->keys->count();
    }

    /**
     * {@inheritdoc}
     */
    public function put($key, $value): MapInterface
    {
        $this->keySpecification->validate($key);
        $this->valueSpecification->validate($value);

        $map = clone $this;

        if ($this->keys->contains($key)) {
            $index = $this->keys->indexOf($key);
            $map->values = $this->values->take($index)
                ->add($value)
                ->append($this->values->drop($index + 1));
            $map->pairs = $this->pairs->take($index)
                ->add(new Pair($key, $value))
                ->append($this->pairs->drop($index + 1));
        } else {
            $map->keys = $this->keys->add($key);
            $map->values = $this->values->add($value);
            $map->pairs = $this->pairs->add(new Pair($key, $value));
        }

        return $map;
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        if (!$this->keys->contains($key)) {
            throw new ElementNotFoundException;
        }

        return $this->values->get(
            $this->keys->indexOf($key)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function contains($key): bool
    {
        return $this->keys->contains($key);
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): MapInterface
    {
        $map = clone $this;
        $map->keys = $this->keys->clear();
        $map->values = $this->values->clear();
        $map->pairs = $this->pairs->clear();

        return $map;
    }

    /**
     * {@inheritdoc}
     */
    public function equals(MapInterface $map): bool
    {
        if (!$map->keys()->equals($this->keys())) {
            return false;
        }

        foreach ($this->pairs->toArray() as $pair) {
            if ($map->get($pair->key()) !== $pair->value()) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function filter(callable $predicate): MapInterface
    {
        $map = $this->clear();

        foreach ($this->pairs->toArray() as $pair) {
            if ($predicate($pair->key(), $pair->value()) === true) {
                $map->keys = $map->keys->add($pair->key());
                $map->values = $map->values->add($pair->value());
                $map->pairs = $map->pairs->add($pair);
            }
        }

        return $map;
    }

    /**
     * {@inheritdoc}
     */
    public function foreach(callable $function): MapInterface
    {
        foreach ($this->pairs->toArray() as $pair) {
            $function($pair->key(), $pair->value());
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function groupBy(callable $discriminator): MapInterface
    {
        if ($this->size() === 0) {
            throw new GroupEmptyMapException;
        }

        $map = null;

        foreach ($this->pairs->toArray() as $pair) {
            $key = $discriminator($pair->key(), $pair->value());

            if ($map === null) {
                $map = new Map(
                    Type::determine($key),
                    MapInterface::class
                );
            }

            if ($map->contains($key)) {
                $map = $map->put(
                    $key,
                    $map->get($key)->put(
                        $pair->key(),
                        $pair->value()
                    )
                );
            } else {
                $map = $map->put(
                    $key,
                    $this->clear()->put(
                        $pair->key(),
                        $pair->value()
                    )
                );
            }
        }

        return $map;
    }

    /**
     * {@inheritdoc}
     */
    public function keys(): SetInterface
    {
        return Set::of((string) $this->keyType, ...$this->keys->toArray());
    }

    /**
     * {@inheritdoc}
     */
    public function values(): StreamInterface
    {
        return $this->values;
    }

    /**
     * {@inheritdoc}
     */
    public function map(callable $function): MapInterface
    {
        $map = $this->clear();

        foreach ($this->pairs->toArray() as $pair) {
            $return = $function(
                $pair->key(),
                $pair->value()
            );

            if ($return instanceof Pair) {
                $key = $return->key();
                $value = $return->value();
            } else {
                $key = $pair->key();
                $value = $return;
            }

            $map = $map->put($key, $value);
        }

        return $map;
    }

    /**
     * {@inheritdoc}
     */
    public function join(string $separator): Str
    {
        return $this->values->join($separator);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key): MapInterface
    {
        if (!$this->contains($key)) {
            return $this;
        }

        $index = $this->keys->indexOf($key);
        $map = clone $this;
        $map->keys = $this
            ->keys
            ->slice(0, $index)
            ->append($this->keys->slice($index + 1, $this->keys->size()));
        $map->values = $this
            ->values
            ->slice(0, $index)
            ->append($this->values->slice($index + 1, $this->values->size()));
        $map->pairs = $this
            ->pairs
            ->slice(0, $index)
            ->append($this->pairs->slice($index + 1, $this->pairs->size()));

        return $map;
    }

    /**
     * {@inheritdoc}
     */
    public function merge(MapInterface $map): MapInterface
    {
        if (
            !$this->keyType()->equals($map->keyType()) ||
            !$this->valueType()->equals($map->valueType())
        ) {
            throw new InvalidArgumentException(
                'The 2 maps does not reference the same types'
            );
        }

        return $map->reduce(
            $this,
            function(self $carry, $key, $value): self {
                return $carry->put($key, $value);
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function partition(callable $predicate): MapInterface
    {
        $truthy = $this->clear();
        $falsy = $this->clear();

        foreach ($this->pairs->toArray() as $pair) {
            $return = $predicate(
                $pair->key(),
                $pair->value()
            );

            if ($return === true) {
                $truthy = $truthy->put($pair->key(), $pair->value());
            } else {
                $falsy = $falsy->put($pair->key(), $pair->value());
            }
        }

        return Map::of('bool', MapInterface::class)
            (true, $truthy)
            (false, $falsy);
    }

    /**
     * {@inheritdoc}
     */
    public function reduce($carry, callable $reducer)
    {
        foreach ($this->pairs->toArray() as $pair) {
            $carry = $reducer($carry, $pair->key(), $pair->value());
        }

        return $carry;
    }

    public function empty(): bool
    {
        return $this->pairs->empty();
    }
}
