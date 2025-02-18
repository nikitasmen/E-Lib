<?php
require_once(__DIR__ . '/ApiRouter.php');
require_once(__DIR__ . '/PageRouter.php');

class Router {
    private $apiRouter;
    private $pageRouter;
    private $baseUrl;

    public function __construct($baseUrl = '/E-Lib') {
        $this->apiRouter = new ApiRouter();
        $this->pageRouter = new PageRouter();
        $this->baseUrl = $baseUrl;
    }

    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Debugging output
        echo "Original Path: $path";

        // Update path to remove the base URL if it exists
        if (strpos($path, $this->baseUrl) === 0) {
            $path = substr($path, strlen($this->baseUrl));
        }

        // Debugging output
        echo "Updated Path: $path";

        if (strpos($path, '/api') === 0) {
            $this->apiRouter->handleRequest($method, $path);
        } else {
            $this->pageRouter->handleRequest($path);
        }
    }
}

