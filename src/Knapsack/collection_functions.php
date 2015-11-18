<?php

namespace Knapsack;

use ArrayIterator;
use Generator;
use Knapsack\Exceptions\ItemNotFound;
use Knapsack\Exceptions\NoMoreItems;
use stdClass;
use Traversable;

/**
 * Converts $collection to array. If $collection is not array or Traversable, an array [$collection] will be returned.
 * If there are multiple items with the same key, only the last will be preserved.
 *
 * @param array|Traversable $collection
 * @return array
 */
function toArray($collection)
{
    if (is_array($collection) || $collection instanceof Traversable) {
        $arr = [];
        foreach ($collection as $key => $value) {
            $arr[$key] = toArray($value);
        }

        return $arr;
    } else {
        return $collection;
    }
}

/**
 * Returns a lazy collection of distinct items in $collection.
 *
 * @param array|Traversable $collection
 * @return Generator
 */
function distinct($collection)
{
    $distinctValues = [];

    foreach ($collection as $key => $value) {
        if (!in_array($value, $distinctValues)) {
            $distinctValues[] = $value;
            yield $key => $value;
        }
    }
}

/**
 * Returns number of items in $collection.
 *
 * @param array|Traversable $collection
 * @return int
 */
function size($collection)
{
    $result = 0;
    foreach ($collection as $value) {
        $result++;
    }

    return $result;
}

/**
 * Returns a non-lazy collection with items from $collection in reversed order.
 *
 * @param array|Traversable $collection
 * @return Generator
 */
function reverse($collection)
{
    $array = [];
    foreach ($collection as $key => $value) {
        $array[] = [$key, $value];
    }

    return map(
        indexBy(
            array_reverse($array),
            function($item) {
                return $item[0];
            }
        ),
        function($item) {
            return $item[1];
        }
    );
}

/**
 * Returns a lazy collection of values from $collection (i.e. the keys are reset).
 *
 * @param array|Traversable $collection
 * @return Generator
 */
function values($collection)
{
    foreach ($collection as $value) {
        yield $value;
    }
}

/**
 * Returns a lazy collection of keys from $collection.
 *
 * @param array|Traversable $collection
 * @return Generator
 */
function keys($collection)
{
    foreach ($collection as $key => $value) {
        yield $key;
    }
}

/**
 * Returns a lazy collection of items from $collection repeated infinitely.
 *
 * @param array|Traversable $collection
 * @return Generator
 */
function cycle($collection)
{
    while (true) {
        foreach ($collection as $key => $value) {
            yield $key => $value;
        }
    }
}

/**
 * Returns a non-lazy collection of shuffled items from $collection.
 *
 * @param array|Traversable $collection
 * @return Generator
 */
function shuffle($collection)
{
    $arr = [];
    foreach ($collection as $key => $value) {
        $arr[] = [$key, $value];
    }

    \shuffle($arr);

    foreach ($arr as $item) {
        yield $item[0] => $item[1];
    }
}

/**
 * Returns true if $collection does not contain any items.
 *
 * @param array|Traversable $collection
 * @return bool
 */
function isEmpty($collection)
{
    foreach ($collection as $value) {
        return false;
    }

    return true;
}

/**
 * Returns true if $collection does contain any items.
 *
 * @param array|Traversable $collection
 * @return bool
 */
function isNotEmpty($collection)
{
    return !isEmpty($collection);
}

/**
 * Returns a collection where keys are distinct values from $collection and values are number of occurrences of each
 * value.
 *
 * @param array|Traversable $collection
 * @return Generator
 */
function frequencies($collection)
{
    return countBy($collection, function($value) {
        return $value;
    });
}

/**
 * Returns the first item of $collection or throws ItemNotFound if #collection is empty.
 *
 * @param array|Traversable $collection
 * @return mixed
 */
function first($collection)
{
    return getNth($collection, 0);
}

/**
 * Returns the last item of $collection or throws ItemNotFound if #collection is empty.
 *
 * @param array|Traversable $collection
 * @return mixed
 */
function last($collection)
{
    return first(reverse($collection));
}

/**
 * Returns a lazy collection of items of $collection where value of each item is set to the return value of calling
 * $function on its value and key.
 *
 * @param array|Traversable $collection
 * @param callable $function ($value, $key)
 * @return Generator
 */
function map($collection, callable $function)
{
    foreach ($collection as $key => $value) {
        yield $key => $function($value, $key);
    }
}

/**
 * Returns a lazy collection of items from $collection for which $function returns true.
 *
 * @param array|Traversable $collection
 * @param callable $function ($value, $key)
 * @return Generator
 */
