<?php

namespace Tests\Unit;

use App\Models\Batch;
use PHPUnit\Framework\TestCase;

class BatchNumberReuseTest extends TestCase
{
    public function test_it_reuses_the_first_missing_batch_number(): void
    {
        $this->assertSame(1, Batch::nextAvailableNumberFromList([]));
        $this->assertSame(3, Batch::nextAvailableNumberFromList([1, 2]));
        $this->assertSame(2, Batch::nextAvailableNumberFromList([1, 3, 4]));
        $this->assertSame(1, Batch::nextAvailableNumberFromList([4, 5, 6]));
        $this->assertSame(4, Batch::nextAvailableNumberFromList([1, 2, 3, 3]));
    }
}