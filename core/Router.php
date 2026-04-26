<?php

require_once __DIR__ . '/Middleware.php';

class Router {

    private $routes = [];

    public function get($uri, $action, $middleware = null) {
        $this->routes['GET'][$uri] = [
            'action' => $action,
            'middleware' => $middleware
        ];
    }

    public function post($uri, $action, $middleware = null) {
        $this->routes['POST'][$uri] = [
            'action' => $action,
            'middleware' => $middleware
        ];
    }

    public function dispatch($uri) {

        $uri = trim(parse_url($uri, PHP_URL_PATH), '/');

        if ($uri === '') {
            $uri = '/';
        } else {
            $uri = '/' . $uri;
        }

        $method = $_SERVER['REQUEST_METHOD'];

        if (!isset($this->routes[$method][$uri])) {
            if ($method === 'POST' && isset($this->routes['GET'][$uri])) {
                $method = 'GET';
            } else {
                http_response_code(404);
                echo "404 Not Found";
                return;
            }
        }

        $route = $this->routes[$method][$uri];

        list($controller, $methodName) = explode('@', $route['action']);

        $controllerFile = __DIR__ . '/../controllers/' . $controller . '.php';

        if (!file_exists($controllerFile)) {
            $moduleName = strtolower(str_replace('Controller', '', $controller));
            $controllerFile = __DIR__ . '/../modules/' . $moduleName . '/' . $controller . '.php';
        }

        if (!file_exists($controllerFile)) {
            die("❌ Controller not found: " . $controllerFile);
        }

        require_once $controllerFile;

        $controllerObj = new $controller();

        if (!method_exists($controllerObj, $methodName)) {
            die("❌ Method not found: " . $methodName);
        }

        $controllerObj->$methodName();
    }
}

?>