function filter($collection, callable $function)
{
    foreach ($collection as $key => $value) {
        if ($function($value, $key)) {
            yield $key => $value;
        }
    }
}

/**
 * Returns a lazy collection with items from $collection2 appended at the end of $collection1
 *
 * @param array|Traversable $collection1
 * @param array|Traversable $collection2
 * @return Generator
 */
function concat($collection1, $collection2)
{
    foreach ($collection1 as $key => $value) {
        yield $key => $value;
    }

    foreach ($collection2 as $key => $value) {
        yield $key => $value;
    }
}

/**
 * Reduces the collection to single value by iterating over the collection and calling $reduction while
 * passing $startValue and current key/item as parameters. The output of $function is used as $startValue in
 * next iteration. The output of $function on last element is the return value of this function.
 *
 * @param array|Traversable $collection
 * @param callable $function ($value, $key)
 * @param mixed $startValue
 * @return mixed
 */
function reduce($collection, callable $function, $startValue)
{
    $tmp = duplicate($startValue);

    foreach ($collection as $key => $value) {
        $tmp = $function($tmp, $value, $key);
    }

    return $tmp;
}

/**
 * Flattens multiple levels of nesting in collection. If $levelsToFlatten is not specified, flattens all levels of
 * nesting.
 *
 * @param array|Traversable $collection
 * @param int $levelsToFlatten -1 to flatten everything
 * @return Generator
 */
function flatten($collection, $levelsToFlatten = -1)
{
    $flattenNextLevel = $levelsToFlatten < 0 || $levelsToFlatten > 0;
    $childLevelsToFlatten = $levelsToFlatten > 0 ? $levelsToFlatten - 1 : $levelsToFlatten;

    foreach ($collection as $key => $value) {
        if ($flattenNextLevel && (is_array($value) || $value instanceof Traversable)) {
            foreach (flatten($value, $childLevelsToFlatten) as $childKey => $childValue) {
                yield $childKey => $childValue;
            }
        } else {
            yield $key => $value;
        }
    }
}

/**
 * Returns a non-lazy collection sorted using $collection($item1, $item2, $key1, $key2 ). $collection should
 * return true if first item is larger than the second and false otherwise.
 *
 * @param array|Traversable $collection
 * @param callable $function ($value1, $value2, $key1, $key2)
 * @return Generator
 */
function sort($collection, callable $function)
{
    $array = iterator_to_array(
        values(
            map(
                $collection,
                function ($value, $key) {
                    return [$key, $value];
                }
            )
        )
    );

    uasort(
        $array,
        function ($a, $b) use ($function) {
            return $function($a[1], $b[1], $a[0], $b[0]);
        }
    );

    foreach ($array as $value) {
        yield $value[0] => $value[1];
    }
}

/**
 * Returns a lazy collection that is a part of $collection starting from $from position and ending in $to position.
 * If $to is not provided, the returned collection is contains all items from $from until end of $collection. All items
 * before $from are iterated over, but not included in result.
 *
 * @param array|Traversable $collection
 * @param int $from
 * @param int $to -1 to slice until end
 * @return Generator
 */
function slice($collection, $from, $to = -1)
{
    $index = 0;
    foreach ($collection as $key => $value) {
        if ($index >= $from && ($index < $to || $to == -1)) {
            yield $key => $value;
        } elseif ($index >= $to && $to >= 0) {
            break;
        }

        $index++;
    }
}

/**
 * Returns a non-lazy collection of items grouped by the result of $function.
 *
 * @param array|Traversable $collection
 * @param callable $function ($value, $key)
 * @return array
 */
function groupBy($collection, callable $function)
{
    $result = [];
    foreach ($collection as $key => $value) {
        $newKey = $function($value, $key);
        $result[$newKey][] = $value;
    }

    return $result;
}

/**
 * Executes $function for each item in $collection
 *
 * @param array|Traversable $collection
 * @param callable $function ($value, $key)
 * @return Generator
 */
function each($collection, callable $function)
{
    foreach ($collection as $key => $value) {
        $function($value, $key);

        yield $key => $value;
    }
}

/**
 * Returns an item with $key key from $collection. If that key is not present, throws ItemNotFound.
 *
 * @param array|Traversable $collection
 * @param mixed $key
 * @return mixed
 */
function get($collection, $key)
{
    foreach ($collection as $valueKey => $value) {
        if ($key === $valueKey) {
            return $value;
        }
    }

    throw new ItemNotFound;
}

/**
 * Returns an item with $key key from $collection. If that key is not present, returns $default.
 *
 * @param array|Traversable $collection
 * @param mixed $key
 * @param mixed $default value returned if key is not found
 * @return mixed
 */
