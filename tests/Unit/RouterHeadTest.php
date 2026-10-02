<?php

namespace Tests\Unit;

use App\Core\Request;
use App\Core\Router;
use Tests\TestCase;

class HeadProbeController
{
    public static int $calls = 0;

    public function show(Request $request): void
    {
        self::$calls++;
        echo 'the body';
    }
}

class RouterHeadTest extends TestCase
{
    private function dispatch(string $method): string
    {
        HeadProbeController::$calls = 0;
        $router = new Router();
        $router->get('/probe', [HeadProbeController::class, 'show']);

        $level = ob_get_level();
        ob_start();
        $router->dispatch(new Request($method, '/probe', [], [], []));
        while (ob_get_level() > $level + 1) {
            ob_end_flush(); // flush through the HEAD body-discarding callback
        }
        return (string) ob_get_clean();
    }

    public function testGetReturnsTheBody(): void
    {
        $this->assertSame('the body', $this->dispatch('GET'));
    }

    public function testHeadReachesTheGetRouteButSendsNoBody(): void
    {
        $out = $this->dispatch('HEAD');

        $this->assertSame(1, HeadProbeController::$calls, 'HEAD must hit the GET handler instead of 404ing');
        $this->assertSame('', $out);
    }
}
