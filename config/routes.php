<?php

use FastRoute\RouteCollector;

/** @var RouteCollector $route */

// Pages
$route->get('/', 'HomeController@index');
$route->get('/credits', 'CreditsController@index');
$route->get('/health', 'HealthController@index');

// Authentication
$route->get('/login', 'AuthController@login');
$route->post('/login', 'AuthController@postLogin');
$route->get('/logout', 'AuthController@logout');

// OAuth
$route->get('/oauth/{provider}', 'OAuthController@index');
$route->post('/oauth/{provider}/connect', 'OAuthController@connect');
$route->post('/oauth/{provider}/disconnect', 'OAuthController@disconnect');

// User settings
$route->get('/settings/password', 'SettingsController@password');
$route->post('/settings/password', 'SettingsController@savePassword');
$route->get('/settings/oauth', 'SettingsController@oauth');

// Password recovery
$route->get('/password/reset', 'PasswordResetController@reset');
$route->post('/password/reset', 'PasswordResetController@postReset');
$route->get('/password/reset/{token:.+}', 'PasswordResetController@resetPassword');
$route->post('/password/reset/{token:.+}', 'PasswordResetController@postResetPassword');

// Stats
$route->get('/metrics', 'Metrics\\Controller@metrics');
$route->get('/stats', 'Metrics\\Controller@stats');

// News
$route->get('/news', 'NewsController@index');
$route->get('/meetings', 'NewsController@meetings');
$route->get('/news/{id:\d+}', 'NewsController@show');
$route->post('/news/{id:\d+}', 'NewsController@comment');
$route->post('/news/comment/{id:\d+}', 'NewsController@deleteComment');

// FAQ
$route->get('/faq', 'FaqController@index');

// Questions
$route->get('/questions', 'QuestionsController@index');
$route->post('/questions', 'QuestionsController@delete');
$route->get('/questions/new', 'QuestionsController@add');
$route->post('/questions/new', 'QuestionsController@save');

// API
$route->get('/api[/{resource:.+}]', 'ApiController@index');

// Design
$route->get('/design', 'DesignController@index');

// Register new user
$route->get('/register', 'Admin\\CreateUserController@index');
$route->post('/register', 'Admin\\CreateUserController@process');

// Shifts
$route->get('/export_shift/{id:\d+}', 'ShiftExportController@index');
$route->get('/shifts/list', 'ShiftListController@index');
$route->get('/shifts/mine', 'ShiftListController@mine');
$route->get('/shifts/history', 'ShiftListController@history');

// Administration
$route->addGroup(
    '/admin',
    function (RouteCollector $route) {
        // FAQ
        $route->addGroup(
            '/faq',
            function (RouteCollector $route) {
                $route->get('[/{id:\d+}]', 'Admin\\FaqController@edit');
                $route->post('[/{id:\d+}]', 'Admin\\FaqController@save');
            }
        );

        // Log
        $route->get('/logs', 'Admin\\LogsController@index');
        $route->post('/logs', 'Admin\\LogsController@index');

        // Schedule
        $route->addGroup(
            '/schedule',
            function (RouteCollector $route) {
                $route->get('', 'Admin\\Schedule\\ImportSchedule@index');
                $route->get('/edit[/{id:\d+}]', 'Admin\\Schedule\\ImportSchedule@edit');
                $route->post('/edit[/{id:\d+}]', 'Admin\\Schedule\\ImportSchedule@save');
                $route->get('/load/{id:\d+}', 'Admin\\Schedule\\ImportSchedule@loadSchedule');
                $route->post('/import/{id:\d+}', 'Admin\\Schedule\\ImportSchedule@importSchedule');
            }
        );

        // Questions
        $route->addGroup(
            '/questions',
            function (RouteCollector $route) {
                $route->get('', 'Admin\\QuestionsController@index');
                $route->post('', 'Admin\\QuestionsController@delete');
                $route->get('/{id:\d+}', 'Admin\\QuestionsController@edit');
                $route->post('/{id:\d+}', 'Admin\\QuestionsController@save');
            }
        );

        // User
        $route->addGroup(
            '/user/{id:\d+}',
            // Shirts
            function (RouteCollector $route) {
                $route->get('/shirt', 'Admin\\UserShirtController@editShirt');
                $route->post('/shirt', 'Admin\\UserShirtController@saveShirt');
            }
        );

        // User
        $route->addGroup(
            '/users',
            function (RouteCollector $route) {
                $route->get('/import', 'Admin\\ImportUsersCotroller@index');
                $route->post('/import', 'Admin\\ImportUsersCotroller@process');
                $route->get('/new', 'Admin\\CreateUserController@index');
                $route->post('/new', 'Admin\\CreateUserController@process');
            }
        );

        // News
        $route->addGroup(
            '/news',
            function (RouteCollector $route) {
                $route->get('[/{id:\d+}]', 'Admin\\NewsController@edit');
                $route->post('[/{id:\d+}]', 'Admin\\NewsController@save');
            }
        );
    }
);