function getOrDefault($collection, $key, $default = null)
{
    try {
        return get($collection, $key);
    } catch (ItemNotFound $e) {
        return $default;
    }
}

/**
 * Returns the first item from $collection for which $function returns true. If item like that is not present, returns
 * $default.
 *
 * @param array|Traversable $collection
 * @param callable $function ($value, $key)
 * @param mixed $default
 * @return mixed
 */
function find($collection, callable $function, $default = null)
{
    foreach ($collection as $key => $value) {
        if ($function($value, $key)) {
            return $value;
        }
    }

    return $default;
}

/**
 * Returns a lazy collection by changing keys of $collection for each item to the result of $function for
 * that item.
 *
 * @param array|Traversable $collection
 * @param callable $function ($value, $key)
 * @return Generator
 */
function indexBy($collection, callable $function)
{
    foreach ($collection as $key => $value) {
        yield $function($value, $key) => $value;
    }
}

/**
 * Returns a non-lazy collection of items whose keys are the return values of $function and values are the number of
 * items in this collection for which the $function returned this value.
 *
 * @param array|Traversable $collection
 * @param callable $function ($value, $key)
 * @return Generator
 */
function countBy($collection, callable $function)
{
    return map(
        groupBy($collection, $function),
        '\Knapsack\size'
    );
}

/**
 * Returns true if $function returns true for every item in $collection
 *
 * @param array|Traversable $collection
 * @param callable $function ($value, $key)
 * @return bool
 */
function every($collection, callable $function)
{
    foreach ($collection as $key => $value) {
        if (!$function($value, $key)) {
            return false;
        }
    }

    return true;
}

/**
 * Returns true if $function returns true for at least one item in $collection.
 *
 * @param array|Traversable $collection
 * @param callable $function ($value, $key)
 * @return bool
 */
function some($collection, callable $function)
{
    foreach ($collection as $key => $value) {
        if ($function($value, $key)) {
            return true;
        }
    }

    return false;
}

/**
 * Returns true if $needle is found in $collection values.
 *
 * @param $collection
 * @param mixed $needle
 * @return bool
 */
function contains($collection, $needle)
{
    foreach ($collection as $key => $value) {
        if ($value === $needle) {
            return true;
        }
    }

    return false;
}

/**
 * Reduce that walks from right to the left.
 *
 * @param array|Traversable $collection
 * @param callable $function
 * @param mixed $startValue
 * @return mixed
 */
function reduceRight($collection, callable $function, $startValue)
{
    return reduce(reverse($collection), $function, $startValue);
}

/**
 * Returns a lazy collection of first $numberOfItems items of $collection.
 *
 * @param array|Traversable $collection
 * @param int $numberOfItems
 * @return Generator
 */
function take($collection, $numberOfItems)
{
    return slice($collection, 0, $numberOfItems);
}

/**
 * Returns a lazy collection of all but first $numberOfItems items of $collection.
 *
 * @param array|Traversable $collection
 * @param int $numberOfItems
 * @return Generator
 */
function drop($collection, $numberOfItems)
{
    return slice($collection, $numberOfItems);
}

/**
 * Returns a lazy collection of values, where first value is $value and all subsequent values are computed by applying
 * $function to the last value in the collection. By default this produces an infinite collection. However you can
 * end the collection by throwing a NoMoreItems exception.
 *
 * @param mixed $value
 * @param callable $function ($value, $key)
 * @return Generator
 */
function iterate($value, callable $function)
{
    yield $value;

    while (true) {
        try {
            $value = $function($value);
            yield $value;
        } catch (NoMoreItems $e) {
            break;
        }
    }
}

/**
 * Returns a lazy collection of items from $collection for which $function returned true.
 *
 * @param array|Traversable $collection
 * @param callable $function ($value, $key)
 * @return Generator
 */
function reject($collection, callable $function)
{
    return filter(
        $collection,
        function($value, $key) use ($function) {
            return !$function($value, $key);
        }
    );
}

/**
 * Returns a lazy collection of items in $collection without the last $numberOfItems items.
 *
 * @param array|Traversable $collection
 * @param int $numberOfItems
 * @return Generator
 */
function dropLast($collection, $numberOfItems = 1)
{
    $buffer = [];

    foreach ($collection as $key => $value) {
        $buffer[] = [$key, $value];

        if (count($buffer) > $numberOfItems) {
            $val = array_shift($buffer);
            yield $val[0] => $val[1];
        }
    }
}

/**
 * Returns a lazy collection of items from $collection separated by $separator.
 *
 * @param array|Traversable $collection
 * @param mixed $separator
 * @return Generator
 */
