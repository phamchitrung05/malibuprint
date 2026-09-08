<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductConfigTest extends TestCase
{
    #[Test]
    public function product_type_contains_only_cup_and_paper_printing(): void
    {
        $this->assertSame([
            'in_ly' => 'In ly',
            'in_giay' => 'In giấy',
        ], config('product.product_type'));

        $this->assertSame('in_ly', config('product.default_product_type'));
    }
}
