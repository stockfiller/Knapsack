<?php

namespace DusanKasan\Knapsack\Tests\Unit;

use DusanKasan\Knapsack\Collection;
use DusanKasan\Knapsack\Tests\Helpers\FastCar;
use DusanKasan\Knapsack\Tests\Helpers\Part;
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
    }

    public function testMapCat()
    {
        $cars = [
            new FastCar('Ferrari', [new Part('Engine'), new Part('Body'), new Part('Wheels')]),
            new FastCar('Porche', [new Part('Spoiler'), new Part('Mirrors'), new Part('Seats'), new Part("Windshield")]),
        ];

        // test case 1: everything is as expected
        $allParts = Collection::from($cars)
            ->mapcat(fn ($car) => Collection::from($car->parts)->map(fn ($p) => $p->partName))
            ->values()
            ->map(fn ($name) => $name)
            ->toArray();

        $this->assertEquals(["Engine", "Body", "Wheels", "Spoiler", "Mirrors", "Seats", "Windshield"], $allParts);

        // test case 2: well, unsure if its expected behavior
        $lastParts = Collection::from($cars)
            ->mapcat(fn ($car) => Collection::from($car->parts)->map(fn ($p) => $p->partName))
            // notice that this step does not have ->values() . Due to this it replaces values at the same index
            ->map(fn ($name) => $name)
            ->toArray();

        $this->assertEquals(["Spoiler", "Mirrors", "Seats", "Windshield"], $lastParts);


        // test case 3: unexpected behavior
        $collection = Collection::from($cars)
            ->mapcat(fn ($car) => Collection::from($car->parts)->map(fn ($p) => $p->partName))
            ->realize();
        $allValuesWithRealize = $collection->map(fn ($name) => $name)->toArray();
        $this->assertEquals(["Spoiler", "Mirrors", "Seats", "Windshield"], $allValuesWithRealize);


        // test case 4: lets verify if indexBy helps
        $result4 = Collection::from($cars)
            ->mapcat(fn ($car) => Collection::from($car->parts)->map(fn ($p) => $p->partName)->indexBy(fn ($name) => $name))
            ->toArray();
        $this->assertEquals(["Engine" => "Engine", "Body" => "Body", "Wheels" => "Wheels", "Spoiler" => "Spoiler", "Mirrors" => "Mirrors", "Seats" => "Seats", "Windshield" => "Windshield"], $result4);
    }
}