function interpose($collection, $separator)
{
    foreach (take($collection, 1) as $key => $value) {
        yield $key => $value;
    }

    foreach (drop($collection, 1) as $key => $value) {
        yield $separator;
        yield $key => $value;
    }
}

/**
 * Returns a lazy collection of first item from first collection, first item from second, second from first and
 * so on.
 *
 * @param array|Traversable $collection1
 * @param array|Traversable $collection2
 * @return Generator
 */
function interleave($collection1, $collection2)
{
    $collection1 = (is_array($collection1)) ? new ArrayIterator($collection1) : $collection1;
    $collection2 = (is_array($collection2)) ? new ArrayIterator($collection2) : $collection2;

    $collection1->rewind();
    $collection2->rewind();

    while ($collection1->valid() || $collection2->valid()) {
        if ($collection1->valid()) {
            yield $collection1->key() => $collection1->current();
            $collection1->next();
        }

        if ($collection2->valid()) {
            yield $collection2->key() => $collection2->current();
            $collection2->next();
        }
    }
}

/**
 * Returns a lazy collection of items in $collection with $value added as first element. If $key is not provided
 * it will be next integer index.
 *
 * @param array|Traversable $collection
 * @param mixed $value
 * @param mixed|null $key
 * @return Generator
 */
function prepend($collection, $value, $key = null)
{
    if ($key === null) {
        yield $value;
    } else {
        yield $key => $value;
    }

    foreach ($collection as $key => $value) {
        yield $key => $value;
    }
}

/**
 * Returns a lazy collection of items in $collection with $value added as last element. If $key is not provided
 * it will be next integer index.
 *
 * @param array|Traversable $collection
 * @param mixed $value
 * @param mixed|null $key
 * @return Generator
 */
function append($collection, $value, $key = null)
{
    foreach ($collection as $k => $v) {
        yield $k => $v;
    }

    if ($key === null) {
        yield $value;
    } else {
        yield $key => $value;
    }
}

/**
 * Returns a lazy collection by removing items from $collection until first item for which $function returns false.
 *
 * @param array|Traversable $collection
 * @param callable $function ($value, $key)
 * @return Generator
 */
function dropWhile($collection, callable $function)
{
    $shouldDrop = true;
    foreach ($collection as $key => $value) {
        if ($shouldDrop) {
            $shouldDrop = $function($value, $key);
        }

        if (!$shouldDrop) {
            yield $key => $value;
        }
    }
}

/**
 * Returns a lazy collection of items from $collection until first item for which $function returns false.
 *
 * @param array|Traversable $collection
 * @param callable $function ($value, $key)
 * @return Generator
 */
function takeWhile($collection, callable $function)
{
    $shouldTake = true;
    foreach ($collection as $key => $value) {
        if ($shouldTake) {
            $shouldTake = $function($value, $key);
        }

        if ($shouldTake) {
            yield $key => $value;
        }
    }
}

/**
 * Returns a lazy collection. A result of calling map and flatten(1)
 *
 * @param array|Traversable $collection
 * @param callable $function ($value, $key)
 * @return Generator
 */
function mapcat($collection, callable $function)
{
    return flatten(map($collection, $function), 1);
}

/**
 * Returns a lazy collection [take($collection, $position), drop($collection, $position)]
 *
 * @param array|Traversable $collection
 * @param int $position
 * @return Generator
 */
function splitAt($collection, $position)
{
    yield take($collection, $position);
    yield drop($collection, $position);
}

/**
 * Returns a lazy collection [takeWhile($collection, $function), dropWhile($collection, $function)]
 *
 * @param array|Traversable $collection
 * @param callable $function ($value, $key)
 * @return Generator
 */
function splitWith($collection, callable $function)
{
    yield takeWhile($collection, $function);
    yield dropWhile($collection, $function);
}

/**
 * Returns a lazy collection with items from $collection but values that are found in keys of $replacementMap
 * are replaced by their values.
 *
 * @param array|Traversable $collection
 * @param array|Traversable $replacementMap
 * @return Generator
 */
function replace($collection, $replacementMap)
{
    foreach ($collection as $key => $value) {
        $newValue = getOrDefault($replacementMap, $value, $value);
        yield $key => $newValue;
    }
}

/**
 * Returns a lazy collection of reduction steps.
 *
 * @param array|Traversable $collection
 * @param callable $function
 * @param mixed $startValue
 * @return Generator
 */
function reductions($collection, callable $function, $startValue)
{
    $tmp = duplicate($startValue);

    yield $tmp;
    foreach ($collection as $key => $value) {
        $tmp = $function($tmp, $value, $key);
        yield $tmp;
    }
}

