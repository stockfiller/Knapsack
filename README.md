# Knapsack
**Collection pipeline library for PHP**

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/5fcb3dc2-2061-4da3-853b-a5e2a35a35fb/mini.png)](https://insight.sensiolabs.com/projects/5fcb3dc2-2061-4da3-853b-a5e2a35a35fb) [![Build Status](https://scrutinizer-ci.com/g/DusanKasan/Knapsack/badges/build.png?b=master)](https://scrutinizer-ci.com/g/DusanKasan/Knapsack/build-status/master) [![Code Coverage](https://scrutinizer-ci.com/g/DusanKasan/Knapsack/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/DusanKasan/Knapsack/?branch=master) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/DusanKasan/Knapsack/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/DusanKasan/Knapsack/?branch=master)

Knapsack is a collection library for PHP >= 5.6 that implements most of the sequence operations proposed by [Clojures sequences](http://clojure.org/sequences) plus some additional ones. All its features are available as functions (for functional programming) and as a [collection pipeline](http://martinfowler.com/articles/collection-pipeline/) object methods.

The heart of Knapsack is its [Collection class](https://github.com/DusanKasan/Knapsack/blob/master/src/Collection.php). However its every method calls a simple function with the same name that does the actual heavy lifting. These are located in `DusanKasan\Knapsack` namespace and you can find them [here](https://github.com/DusanKasan/Knapsack/blob/master/src/collection_functions.php). Collection is an iterator implementor that accepts Traversable object, array or even a callable that produces a Traversable object or array as constructor argument. It provides most of Clojures sequence functionality plus some extra features. It is also immutable - operations preformed on the collection will return new collection (or value) instead of modifying the original collection.
 
Most of the methods of Collection return lazy collections (such as filter/map/etc.). However, some return non-lazy collections (reverse) or simple values (count). For these operations all of the items in the collection must be iterated over (and realized). There are also operations (drop) that iterate over some items of the collection but do not affect/return them in the result. This behaviour as well as laziness is noted for each of the operations.  

If you want more example usage beyond what is provided here, check the [specs](https://github.com/DusanKasan/Knapsack/tree/master/tests/spec) and/or [scenarios](https://github.com/DusanKasan/Knapsack/tree/master/tests/scenarios). There are also [performance tests](https://github.com/DusanKasan/Knapsack/tree/master/tests/performance) you can run on your machine and see the computation time impact of this library (the output of these is included below).

Feel free to report any [issues](https://github.com/DusanKasan/Knapsack/issues) you find. I will do my best to fix them as soon as possible, but community [pull requests](https://github.com/DusanKasan/Knapsack/pulls) to fix them are more than welcome.

## Documentation
Check out the documentation (which is prettified version of this readme) at http://dusankasan.github.io/Knapsack

## Installation

Require this package using Composer.


```
$ composer require dusank/knapsack
```

## Usage

### Instantiate via static or dynamic constructor
```php
use DusanKasan\Knapsack\Collection;

$collection1 = new Collection([1, 2, 3]);
$collection2 = Collection::from([1, 2, 3]); //preferred since you can call methods on its result directly.
```

### Work with arrays, Traversable objects or callables that produce Traversables
```php
$collection1 = Collection::from([1, 2, 3]);
$collection2 = Collection::from(new ArrayIterator([1, 2, 3]);

//Used because Generator can not be rewound
$collection2 = Collection::from(function() { //must have 0 arguments
    foreach ([1, 2, 3] as $value) {
        yield $value;
    }
});
```

### Basic map/reduce
```php
$result = Collection::from([1, 2])
    ->map(function($v) {return $v*2;})
    ->reduce(function($tmp, $v) {return $tmp+$v;}, 0);
    
echo $result; //6
```

### The same map/reduce using Knapsack's collection functions
```php
$result = reduce(
    map(
        [1, 2], 
        function($v) {return $v*2;}
    ),
    function($tmp, $v) {return $tmp+$v;},
    0
);

echo $result; //6
```

### Get first 5 items of Fibonacci's sequence
```php
$result = Collection::iterate([1,1], function($v) {
        return [$v[1], $v[0] + $v[1]]; //[1, 2], [2, 3] ...
    })
    ->map('\Knapsack\first') //one of the collection functions
    ->take(5);
    
foreach ($result as $item) {
    echo $item . PHP_EOL;
}

//1
//1
//2
//3
//5
```

### If array or Traversable would be returned from functions that return an item from the collection, it can be converted to Collection using the optional flag. By default it returns the item as is.
```php
$result = Collection::from([[[1]]])
    ->first(true)
    ->first();
    
vardump($result); //[1]
```


### Collections are immutable
```php
function multiplyBy2($v)
{
    return $v * 2;
}

function multiplyBy3($v)
{
    return $v * 3;
}

function add($a, $b)
{
    return $a + $b;
}

$result = Collection::from([1, 2])
    ->map('multiplyBy2')
    ->reduce(0, 'add');
    
echo $result; //6

//On the same collection
$differentResult = Collection::from([1, 2])
    ->map('multiplyBy3')
    ->reduce(0, 'add');
    
echo $differentResult; //9
```

### Keys are not unique by design
It would harm performance. This is only a problem if you need to call toArray(), then you should call values() before.
```php
$result = Collection::from([1, 2])->concat([3,4]);
    
//arrays have unique keys
$result->toArray(); //[3,4]
$result->values()->toArray(); //[1, 2, 3, 4]

//When iterating, you can have multiple keys.
foreach ($result as $key => $item) {
    echo $key . ':' . $item . PHP_EOL;
}

//0:1
//1:2
//0:3
//1:4
```

### Collection trait is provided
If you wish to use all the Collection methods in your existing classes directly, no need to proxy their calls, you can just use the provided [CollectionTrait](https://github.com/DusanKasan/Knapsack/blob/master/src/CollectionTrait.php). This will work on any Traversable by default. In any other class you will have to override the getItems() method provided by the trait. Keep in mind that after calling filter or any other method that returns collection, the returned type will be actually Collection, not the original Traversable. 

```php
class AwesomeIterator extends ArrayIterator {
    use CollectionTrait;
}

$iterator = new AwesomeIterator([1, 2, 3]);
$iterator->size(); //3
```
## Performance tests

### PHP 5.6
```php
+------------------------------------------------------------------------------------+-----------------------+---------------------------+----------------------+
| operation details                                                                  | native execution time | collection execution time | difference           |
+------------------------------------------------------------------------------------+-----------------------+---------------------------+----------------------+
| array_map vs Collection::map on 1000 integers (addition)                           | 0.0026963949203491s   | 0.017051982879639s        | 632%                 |
| array_map vs Collection::map on 1000 strings (concatenation)                       | 0.0028762340545654s   | 0.019258499145508s        | 669%                 |
| array_map vs Collection::map on 1000 object (object to field value)                | 0.0030517101287842s   | 0.017919874191284s        | 587%                 |
| array_map vs Collection::map on 1000 md5 invocations                               | 0.0038059234619141s   | 0.01975724697113s         | 519%                 |
| array_map vs Collection::map for 1000 integers n, counting sum(0, n) the naive way | 0.052967953681946s    | 0.072820711135864s        | 137%                 |
+------------------------------------------------------------------------------------+-----------------------+---------------------------+----------------------+
```

### PHP 7 beta 2
```php
+------------------------------------------------------------------------------------+-----------------------+---------------------------+----------------------+
| operation details                                                                  | native execution time | collection execution time | difference           |
+------------------------------------------------------------------------------------+-----------------------+---------------------------+----------------------+
| array_map vs Collection::map on 1000 integers (addition)                           | 9.7393989562988E-5s   | 0.0013259887695313s       | 1361%                |
| array_map vs Collection::map on 1000 strings (concatenation)                       | 0.0001237154006958s   | 0.0013420820236206s       | 1084%                |
| array_map vs Collection::map on 1000 object (object to field value)                | 0.00011978149414062s  | 0.0014124155044556s       | 1179%                |
| array_map vs Collection::map on 1000 md5 invocations                               | 0.00045244693756104s  | 0.0019778966903687s       | 437%                 |
| array_map vs Collection::map for 1000 integers n, counting sum(0, n) the naive way | 0.011838340759277s    | 0.013077282905579s        | 110%                 |
+------------------------------------------------------------------------------------+-----------------------+---------------------------+----------------------+
```

## Constructors
These are ways how to create the Collection class. There is one default constructor and few named (static) ones.

#### new(array|Traversable|callback $input)
The default constructor accepts array, Traversable or a callable that takes no arguments and produces Traversable. The use case for the callable argument is for example a Generator, which can not be rewound so the Collection must be able to reconstruct it when rewinding itself.

```php
$collection = new Collection([1, 2, 3]);
```
```php
$collection = new Collection(new ArrayIterator([1, 2, 3]));
```
```php
$generatorFactory = function () {
    foreach ([1, 2] as $value) {
        yield $value;
    }
};

$collection = new Collection($generatorFactory);
```

#### from(array|Traversable|callback $input)
Collection::from is a static alias of the default constructor.

```php
$collection = Collection::from([1, 2, 3]);
```
```php
$collection = Collection::from(new ArrayIterator([1, 2, 3]));
```
```php
$generatorFactory = function () {
    foreach ([1, 2] as $value) {
        yield $value;
    }
};

$collection = Collection::from($generatorFactory);
```

#### iterate(mixed $input, callable $function)
Returns lazy collection of values, where first value is $input and all subsequent values are computed by applying $function to the last value in the collection. By default this produces an infinite collection. However you can end the collection by throwing a NoMoreItems exception.

```php
$collection = Collection::iterate(1, function ($value) {return $value + 1;}); // 1, 2, 3, 4 ...
```

#### repeat(mixed $value, int $times = -1)
Returns a lazy collection of $value repeated $times times. If $times is not provided the collection is infinite.

```php
Collection::repeat(1); //infinite collection of ones
```

```php
Collection::repeat(1, 4)->toArray(); //[1, 1, 1, 1]
```

#### range(int $start = 0, int $end = null, int step = 1)
Returns a lazy collection of numbers starting at $start, incremented by $step until $end is reached.
```php
Collection::range(0, 6, 2)->toArray(); //[0, 2, 4, 6]
```

## Operations
These are the operations (methods) provided by Collection class. For each one, there is a function with the same name in Knapsack namespace. The function has the same footprint as the method, except it has one extra argument prepended - the collection (array or Traversable).

### Standard Iterator methods
It implements http://php.net/manual/en/class.iterator.php

#### append(mixed $item, mixed $key = null) : Collection
Returns a lazy collection of items of this collection with $item added as last element. If $key is not provided, its key will be the next integer in the sequence.
```php
Collection::from([1, 3, 3, 2])
    ->append(1)
    ->toArray(); //[1, 3, 3, 2, 1]
```
```php    
Collection::from([1, 3, 3, 2])
    ->append(1, 'key')
    ->toArray(); //[1, 3, 3, 2, 'key' => 1]
```    
```php    
toArray(append([1, 3, 3, 2], 1, 'key')); //[1, 3, 3, 2, 'key' => 1]
```

#### combine($collection, $strict = false) : Collection
Combines the values of this collection as keys, with values of $collection as values.  The resulting collection has length equal to the size of smaller collection. If $strict is true, the size of both collections must be equal, otherwise ItemNotFound is thrown. When strict, the collection is realized immediately.
```php
Collection::from(['a', 'b])
    ->combine([1, 2)
    ->toArray(); //['a' => 1, 'b' => 2]
```
```php    
toArray(combine(['a', 'b], [1, 2])); //['a' => 1, 'b' => 2]
```

#### concat(...Traversable|array) : Collection
Returns a lazy collection with items from this collection followed by items from the collection from first argument, then second and so on.
```php
Collection::from([1, 3, 3, 2])
    ->concat([4, 5]) //If we would convert to array here, we would loose 2 items because of same keys [4, 5, 3, 2]
    ->values() 
    ->toArray(); //[1, 3, 3, 2, 4, 5]
```
```php    
toArray(values(concat([1, 3, 3, 2], [4, 5]))); //[1, 3, 3, 2, 4, 5]  
```

#### contains(mixed $needle) : bool
Returns true if $needle is present in the collection.
```php
Collection::from([1, 3, 3, 2])->contains(2); //true
```
```php
contains([1, 3, 3, 2], 2); //true
```

#### countBy(callable $function) : Collection
Returns a collection of items whose keys are the return values of $function(value, key) and values are the number of items in this collection for which the $function returned this value.
```php
Collection::from([1, 2, 3, 4, 5])
    ->countBy(function ($value) {
        return $value % 2 == 0 ? 'even' : 'odd';
    })
    ->toArray(); //['odd' => [1, 3, 5], 'even' => [2, 4]]  
```
```php      
toArray(countBy([1, 2, 3, 4, 5], function ($value) {return $value % 2 == 0 ? 'even' : 'odd';}));      
```

#### cycle() : Collection
Returns an infinite lazy collection of items in this collection repeated infinitely.
```php
Collection::from([1, 3, 3, 2])
    ->cycle()
    ->take(8) //we take just 8 items, since this collection is infinite
    ->values()
    ->toArray(); //[1, 3, 3, 2, 1, 3, 3, 2]
```
```php    
toArray(values(take(cycle([1, 3, 3, 2]), 8))); //[1, 3, 3, 2, 1, 3, 3, 2]
```

#### difference(array|Traversable ...$collections) : Collection
Returns a lazy collection of items that are in $this but are not in any of the other arguments. Note that the ...$collections are iterated non-lazily.
```php
Collection::from([1, 3, 3, 2])
    ->difference([1, 3])
    ->toArray() //[3 => 2]
```
```php    
toArray(difference([1, 3, 3, 2], [1, 3])); //[3 => 2]
```

#### distinct() : Collection
Returns a lazy collection of distinct items. The comparison whether the item is in the collection or not is the same as in in_array.
```php
Collection::from([1, 3, 3, 2])
    ->distinct()
    ->toArray() //[1, 3, 3 => 2] - each item has key of the first occurrence
```
```php    
toArray(distinct([1, 3, 3, 2])); //[1, 3, 3 => 2] - each item has key of the first occurrence
```

#### drop(int $numberOfItems) : Collection
A form of slice that returns all but first $numberOfItems items.
```php
Collection::from([1, 2, 3, 4, 5])
    ->drop(4)
    ->toArray(); //[4 => 5]
```    
```php
toArray(drop([1, 2, 3, 4, 5], 4)); //[4 => 5]    
```

#### dropLast($numberOfItems = 1) : Collection
Returns a lazy collection with last $numberOfItems items skipped. These are still realized, just skipped.
```php
Collection::from([1, 2, 3])
    ->dropLast()
    ->toArray(); //[1, 2]
```
```php
Collection::from([1, 2, 3])
$collection
    ->dropLast(2)
    ->toArray(); //[1]
```    
```php    
toArray(dropLast([1, 2, 3], 2)); //[1]    
```

#### dropWhile(callable $function) : Collection
Returns a lazy collection by removing items from this collection until first item for which $function(value, key) returns false.
```php
Collection::from([1, 3, 3, 2])
    ->dropWhile(function ($v) {
        return $v < 3;
    })
    ->toArray(); //[1 => 3, 2 => 3, 3 => 2])
```
```php
Collection::from([1, 3, 3, 2])
    ->dropWhile(function ($v, $k) {
        return $k < 2 && $v < 3;
    })
    ->toArray(); //[1 => 3, 2 => 3, 3 => 2])
```
```php
Collection::from([1, 3, 3, 2])
    ->dropWhile(function ($v, $k) {
        return $k < 2 && $v < 3;
    })
    ->toArray(); //[1 => 3, 2 => 3, 3 => 2])
```
```php
toArray(values(dropWhile([1, 3, 3, 2], function ($v) {return $v < 3;}))); // [3, 3, 2]
```    

#### each(callable $function) : Collection
Returns a lazy collection in which $function(value, key) is executed for each item.
```php
Collection::from([1, 2, 3, 4, 5])
    ->each(function ($i) {
        echo $i . PHP_EOL;
    })
    ->toArray(); //[1, 2, 3, 4, 5]

//1
//2
//3
//4
//5
```
```php
each([1, 2, 3, 4, 5], function ($v) {echo $v . PHP_EOL;});

//1
//2
//3
//4
//5
```

#### every(callable $function) : bool
Returns true if $function(value, key) returns true for every item in this collection, false otherwise.
```php
Collection::from([1, 3, 3, 2])
    ->every(function ($v) {
        return $v < 3;
    }); //false
```
```php
Collection::from([1, 3, 3, 2])
    ->find(function ($v. $k) {
       return $v < 4 && $k < 2;
    }, 10); //false
```
```php
every([1, 3, 3, 2], function ($v) {return $v < 5;}); //true
```

#### except(array|Traversable $keys) : Collection
Returns a lazy collection without the items associated to any of the keys from $keys.
```php
Collection::from(['a' => 1, 'b' => 2])
    ->except(['a'])
    ->toArray(); //['b' => 2]
```
```php
toArray(except(['a' => 1, 'b' => 2], ['a'])); //['b' => 2]
```

#### filter(callable $function) : Collection
Returns a lazy collection of items for which $function(value, key) returned true.
```php
Collection::from([1, 3, 3, 2])
    ->filter(function ($value) {
        return $value > 2;
    })
    ->values()
    ->toArray() //[3, 3]
```
```php
Collection::from([1, 3, 3, 2])
    ->filter(function ($value, $key) {
        return $value > 2 && $key > 1;
    })
    ->toArray() //[2 => 3]
```
```php
toArray(values(filter([1, 3, 3, 2], function ($value) {return $value > 2;}))); //[3, 3]
```

#### find(callable $function, mixed $ifNotFound = null, bool $convertToCollection = false) : mixed|Collection
Returns first value for which $function(value, key) returns true. If no item is matched, returns $ifNotFound. If $convertToCollection is true and the return value is a collection (array|Traversable) an instance of Collection will be returned.
```php
Collection::from([1, 3, 3, 2])
    ->find(function ($value) {
       return $value < 3;
    }); //1
```
```php
Collection::from([1, 3, 3, 2])
    ->find(function ($value) {
       return $value > 3;
    }, 10); //10
```
```php
Collection::from([1, 3, 3, 2])
    ->find(function ($value, $key) {
      return $value < 3 && $key > 1;
    }); //2
```
```php
//if the output can be converted to Collection (it's array or Traversable), it will be.
Collection::from([1, [4, 5], 3, 2])
    ->find(function ($value) {
      return is_array($value);
    }, true)
    ->size(); //2 
```
```php
find([1, 3, 3, 2], function ($value) {return $value > 2;}); //3
```

#### first(bool $convertToCollection = false) : mixed|Collection
Returns first value in the collection or throws ItemNotFound if the collection is empty. If $convertToCollection is true and the return value is a collection (array|Traversable) an instance of Collection will be returned.
```php
Collection::from([1, 2, 3])->first(); //1
```
```php
Collection::from([[1], 2, 3])->first(); //[1]
```
```php
Collection::from([])->first(); //throws ItemNotFound
```
```php
first([1, 2, 3]); //1
```

#### flatten(int $depth = -1) : Collection
Returns a lazy collection with one or multiple levels of nesting flattened. Removes all nesting when no $depth value is passed.
```php
Collection::from([1,[2, [3]]])
    ->flatten()
    ->values() //1, 2 and 3 have all key 0
    ->toArray() //[1, 2, 3]
```
```php
Collection::from([1,[2, [3]]])
    ->flatten(1)
    ->values() //1, 2 and 3 have all key 0
    ->toArray() //[1, 2, [3]]
```
```php
toArray(values(flatten([1, [2, [3]]]))); //[1, 2, 3]
```

#### flip() : Collection
Returns a lazy collection where keys and values are flipped.
```php
Collection::from(['a' => 0, 'b' => 1])
    ->flip()
    ->toArray() //['a', 'b']
```
```php
toArray(flip(['a' => 0, 'b' => 1])); //['a', 'b']
```

#### frequencies() : Collection
Returns a collection where keys are distinct items from this collection and their values are number of occurrences of each value.
```php
Collection::from([1, 3, 3, 2])
    ->frequencies()
    ->toArray(); //[1 => 1, 3 => 2, 2 => 1]
```
```php
toArray(frequencies([1, 3, 3, 2])); //[1 => 1, 3 => 2, 2 => 1]
```

#### get(mixed $key, bool $convertToCollection = false) : mixed|Collection
Returns value at the key $key. If multiple values have this key, return first. If no value has this key, throw `ItemNotFound`. If $convertToCollection is true and the return value is a collection (array|Traversable) an instance of Collection will be returned.
```php
Collection::from([1, 3, 3, 2])->get(2); //3
```
```php
Collection::from([1, [1, 2]])->get(1, true)->toArray(); //[1, 2]
```
```php
Collection::from([1, 3, 3, 2])->get(5); //throws ItemNotFound
```
```php
get([1, 3, 3, 2], 2); //3
```

#### getNth(int $position, bool $convertToCollection = false) : mixed|Collection
Returns value at $position position in collection. If that position does not exist, throws `ItemNotFound`. If $convertToCollection is true and the return value is a collection (array|Traversable) an instance of Collection will be returned.
```php
Collection::from([1, 3, 3, 2])->getNth(0); //1
```
```php
Collection::from([1, [1, 2]])->getNth(1, true)->toArray(); //[1, 2]
```
```php
Collection::from([1, 3, 3, 2])->getNth(5); //throws ItemNotFound
```
```php
getNth([1, 3, 3, 2], 2); //3
```

#### getOrDefault(mixed $key, mixed $default = null, bool $convertToCollection = false) : mixed|Collection
Returns value at the key $key. If multiple values have this key, return first. If no value has this key, return $default. If $convertToCollection is true and the return value is a collection (array|Traversable) an instance of Collection is returned.
```php
Collection::from([1, 3, 3, 2])->getOrDefault(2); //3
```
```php
Collection::from([1, 3, 3, 2])->getOrDefault(5); //null
```
```php
Collection::from([1, 3, 3, 2])->getOrDefault(5, 'asd'); //'asd'
```
```php
Collection::from([1, 3, 3, 2])->getOrDefault(5, [1, 2], null, true)->toArray(); //[1, 2]
```
```php
getOrDefault([1, 3, 3, 2], 5, 'asd'); //'asd'
```

#### groupBy(callable $function) : Collection
Returns collection which items are separated into groups indexed by the return value of $function(value, key).
```php
Collection::from([1, 2, 3, 4, 5])
    ->groupBy(function ($value) {
        return $value % 2;
    })
    ->toArray(); //[1 => [1, 3, 5], 0 => [2, 4]]
```
```php
toArray(groupBy([1, 2, 3, 4, 5], function ($value) {return $value % 2;})); //[1 => [1, 3, 5], 0 => [2, 4]]
```


#### has(mixed $key) : bool
Checks for the existence of $key in this collection.
```php
Collection::from(['a' => 1])->has('a'); //true
```
```php
has(['a' => 1], 'a'); //true
```

#### indexBy(callable $function) : Collection
Returns a lazy collection by changing keys of this collection for each item to the result of $function(value, key) for that key/value.
```php
Collection::from([1, 3, 3, 2])
    ->indexBy(function ($v) {
        return $v;
    })
    ->toArray(); //[1 => 1, 3 => 3, 2 => 2]
```
```php
toArray(indexBy([1, 3, 3, 2], '\DusanKasan\Knapsack\identity')); //[1 => 1, 3 => 3, 2 => 2]
```

#### interleave(...Traversable|array $collection) : Collection
Returns a lazy collection of first item from first collection, first item from second, second from first and so on. Works with any number of arguments.
```php
Collection::from([1, 3, 3, 2])
    ->interleave(['a', 'b', 'c', 'd', 'e'])
    ->values()
    ->toArray(); //[1, 'a', 3, 'b', 3, 'c', 2, 'd', 'e']
```
```php
toArray(interleave([1, 3, 3, 2], ['a', 'b', 'c', 'd', 'e'])); //[1, 'a', 3, 'b', 3, 'c', 2, 'd', 'e']
```

#### interpose(mixed $separator) : Collection
Returns a lazy collection of items of this collection separated by $separator item.
```php
Collection::from([1, 2, 3])
    ->interpose('a')
    ->values() // we must reset the keys, because each 'a' has undecided key
    ->toArray(); //[1, 'a', 2, 'a', 3]
```
```php
toArray(interpose([1, 3, 3, 2], 'a')); //[1, 'a', 2, 'a', 3] 
```

#### isEmpty() : bool
Returns true if is collection is empty. False otherwise.
```php
Collection::from([1, 3, 3, 2])->isEmpty(); //false
```
```php
isEmpty([1]); //false
```

#### isNotEmpty() : bool
Opposite of isEmpty
```php
Collection::from([1, 3, 3, 2])>isNotEmpty(); //true
```
```php
isNotEmpty([1]); //true
```

#### keys() : Collection
Returns a lazy collection of the keys of this collection.
```php
Collection::from(['a' => [1, 2], 'b' => [2, 3]])
    ->keys()
    ->toArray(); //['a', 'b']
```
```php
toArray(keys(['a' => [1, 2], 'b' => [2, 3]])); //['a', 'b']
```

#### last(bool $convertToCollection = false) : mixed|Collection
Returns last value in the collection or throws ItemNotFound if the collection is empty. If $convertToCollection is true and the return value is a collection (array|Traversable) an instance of Collection is returned.
```php
Collection::from([1, 2, 3])->last(); //3
```
```php
Collection::from([1, 2, [3]])->last(true)->toArray(); //[1]
```
```php
Collection::from([])->last(); //throws ItemNotFound
```
```php
last([1, 2, 3]); //3
```

#### map(callable $function) : Collection
Returns collection where each value is changed to the output of executing $function(value, key).
```php
Collection::from([1, 3, 3, 2])
    ->map(function ($value) {
        return $value + 1;
    })
    ->toArray() //[2, 4, 4, 3]
```
```php
toArray(map([1, 3, 3, 2], '\DusanKasan\Knapsack\increment')); //[2, 4, 4, 3]
```

#### mapcat(callable $mapper) : Collection
Returns a lazy collection which is a result of calling map($mapper(value, key)) and then flatten(1).
```php
Collection::from([1, 3, 3, 2])
    ->mapcat(function ($value) {
        return [[$value]];
    })
    ->toArray(); //[[1], [3], [3], [2]]
```
```php
Collection::from([1, 3, 3, 2])
    ->mapcat(function ($key, $value) {
        return [[$key]];
    })
    ->toArray(); //[[0], [1], [2], [3]]
```
```php
toArray(mapcat([1, 3, 3, 2], function ($value) {return [[$value]];})); //[[1], [3], [3], [2]]
``` 

#### only(array|Traversable $keys) : Collection
Returns a lazy collection of items associated to any of the keys from $keys.
```php
Collection::from(['a' => 1, 'b' => 2])
    ->only(['b'])
    ->toArray(); //['b' => 2]
```
```php
toArray(only(['a' => 1, 'b' => 2], ['b'])); //['b' => 2]
```

#### partition(int $numberOfItems, int $step = 0, Traversable|array $padding = []) : Collection
Returns a lazy collection of collections of $numberOfItems items each, at $step step apart. If $step is not supplied, defaults to $numberOfItems, i.e. the partitionsdo not overlap. If a $padding collection is supplied, use its elements asnecessary to complete last partition up to $numberOfItems items. In case there are not enough padding elements, return a partition with less than $numberOfItems items.
```php
Collection::from([1, 3, 3, 2])
    ->partition(3, 2, [0, 1])
    ->toArray(); //[[1, 3, 3], [2 => 3, 3 => 2, 0 => 0]]
```
```php
Collection::from([1, 3, 3, 2])
    ->partition(3, 2)
    ->toArray(); //[[1, 3, 3], [2 => 3, 3 => 2]]
```
```php
Collection::from([1, 3, 3, 2])
    ->partition(3)
    ->toArray(); //[[1, 3, 3], [3 => 2]]
```
```php
toArray(partition([1, 3, 3, 2], 3)); //[[1, 3, 3], [3 => 2]]
```

#### partitionBy(callable $function) : Collection
Creates a lazy collection of collections created by partitioning this collection every time $function(value, key) will return different result.
```php
Collection::from([1, 3, 3, 2])
    ->partitionBy(function ($v) {
        return $v % 3 == 0;
    })
    ->toArray(); //[[1], [1 => 3, 2 => 3], [3 => 2]]
```
```php
toArray(partitionBy([1, 3, 3, 2], function ($value) {return $value % 3 == 0;})); //[[1], [1 => 3, 2 => 3], [3 => 2]]
```

#### pluck(mixed $key) : Collection
Returns a lazy collection by picking a $key key from each sub-collection of $collection.
```php
Collection::from([['a' => 1], ['a' => 2,  'b' => 3]])
    ->pluck('a')
    ->toArray(); //[1, 2]
```
```php
toArray(pluck([['a' => 1], ['a' => 2,  'b' => 3]], 'a')); //[1, 2]
```

#### prepend(mixed $item, mixed $key = null) : Collection
Returns a lazy collection of items of this collection with $item added as first element. Its key will be $key. If $key is not provided, its key will be the next numerical index.
```php
Collection::from([1, 3, 3, 2])
    ->prepend(1)
    ->values() //both 1 have 0 key
    ->toArray(); //[1, 1, 3, 3, 2]
```
```php
Collection::from([1, 3, 3, 2])
    ->prepend('a', 1)
    ->toArray(); //['a' => 1, 0 => 1, 1 => 3, 2 => 3, 3 => 2]
```

#### realize() : Collection
Realizes collection - turns lazy collection into non-lazy one by iterating over it and storing the key/values.
```php
$helper->setValue(1); 

$realizedCollection = Collection::from([1, 3, 3, 2])
    ->map(function ($item) use ($helper) {return $helper->getValue() + $item;})
    ->realize();
    
$helper->setValue(2);
$realizedCollection->toArray([2, 4, 4, 3]);
```
```php
toArray(realize([1, 3, 3, 2])); //[1, 3, 3, 2]
```

#### reduce(callable $function, mixed $start) : mixed
Reduces the collection to single value by iterating over the collection and calling $function(tmp, value, key) while passing $start and current key/item as parameters. The output of callable is used as $start in next iteration. The output of callable on last element is the return value of this function.
```php
Collection::from([1, 3, 3, 2])
    ->reduce(function ($tmp, $i) {
       return $tmp + $i;
    }, 0); //9
```
```php
reduce([1, 3, 3, 2], function ($tmp, $value) {return $tmp + $value;}, 0); //9
```

#### reduceRight(callable $function, mixed $start) : mixed
Like reduce, but walks from last item to the first one.
```php
Collection::from([1, 3, 3, 2])
    ->reduceRight(function ($tmp, $i) {
       return $tmp + $i;
    }, 0); //9
```
```php
reduceRight([1, 3, 3, 2], function ($tmp, $value) {return $tmp + $value;}, 0); //9
```

#### reductions(callable $reduction, $start) : Collection
Returns a lazy collection of reduction steps.
```php
Collection::from([1, 3, 3, 2])
    ->reductions(function ($tmp, $i) {
        return $tmp + $i;
    }, 0)
    ->toArray(); //[1, 4, 7, 9]
```
```php
toArray(reductions([1, 3, 3, 2], function ($tmp, $value) {return $tmp + $value;}, 0)); //[1, 4, 7, 9]
```

#### reject(callable $filter) : Collection
Returns a lazy collection of items for which $filter(value, key) returned false.
```php
Collection::from([1, 3, 3, 2])
    ->reject(function ($value) {
        return $value > 2;
    })
    ->toArray() //[1, 3 => 2]
```
```php
Collection::from([1, 3, 3, 2])
    ->reject(function ($value, $key) {
        return $value > 2 && $key > 1;
    })
    ->toArray() //[1, 1 => 3, 3 => 2]
```
```php
toArray(reject([1, 3, 3, 2], unction ($value) {return $value > 2;})); //[1, 1 => 3, 3 => 2]
```

#### replace(Traversable|array $replacementMap) : Collection
Returns a lazy collection with items from this collection equal to any key in $replacementMap replaced for their value.
```php
Collection::from([1, 3, 3, 2])
    ->replace([3 => 'a'])
    ->toArray(); //[1, 'a', 'a', 2]
```
```php
toArray(replace([1, 3, 3, 2], [3 => 'a'])); //[1, 'a', 'a', 2]
```

#### reverse() : Collection
Returns a non-lazy collection of items in this collection in reverse order.
```php
Collection::from([1, 2, 3])
    ->reverse()
    ->toArray(); //[2 => 3, 1 => 2, 0 => 1]
```
```php
toArray(reverse([1, 2, 3])); //[2 => 3, 1 => 2, 0 => 1]
```


#### second() : mixed
Returns the second item of $collection or throws ItemNotFound if $collection is empty or has 1 item.
```php
Collection::from([1, 3, 3, 2])->second(); //3
```
```php
second([1, 3]); //3
```


#### shuffle() : Collection
Returns a collection of shuffled items from this collection
```php
Collection::from([1, 3, 3, 2])
    ->shuffle()
    ->toArray(); //something like [2 => 3, 0 => 1, 3 => 2, 1 => 3]
```
```php
toArray(shuffle([1, 3, 3, 2])); //something like [2 => 3, 0 => 1, 3 => 2, 1 => 3]
```

#### size() : int
Returns the number of items in this collection.
```php
Collection::from([1, 3, 3, 2])->size(); //4
```
```php
size([1, 3, 3, 2]); //4
```

#### slice(int $from, int $to) : Collection
Returns a lazy collection of items which are part of the original collection from item number $from to item number $to inclusive. The items before $from are also realized, just not returned.
```php
Collection::from([1, 2, 3, 4, 5])
    ->slice(2, 4)
    ->toArray(); //[2 => 3, 3 => 4]
```
```php
Collection::from([1, 2, 3, 4, 5])
    ->slice(4)
    ->toArray(); //[4 => 5]
```
```php
toArray(slice([1, 2, 3, 4, 5], 4)); //[4 => 5]
```

#### some(callable $function) : bool
Returns true if $function(value, key) returns true for at least one item in this collection, false otherwise.
```php
Collection::from([1, 3, 3, 2])
    ->every(function ($value) {
       return $value < 3;
    }); //true
```
```php
Collection::from([1, 3, 3, 2])
    ->find(function ($value, $key) {
       return $value < 4 && $key < 2;
    }, 10); //true
```
```php
some([1, 3, 3 ,2], function ($value) {return $value < 3;}); //true
```

#### sort(callable $function) : Collection
Returns collection sorted using $function(value1, value2, key1, key2). $function should return an integer less than, equal to, or greater than zero if the first argument is considered to be respectively less than, equal to, or greater than the second.
```php
Collection::from([3, 1, 2])
    ->sort(function ($a, $b) {
        return $a > $b;
    })
    ->toArray(); //[1 => 1, 2 => 2, 0 => 3]
```
```php
Collection::from([3, 1, 2])
    ->sort(function ($v1, $v2, $k1, $k2) {
        return $v1 < $v2;
    })
    ->toArray(); //[2 => 2, 1 => 1, 0 => 3]
```
```php
toArray(sort([3, 1, 2], function ($a, $b) {return $a > $b;})); //[1 => 1, 2 => 2, 0 => 3]
```

#### splitAt(int $position) : Collection
Returns a collection of lazy collections: [take($position), drop($position)].
```php
Collection::from([1, 3, 3, 2])
    ->splitAt(2)
    ->toArray(); //[[1, 3], [2 => 3, 3 => 2]]
```
```php
toArray(splitAt([1, 3, 3, 2], 2)); //[[1, 3], [2 => 3, 3 => 2]]
```

#### splitWith(callable $function) : Collection
Returns a collection of lazy collections: [takeWhile($function(value, key)), dropWhile($function(value, key))].
```php
Collection::from([1, 3, 3, 2])
    ->splitWith(function ($value) {
        return $value < 3;
    })
    ->toArray(); //[[1], [1 => 3, 2 => 3, 3 => 2]]
```
```php
Collection::from([1, 3, 3, 2])
    ->splitWith(function ($value, $key) {
        return $key < 2 && $value < 3;
    })
    ->toArray(); //[[1], [1 => 3, 2 => 3, 3 => 2]]
```
```php
toArray(splitWith([1, 3, 3, 2], function ($value) {return $value < 3;})); //[[1], [1 => 3, 2 => 3, 3 => 2]]
```

#### take(int $numberOfItems) : Collection
A form of slice that returns first $numberOfItems items.
```php
Collection::from([1, 2, 3, 4, 5])
    ->take(2)
    ->toArray(); //[1, 2]
```
```php
toArray(take([1, 2, 3, 4, 5], 2)); //[1, 2]
```

#### takeNth(int $step) : Collection
Returns a lazy collection of every nth item in this collection
```php
Collection::from([1, 3, 3, 2])
    ->takeNth(2)
    ->toArray(); //[1, 2 => 3]
```
```php
toArray(takeNth([1, 3, 3, 2], 2)); //[1, 2 => 3]
```

#### takeWhile(callable $function) : Collection
Returns a lazy collection of items from the start of the collection until the first item for which $function(value, key) returns false.
```php
Collection::from([1, 3, 3, 2])
    ->takeWhile(function ($value) {
        return $value < 3;
    })
    ->toArray(); //[1]
```
```php
Collection::from([1, 3, 3, 2])
    ->takeWhile(function ($value, $key) {
        return $key < 2 && $value < 3;
    })
    ->toArray(); //[1]
```
```php
toArray(takeWhile([1, 3, 3, 2], function ($value) {return $value < 3;})); //[1]
```

#### toArray() : array
Converts the collection to array recursively. Obviously this is not lazy since all the items must be realized. Calls iterator_to_array internaly.
```php
Collection::from([1, 3, 3, 2])->toArray(); //[1, 3, 3, 2]
```
```php
toArray([1, 3, 3, 2]); //[1, 3, 3, 2]
```

## Utility functions
These are the functions bundled with Knapsack to make your life easier when transitioning into functional programming.

#### identity(mixed $value)
Returns $value

```php
$value === identity($value); //true
```

#### compare(mixed $a, mixed $b)
Default comparator function. Returns a negative number, zero, or a positive number when $a is logically 'less than', 'equal to', or 'greater than' $b.

```php
compare(1, 2); //-1
```

#### increment(int $value)
Returns value of $value incremented by one.

```php
increment(0) === 1; //true
```

#### decrement(int $value)
Returns value of $value decremented by one.

```php
decrement(2) === 1; //true
```

#### sum(... int $values)
Returns the sum of all arguments.

```php
sum(1, 2, 3) === 6; //true
```

#### max(... int $values)
Returns the max value of all arguments.

```php
sum(1, 5, 3) === 5; //true
```

#### min(... int $values)
Returns the min value of all arguments.

```php
sum(1, 5, 3) === 1; //true
```
