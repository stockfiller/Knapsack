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
    }
}