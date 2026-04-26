<?php

session_start();
require_once __DIR__ . '/core/autoload.php';
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/core/Router.php';

/* ========================================
   INIT ROUTER
======================================== */
$router = new Router();

/* ========================================
   FRONT ROUTES
======================================== */
$router->get('/', 'HomeController@index');
$router->get('/services', 'HomeController@services');

/* ========================================
   AUTH ROUTES
======================================== */
$router->get('/login', 'AuthController@login', 'guest');
$router->post('/login', 'AuthController@doLogin', 'guest');
$router->get('/logout', 'AuthController@logout', 'admin');

/* ========================================
   ADMIN ROUTES (🔐 محمية)
======================================== */
$router->get('/admin', 'AdminController@dashboard', 'admin');

/* ========================================
   RUN ROUTER
======================================== */
$router->dispatch($_SERVER['REQUEST_URI']);
?>
