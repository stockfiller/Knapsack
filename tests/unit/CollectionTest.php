<?php

namespace DusanKasan\Knapsack\Tests\Unit;

use DusanKasan\Knapsack\Collection;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
    public function testPartition()
    {
        $collection = new Collection([1, 3, 3, 2]);

        $s1 = $collection->partition(3, 2, [0, 1]);
        $this->assertEquals(2, $s1->size());
        $this->assertEquals([1, 3, 3], $s1->first()->toArray());
        $this->assertEquals([2 => 3, 3 => 2, 0 => 0], $s1->second()->toArray());

        $s2 = $collection->partition(3, 2);
        $this->assertEquals(2, $s2->size());
        $this->assertEquals([1, 3, 3], $s2->first()->toArray());
        $this->assertEquals([2 => 3, 3 => 2], $s2->second()->toArray());

        $s3 = $collection->partition(3);
        $this->assertEquals(2, $s3->size());
        $this->assertEquals([1, 3, 3], $s3->first()->toArray());
        $this->assertEquals([3 => 2], $s3->second()->toArray());

        $s4 = $collection->partition(1, 3);
        $this->assertEquals(2, $s4->size());
        $this->assertEquals([1], $s4->first()->toArray());
        $this->assertEquals([3 => 2], $s4->second()->toArray());

        // same size partition
        $collection2 = Collection::from([1, 2, 3, 4, 5, 6, 7, 8,]);
        $s5 = $collection2->partition(4);
        $this->assertEquals(2, $s5->size());
        $this->assertEquals([1,2,3,4], $s5->first()->toArray());
        $this->assertEquals([4 => 5, 5 => 6, 6 => 7, 7 => 8], $s5->second()->toArray());

        // same size partition with map, mapcat
        $iterator = new \ArrayIterator([1, 2, 3 => 3, 7 => [4,5,6,7,8], 9, 10, 11, 12]);
        $collection3 = Collection::from($iterator);
        $s5 = $collection3
            ->partition(4)
            ->map(fn (Collection $numbers) => $numbers->values()->toArray())
            ->mapcat(function (array $numbers) {
                $data = [];
                if (empty($numbers)) {
                    $data[] = "empty";
                }

                foreach ($numbers as $number) {
                    if (is_array($number)) {
                        $data = [
                            ...$data,
                            ...$number
                        ];
                    } else {
                        $data[] = $number;
                    }
                }

                return $data;
            })
            ->values()
            ->map(fn (int $number) => (string) $number)
            ->toArray();

        $this->assertSame(["1", "2", "3", "4", "5", "6", "7", "8", "9", "10", "11", "12"], $s5);
    }
}