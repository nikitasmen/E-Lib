<?php

namespace App\Router;

// Include all necessary controllers
use App\Controllers\AnnotationController;
use App\Controllers\BookController;
use App\Controllers\FriendController;
use App\Controllers\UserController;
use App\Includes\ResponseHandler;

class ApiRouter
{
    /**
     * Controllers are only built for the route that matched. Auth is not checked here: a protected route
     * also needs an entry in PROTECTED_ROUTES (tests/Unit/Router/ApiRouteAuthTest.php fails until it has one).
     *
     * @var list<array{method: string, path: string, handler: array{class-string, string}}>
     */
    public const ROUTES = [
        ['method' => 'GET', 'path' => '/api/v1/books', 'handler' => [BookController::class, 'getAllBooks']],
        ['method' => 'POST', 'path' => '/api/v1/books', 'handler' => [BookController::class, 'addBook']],
        ['method' => 'GET', 'path' => '/api/v1/books/list', 'handler' => [BookController::class, 'listBooks']],
        ['method' => 'GET', 'path' => '/api/v1/books/featured', 'handler' => [BookController::class, 'featuredBooks']],
        ['method' => 'GET', 'path' => '/api/v1/books/categories', 'handler' => [BookController::class, 'getCategories']],
        ['method' => 'POST', 'path' => '/api/v1/books/physical', 'handler' => [BookController::class, 'addPhysicalBook']],
        ['method' => 'POST', 'path' => '/api/v1/books/upload', 'handler' => [BookController::class, 'uploadBooks']],
        ['method' => 'GET', 'path' => '/api/v1/books/([0-9a-f]{24})', 'handler' => [BookController::class, 'viewBook']],
        ['method' => 'PUT', 'path' => '/api/v1/books/([0-9a-f]{24})', 'handler' => [BookController::class, 'updateBook']],
        ['method' => 'DELETE', 'path' => '/api/v1/books/([0-9a-f]{24})', 'handler' => [BookController::class, 'deleteBook']],

        // New API routes for secure PDF viewing
        ['method' => 'GET', 'path' => '/api/v1/books/([0-9a-f]{24})/file', 'handler' => [BookController::class, 'streamBookFile']],
        [
            'method' => 'GET',
            'path' => '/api/v1/books/([0-9a-f]{24})/thumbnail',
            'handler' => [BookController::class, 'streamBookThumbnail'],
        ],

        ['method' => 'GET', 'path' => '/api/v1/search/([^/]+)', 'handler' => [BookController::class, 'searchBooks']],
        ['method' => 'GET', 'path' => '/api/v1/content-search', 'handler' => [BookController::class, 'contentSearch']],
        ['method' => 'GET', 'path' => '/api/v1/features', 'handler' => [BookController::class, 'features']],
        ['method' => 'GET', 'path' => '/api/v1/catalog', 'handler' => [BookController::class, 'catalog']],

        ['method' => 'POST', 'path' => '/api/v1/reviews', 'handler' => [BookController::class, 'saveReview']],
        ['method' => 'GET', 'path' => '/api/v1/reviews/([0-9a-f]{24})', 'handler' => [BookController::class, 'getReviews']],
        ['method' => 'DELETE', 'path' => '/api/v1/reviews/([0-9a-f]{24})', 'handler' => [BookController::class, 'deleteReview']],
        ['method' => 'POST', 'path' => '/api/v1/reviews/([0-9a-f]{24})/replies', 'handler' => [BookController::class, 'addReply']],
        ['method' => 'PUT', 'path' => '/api/v1/replies/([0-9a-f]{24})', 'handler' => [BookController::class, 'updateReply']],
        ['method' => 'DELETE', 'path' => '/api/v1/replies/([0-9a-f]{24})', 'handler' => [BookController::class, 'deleteReply']],

        // The signed-in user's private highlights and notes
        [
            'method' => 'GET',
            'path' => '/api/v1/books/([0-9a-f]{24})/annotations',
            'handler' => [AnnotationController::class, 'listForBook'],
        ],
        [
            'method' => 'GET',
            'path' => '/api/v1/books/([0-9a-f]{24})/annotations/friends',
            'handler' => [AnnotationController::class, 'listFriendsForBook'],
        ],
        [
            'method' => 'POST',
            'path' => '/api/v1/books/([0-9a-f]{24})/annotations',
            'handler' => [AnnotationController::class, 'create'],
        ],
        ['method' => 'PUT', 'path' => '/api/v1/annotations/([0-9a-f]{24})', 'handler' => [AnnotationController::class, 'update']],
        ['method' => 'DELETE', 'path' => '/api/v1/annotations/([0-9a-f]{24})', 'handler' => [AnnotationController::class, 'delete']],

        ['method' => 'GET', 'path' => '/api/v1/books/([0-9a-f]{24})/download', 'handler' => [BookController::class, 'downloadBook']],
        ['method' => 'POST', 'path' => '/api/v1/books/([0-9a-f]{24})/borrow', 'handler' => [BookController::class, 'borrowBook']],
        ['method' => 'POST', 'path' => '/api/v1/books/([0-9a-f]{24})/return', 'handler' => [BookController::class, 'returnBook']],
        ['method' => 'POST', 'path' => '/api/v1/books/([0-9a-f]{24})/renew', 'handler' => [BookController::class, 'renewBook']],
        ['method' => 'POST', 'path' => '/api/v1/books/([0-9a-f]{24})/waitlist', 'handler' => [BookController::class, 'joinWaitlist']],
        [
            'method' => 'POST',
            'path' => '/api/v1/books/([0-9a-f]{24})/waitlist/leave',
            'handler' => [BookController::class, 'leaveWaitlist'],
        ],
        ['method' => 'POST', 'path' => '/api/v1/admin/loans/renew', 'handler' => [BookController::class, 'adminRenew']],

        ['method' => 'POST', 'path' => '/api/v1/signup', 'handler' => [UserController::class, 'handleSignup']],
        ['method' => 'POST', 'path' => '/api/v1/login', 'handler' => [UserController::class, 'handleLogin']],
        ['method' => 'GET', 'path' => '/api/v1/logout', 'handler' => [UserController::class, 'handleLogout']],

        ['method' => 'GET', 'path' => '/api/v1/user/profile', 'handler' => [UserController::class, 'getProfile']],
        ['method' => 'GET', 'path' => '/api/v1/user', 'handler' => [UserController::class, 'getUser']],
        ['method' => 'POST', 'path' => '/api/v1/save-book', 'handler' => [UserController::class, 'saveBook']],
        ['method' => 'POST', 'path' => '/api/v1/remove-book', 'handler' => [UserController::class, 'removeBook']],
        ['method' => 'GET', 'path' => '/api/v1/saved-books', 'handler' => [UserController::class, 'getSavedBooks']],
        ['method' => 'GET', 'path' => '/api/v1/downloaded-books', 'handler' => [UserController::class, 'getDownloadedBooks']],
        ['method' => 'GET', 'path' => '/api/v1/admin/metadata-lookup', 'handler' => [BookController::class, 'metadataLookup']],
        ['method' => 'GET', 'path' => '/api/v1/admin/loans', 'handler' => [BookController::class, 'adminLoans']],
        ['method' => 'POST', 'path' => '/api/v1/admin/loans/checkout', 'handler' => [BookController::class, 'adminCheckOut']],
        ['method' => 'POST', 'path' => '/api/v1/admin/loans/checkin', 'handler' => [BookController::class, 'adminCheckIn']],
        ['method' => 'GET', 'path' => '/api/v1/user/loans', 'handler' => [BookController::class, 'userLoans']],
        ['method' => 'GET', 'path' => '/api/v1/user/notifications', 'handler' => [UserController::class, 'getNotifications']],
        ['method' => 'POST', 'path' => '/api/v1/user/notifications/read', 'handler' => [UserController::class, 'markNotificationsRead']],
        ['method' => 'GET', 'path' => '/api/v1/user/sharing', 'handler' => [FriendController::class, 'getSharing']],
        ['method' => 'POST', 'path' => '/api/v1/user/sharing', 'handler' => [FriendController::class, 'setSharing']],
        ['method' => 'GET', 'path' => '/api/v1/friends', 'handler' => [FriendController::class, 'list']],
        ['method' => 'POST', 'path' => '/api/v1/friends', 'handler' => [FriendController::class, 'request']],
        ['method' => 'POST', 'path' => '/api/v1/friends/([0-9a-f]{24})/accept', 'handler' => [FriendController::class, 'accept']],
        ['method' => 'DELETE', 'path' => '/api/v1/friends/([0-9a-f]{24})', 'handler' => [FriendController::class, 'remove']],
        ['method' => 'GET', 'path' => '/api/v1/friends/([0-9a-f]{24})/library', 'handler' => [FriendController::class, 'library']],
        ['method' => 'GET', 'path' => '/api/v1/admin/logs', 'handler' => [UserController::class, 'viewLogs']],
        ['method' => 'POST', 'path' => '/api/v1/update-profile', 'handler' => [UserController::class, 'updateProfile']],
        ['method' => 'POST', 'path' => '/api/v1/change-password', 'handler' => [UserController::class, 'changePassword']],
        ['method' => 'POST', 'path' => '/api/v1/support', 'handler' => [UserController::class, 'support']]
    ];

