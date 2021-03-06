<?php

namespace Tests\FrameworkBundle\Unit\Model\Pricing;

use PHPUnit\Framework\TestCase;
use Shopsys\FrameworkBundle\Model\Pricing\Price;
use Shopsys\FrameworkBundle\Model\Pricing\PricingService;

class PricingServiceTest extends TestCase
{
    /**
     * @dataProvider getMinimumPriceProvider
     * @param array $prices
     * @param mixed $minimumPrice
     */
    public function testGetMinimumPrice(array $prices, $minimumPrice)
    {
        $pricingService = new PricingService();

        $this->assertEquals($minimumPrice, $pricingService->getMinimumPriceByPriceWithoutVat($prices));
    }

    public function getMinimumPriceProvider()
    {
        return [
            [
                'prices' => [
                    new Price(20, 30),
                    new Price(10, 15),
                    new Price(100, 120),
                ],
                'minimumPrice' => new Price(10, 15),
            ],
            [
                'prices' => [
                    new Price(10, 15),
                ],
                'minimumPrice' => new Price(10, 15),
            ],
            [
                'prices' => [
                    new Price(10, 15),
                    new Price(10, 15),
                ],
                'minimumPrice' => new Price(10, 15),
            ],
        ];
    }

    public function testGetMinimumPriceEmptyArray()
    {
        $pricingService = new PricingService();

        $this->expectException(\Shopsys\FrameworkBundle\Model\Pricing\Exception\InvalidArgumentException::class);
        $pricingService->getMinimumPriceByPriceWithoutVat([]);
    }

    /**
     * @dataProvider getArePricesDifferentProvider
     * @param array $prices
     * @param mixed $arePricesDifferent
     */
    public function testArePricesDifferent(array $prices, $arePricesDifferent)
    {
        $pricingService = new PricingService();

        $this->assertSame($arePricesDifferent, $pricingService->arePricesDifferent($prices));
    }

    public function getArePricesDifferentProvider()
    {
        return [
            [
                'prices' => [
                    new Price(100, 120),
                    new Price(100, 120),
                ],
                'arePricesDifferent' => false,
            ],
            [
                'prices' => [
                    new Price(100, 120),
                ],
                'arePricesDifferent' => false,
            ],
            [
                'prices' => [
                    new Price(100, 120),
                    new Price('100', '120'),
                ],
                'arePricesDifferent' => true,
            ],
            [
                'prices' => [
                    new Price(200, 240),
                    new Price(100, 120),
                ],
                'arePricesDifferent' => true,
            ],
        ];
    }

    public function testArePricesDifferentEmptyArray()
    {
        $pricingService = new PricingService();

        $this->expectException(\Shopsys\FrameworkBundle\Model\Pricing\Exception\InvalidArgumentException::class);
        $pricingService->arePricesDifferent([]);
    }
}
