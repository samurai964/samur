<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

require_once __DIR__ . '/../core/Router.php';
require_once __DIR__ . '/../core/EnterpriseMiddleware.php';
require_once __DIR__ . '/../core/CSRF.php';
require_once __DIR__ . '/../core/Logger.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/bootstrap.php';

$router = new Router();

/* ========================================
   SETTINGS & LANGUAGES
======================================== */
$router->get('/email-settings', 'AdminController@emailSettingsPage');
$router->get('/api-settings', 'AdminController@apiSettingsPage');
$router->get('/design', 'AdminController@designSettingsPage');
$router->get('/home-settings', 'AdminController@homeSettingsPage');

$router->get('/languages', 'AdminController@languagesPage');
$router->get('/change-lang', 'AdminController@changeLanguage');
$router->post('/install-language', 'AdminController@installLanguage');

$router->get('/languages-list', 'AdminController@languagesList');
$router->get('/language-packages', 'AdminController@languagePackages');

$router->get('/upload-language', 'AdminController@uploadLanguagePage');
$router->post('/upload-language', 'AdminController@uploadLanguage');

/* ========================================
   DASHBOARD & USERS
======================================== */
$router->get('/', 'AdminController@dashboard');
$router->get('/users', 'AdminController@users');
$router->get('/services', 'AdminController@services');
$router->get('/test-user', 'UserController@test');
$router->get('/ticker', 'AdminController@tickerPage');

/* CRUD */
$router->get('/users/create', 'AdminController@createUser');
$router->post('/users/store', 'AdminController@storeUser');

$router->get('/users/edit', 'AdminController@editUser');
$router->post('/users/update', 'AdminController@updateUser');

$router->get('/users/delete', 'AdminController@deleteUser');
$router->get('/users/toggle', 'AdminController@toggleUser');

/* ========================================
   SETTINGS
======================================== */
$router->get('/settings', 'AdminController@settingsPage');
$router->get('/system-settings', 'AdminController@systemSettingsPage');

/* ========================================
   DISPATCH
======================================== */

$uri = $_SERVER['REQUEST_URI'];

// حذف /admin من البداية
if (strpos($uri, '/admin') === 0) {
    $uri = substr($uri, 6);
}

$router->dispatch($uri);