/**
 * Returns a lazy collection of every nth ($step) item in  $collection.
 *
 * @param array|Traversable $collection
 * @param int $step
 * @return Generator
 */
function takeNth($collection, $step)
{
    $index = 0;
    foreach ($collection as $key => $value) {
        if ($index % $step == 0) {
            yield $key => $value;
        }

        $index++;
    }
}

/**
 * Returns a lazy collection of collections of $numberOfItems items each, at $step step
 * apart. If $step is not supplied, defaults to $numberOfItems, i.e. the partitions
 * do not overlap. If a $padding collection is supplied, use its elements as
 * necessary to complete last partition up to $numberOfItems items. In case there are
 * not enough padding elements, return a partition with less than $numberOfItems items.
 *
 * @param $collection
 * @param $numberOfItems
 * @param int $step
 * @param array $padding
 * @return Generator
 */
function partition($collection, $numberOfItems, $step = -1, $padding = [])
{
    $buffer = [];
    $itemsToSkip = 0;
    $step = $step ?: $numberOfItems;

    foreach ($collection as $key => $value) {
        if (count($buffer) == $numberOfItems) {
            yield dereferenceKeyValue($buffer);

            $buffer = array_slice($buffer, $step);
            $itemsToSkip =  $step - $numberOfItems;
        }

        if ($itemsToSkip <= 0) {
            $buffer[] = [$key, $value];
        } else {
            $itemsToSkip--;
        }
    }

    yield take(
        concat(dereferenceKeyValue($buffer), $padding),
        $numberOfItems
    );
}

/**
 * Returns a lazy collection created by partitioning $collection each time $function returned a different value.
 *
 * @param array|Traversable $collection
 * @param callable $function
 * @return Generator
 */
function partitionBy($collection, callable $function)
{
    $result = null;
    $buffer = [];

    foreach ($collection as $key => $value) {
        $newResult = $function($value, $key);

        if (!empty($buffer) && $result != $newResult) {
            yield dereferenceKeyValue($buffer);
            $buffer = [];
        }

        $result = $newResult;
        $buffer[] = [$key, $value];
    }

    if (!empty($buffer)) {
        yield dereferenceKeyValue($buffer);
    }
}

/**
 * Returns nth ($position) item from $collection. If $position is greater than $collection size, throws ItemNotFound.
 *
 * @param array|Traversable $collection
 * @param int $position
 * @return mixed
 */
function getNth($collection, $position)
{
    return get(values($collection), $position);
}

/**
 * Returns a lazy collection by picking a $key key from each sub-collection of $collection.
 *
 * @param array|Traversable $collection
 * @param mixed $key
 * @return Generator
 */
function pluck($collection, $key)
{
    return map(
        $collection,
        function($value) use ($key) {
            return $value[$key];
        }
    );

}

/**
 * Returns a lazy collection of $value repeated $times times. If $times is not provided the collection is infinite.
 *
 * @param mixed $value
 * @param int $times
 * @return Generator
 */
function repeat($value, $times = -1)
{
    while ($times != 0) {
        yield $value;

        $times = $times < 0 ? -1 : $times - 1;
    }
}

/**
 * Returns a lazy collection of numbers starting at $start, incremented by $step until $end is reached.
 *
 * @param int $start
 * @param int|null $end
 * @param int $step
 * @return Generator
 */
function range($start = 0, $end = null, $step = 1)
{
    return iterate(
        $start,
        function($value) use ($step, $end) {
            $result = $value + $step;

            if ($end !== null && $result > $end) {
                throw new NoMoreItems;
            }

            return $result;
        }
    );
}

//helpers
/**
 * Returns true if $input is array or Traversable object.
 *
 * @param mixed $input
 * @return bool
 */
function isCollection($input)
{
    return is_array($input) || $input instanceof Traversable;
}

/**
 * Returns duplicated/cloned $input that has no relation to the original one. Used for making sure there are no side
 * effect in functions.
 *
 * @param $input
 * @return Generator
 */
function duplicate($input)
{
    if (is_array($input)) {
        return toArray(
            map(
                $input,
                function($i) {
                    return duplicate($i);
                }
            )
        );
    } elseif (is_object($input)) {
        return clone $input;
    } else {
        return $input;
    }
}

/**
 * Transforms [[$key, $value], [$key2, $value2]] into [$key => $value, $key2 => $value2]. Used as a helper
 *
 * @param array|Traversable $collection
 * @return Generator
 */
function dereferenceKeyValue($collection)
{
    foreach ($collection as $value) {
        yield $value[0] => $value[1];
    }
}