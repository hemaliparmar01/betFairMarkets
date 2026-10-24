<?php

namespace Tests\Unit;

use App\Http\Controllers\Users\Football\FootballController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

class PinnacleFairPricesTest extends TestCase
{
    public function test_margin_is_removed_from_complete_corresponding_outcomes(): void
    {
        $controller = (new ReflectionClass(FootballController::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod($controller, 'pinnacleFairPrices');
        $prices = ['home' => 1.8, 'draw' => 3.6, 'away' => 4.2];
        $fair = $method->invoke($controller, $prices, 3);
        $this->assertEqualsWithDelta(1, array_sum(array_map(static fn (float $price): float => 1 / $price, $fair)), 0.000001);
        $this->assertGreaterThan($prices['home'], $fair['home']);
        $this->assertSame([], $method->invoke($controller, ['home' => 1.8, 'away' => 4.2], 3));
        $this->assertSame([], $method->invoke($controller, ['home' => 1.8], 2));
        $this->assertSame([], $method->invoke($controller, ['home' => INF, 'away' => 2.1], 2));
        $this->assertSame([], $method->invoke($controller, ['home' => 0, 'away' => 2.1], 2));
        $twoWay = $method->invoke($controller, ['over' => 1.9, 'under' => 1.9], 2);
        $this->assertEqualsWithDelta(2, $twoWay['over'], 0.000001);
    }
}
