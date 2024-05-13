<?php

namespace DusanKasan\Knapsack\Tests\Unit;

use DusanKasan\Knapsack\Collection;
use PHPUnit\Framework\TestCase;

final class CollectionTest extends TestCase
{
    public function testPartition(): void
    {
        $collection = new Collection([1, 3, 3, 2]);

        // Test partition with overlap and with padding
        $s1 = $collection->partition(3, 2, [0, 1]);
        $this->assertEquals(2, $s1->size());
        $this->assertEquals([1, 3, 3], $s1->first()->toArray());
        $this->assertEquals([2 => 3, 3 => 2, 0 => 0], $s1->second()->toArray());

        // Test partition with overlap and without padding
        $s2 = $collection->partition(3, 2);
        $this->assertEquals(2, $s2->size());
        $this->assertEquals([1, 3, 3], $s2->first()->toArray());
        $this->assertEquals([2 => 3, 3 => 2], $s2->second()->toArray());

        // Test partition without overlapping partitions
        $s3 = $collection->partition(3);
        $this->assertEquals(2, $s3->size());
        $this->assertEquals([1, 3, 3], $s3->first()->toArray());
        $this->assertEquals([3 => 2], $s3->second()->toArray());

        // Test single item partition with overlap
        $s4 = $collection->partition(1, 3);
        $this->assertEquals(2, $s4->size());
        $this->assertEquals([1], $s4->first()->toArray());
        $this->assertEquals([3 => 2], $s4->second()->toArray());
    }

    public function testPartition_AdditionalTests(): void
    {
        (new Collection([1, 2, 3, 4, 5, 6, 7, 8]))
            ->partition(4)
            ->each(fn (Collection $partition) => $this->assertEquals(4, $partition->size()))
            ->realize();

        /*
        $iterator = new \ArrayIterator([1, 2, 3 => 3, 7 => [4, 5, 6, 7, 8], 9, 10, 11, 12]);
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
        */
    }
}