    /**
     * Routes JwtAuthMiddleware turns away without a valid Bearer token (plain paths match by prefix, paths
     * with regex characters exactly). Controllers still check who the caller is; this is the outer gate.
     *
     * @var list<array{path: string, method: string}>
     */
    public const PROTECTED_ROUTES = [
        // Protect user-specific and admin API endpoints
        ['path' => '/api/v1/user', 'method' => 'GET'],
        ['path' => '/api/v1/remove-book', 'method' => 'POST'],
        ['path' => '/api/v1/save-book', 'method' => 'POST'],
        ['path' => '/api/v1/saved-books', 'method' => 'GET'],
        ['path' => '/api/v1/downloaded-books', 'method' => 'GET'],
        ['path' => '/api/v1/reviews', 'method' => 'POST'],
        ['path' => '/api/v1/reviews', 'method' => 'DELETE'],
        ['path' => '/api/v1/replies', 'method' => 'PUT'],
        ['path' => '/api/v1/replies', 'method' => 'DELETE'],
        ['path' => '/api/v1/books/([0-9a-f]{24})/download', 'method' => 'GET'],
        ['path' => '/api/v1/books/([0-9a-f]{24})/borrow', 'method' => 'POST'],
        ['path' => '/api/v1/books/([0-9a-f]{24})/return', 'method' => 'POST'],
        // Private highlights and notes
        ['path' => '/api/v1/books/([0-9a-f]{24})/annotations', 'method' => 'GET'],
        ['path' => '/api/v1/books/([0-9a-f]{24})/annotations', 'method' => 'POST'],
        ['path' => '/api/v1/books/([0-9a-f]{24})/annotations/friends', 'method' => 'GET'],
        ['path' => '/api/v1/annotations', 'method' => 'PUT'],
        ['path' => '/api/v1/annotations', 'method' => 'DELETE'],
        // Friends (prefix match covers /api/v1/friends/{id}/accept and /library)
        ['path' => '/api/v1/user/sharing', 'method' => 'POST'],
        ['path' => '/api/v1/friends', 'method' => 'GET'],
        ['path' => '/api/v1/friends', 'method' => 'POST'],
        ['path' => '/api/v1/friends', 'method' => 'DELETE'],
        ['path' => '/api/v1/admin/logs', 'method' => 'GET'],
        ['path' => '/api/v1/admin/loans', 'method' => 'GET'],
        ['path' => '/api/v1/admin/loans', 'method' => 'POST'],
        ['path' => '/api/v1/user/notifications/read', 'method' => 'POST'],
        ['path' => '/api/v1/admin/metadata-lookup', 'method' => 'GET'],
        ['path' => '/api/v1/update-profile', 'method' => 'POST'],
        ['path' => '/api/v1/change-password', 'method' => 'POST'],
        // Book management endpoints (moved from session-based AuthMiddleware for SPA/JWT auth;
        // prefix match also covers /api/v1/books/upload and /api/v1/books/{id})
        ['path' => '/api/v1/books', 'method' => 'POST'],
        ['path' => '/api/v1/books', 'method' => 'PUT'],
        ['path' => '/api/v1/books', 'method' => 'DELETE'],
    ];

    public function handleRequest(string $method, string $path): void
    {

        foreach (self::ROUTES as $route) {
            if ($route['method'] === $method && preg_match("#^{$route['path']}$#", $path, $matches)) {
                array_shift($matches); // Remove the full match from the matches array
                [$class, $action] = $route['handler'];
                (new $class())->$action(...$matches);
                return;
            }
        }
        ResponseHandler::respond(false, 'Not Found', 404);
    }
}
