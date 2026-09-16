<?php

declare(strict_types=1);

namespace App\Core;

use Closure;

final class Pipeline
{
    /** @param array<int, class-string|string> $middleware */
    public function __construct(private readonly array $middleware)
    {
    }

    public function handle(Request $request, Closure $destination): Response
    {
        $next = $destination;

        foreach (array_reverse($this->middleware) as $spec) {
            $instance = class_exists($spec) ? new $spec() : MiddlewareFactory::make($spec);
            $current = $next;
            $next = static function (Request $request) use ($instance, $current): Response {
                return $instance->handle($request, $current);
            };
        }

        return $next($request);
    }
}
