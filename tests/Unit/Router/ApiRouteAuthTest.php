<?php

namespace Tests\Unit\Router;

use App\Middleware\JwtAuthMiddleware;
use App\Router\ApiRouter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Every API route is either behind JwtAuthMiddleware or listed here as public on purpose, so a new route
 * can't ship without a Bearer-token check just because nobody added it to ApiRouter::PROTECTED_ROUTES.
 */
class ApiRouteAuthTest extends TestCase
{
    private const PUBLIC_ROUTES = [
        'GET /api/v1/books',
        'GET /api/v1/books/list',
        'GET /api/v1/books/featured',
        'GET /api/v1/books/categories',
        'GET /api/v1/books/([0-9a-f]{24})',
        // Open-access PDFs are readable signed out; the controller checks loans for lent ones.
        'GET /api/v1/books/([0-9a-f]{24})/file',
        'GET /api/v1/books/([0-9a-f]{24})/thumbnail',
        'GET /api/v1/search/([^/]+)',
        'GET /api/v1/content-search',
        'GET /api/v1/features',
        'GET /api/v1/catalog',
        'GET /api/v1/reviews/([0-9a-f]{24})',
        'POST /api/v1/signup',
        'POST /api/v1/login',
        'GET /api/v1/logout',
        'POST /api/v1/support',
    ];

    /**
     * @return array<string, array{string, string}>
     */
    public static function routes(): array
    {
        $routes = [];
        foreach (ApiRouter::ROUTES as $route) {
            $routes["{$route['method']} {$route['path']}"] = [$route['method'], $route['path']];
        }
        return $routes;
    }

    #[DataProvider('routes')]
    public function testRouteIsProtectedUnlessDeclaredPublic(string $method, string $path): void
    {
        // A concrete URL the route matches.
        $url = str_replace(['([0-9a-f]{24})', '([^/]+)'], [str_repeat('a', 24), 'x'], $path);
        $public = in_array("$method $path", self::PUBLIC_ROUTES, true);

        $this->assertSame(
            !$public,
            (new JwtAuthMiddleware(ApiRouter::PROTECTED_ROUTES))->requiresAuth($method, $url),
            $public
                ? "$method $path is declared public but JwtAuthMiddleware protects it"
                : "$method $path is not in ApiRouter::PROTECTED_ROUTES (or declare it public in this test)"
        );
    }

    public function testEveryPublicRouteExists(): void
    {
        $this->assertSame([], array_values(array_diff(self::PUBLIC_ROUTES, array_keys(self::routes()))));
    }
}
