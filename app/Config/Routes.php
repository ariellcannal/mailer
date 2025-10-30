<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Default route
$routes->get('/', 'DashboardController::index');

// Dashboard
$routes->group('dashboard', function($routes) {
    $routes->get('/', 'DashboardController::index');
    $routes->get('chart-data', 'DashboardController::chartData');
});

// Tracking routes (public)
$routes->get('track/open/(:segment)', 'TrackController::open/$1');
$routes->get('track/click/(:segment)', 'TrackController::click/$1');
$routes->get('webview/(:segment)', 'TrackController::webview/$1');
$routes->match(['get', 'post'], 'optout/(:segment)', 'TrackController::optout/$1');

// Campaigns
$routes->group('campaigns', function($routes) {
    $routes->get('/', 'CampaignController::index');
    $routes->get('create', 'CampaignController::create');
    $routes->post('store', 'CampaignController::store');
    $routes->get('view/(:num)', 'CampaignController::view/$1');
    $routes->get('edit/(:num)', 'CampaignController::edit/$1');
    $routes->post('update/(:num)', 'CampaignController::update/$1');
    $routes->post('delete/(:num)', 'CampaignController::delete/$1');
});

// Messages
$routes->group('messages', function($routes) {
    $routes->get('/', 'MessageController::index');
    $routes->get('create', 'MessageController::create');
    $routes->post('store', 'MessageController::store');
    $routes->get('view/(:num)', 'MessageController::view/$1');
    $routes->get('edit/(:num)', 'MessageController::edit/$1');
    $routes->post('update/(:num)', 'MessageController::update/$1');
    $routes->post('duplicate/(:num)', 'MessageController::duplicate/$1');
    $routes->post('cancel/(:num)', 'MessageController::cancel/$1');
    $routes->post('reschedule/(:num)', 'MessageController::reschedule/$1');
    $routes->post('send/(:num)', 'MessageController::send/$1');
});

// Contacts
$routes->group('contacts', function($routes) {
    $routes->get('/', 'ContactController::index');
    $routes->get('create', 'ContactController::create');
    $routes->post('store', 'ContactController::store');
    $routes->get('view/(:num)', 'ContactController::view/$1');
    $routes->get('edit/(:num)', 'ContactController::edit/$1');
    $routes->post('update/(:num)', 'ContactController::update/$1');
    $routes->post('delete/(:num)', 'ContactController::delete/$1');
    $routes->get('import', 'ContactController::import');
    $routes->post('import-process', 'ContactController::importProcess');
});

// Templates
$routes->group('templates', function($routes) {
    $routes->get('/', 'TemplateController::index');
    $routes->get('create', 'TemplateController::create');
    $routes->post('store', 'TemplateController::store');
    $routes->get('view/(:num)', 'TemplateController::view/$1');
    $routes->get('edit/(:num)', 'TemplateController::edit/$1');
    $routes->post('update/(:num)', 'TemplateController::update/$1');
    $routes->post('delete/(:num)', 'TemplateController::delete/$1');
});

// Senders
$routes->group('senders', function($routes) {
    $routes->get('/', 'SenderController::index');
    $routes->get('create', 'SenderController::create');
    $routes->post('store', 'SenderController::store');
    $routes->get('view/(:num)', 'SenderController::view/$1');
    $routes->get('edit/(:num)', 'SenderController::edit/$1');
    $routes->post('update/(:num)', 'SenderController::update/$1');
    $routes->post('delete/(:num)', 'SenderController::delete/$1');
    $routes->post('verify/(:num)', 'SenderController::verify/$1');
    $routes->post('check-dns/(:num)', 'SenderController::checkDNS/$1');
});

// Tracking & Analytics
$routes->group('tracking', function($routes) {
    $routes->get('/', 'TrackingController::index');
    $routes->get('opens', 'TrackingController::opens');
    $routes->get('clicks', 'TrackingController::clicks');
    $routes->get('bounces', 'TrackingController::bounces');
    $routes->get('optouts', 'TrackingController::optouts');
});

// Settings
$routes->group('settings', function($routes) {
    $routes->get('/', 'SettingsController::index');
    $routes->post('update', 'SettingsController::update');
    $routes->get('ses-limits', 'SettingsController::sesLimits');
});

// Queue processing (CLI or cron)
$routes->cli('queue/process', 'QueueController::process');

// Auth routes (to be implemented)
$routes->get('login', 'AuthController::login');
$routes->get('logout', 'AuthController::logout');
$routes->get('auth/google', 'AuthController::google');
$routes->get('auth/google/callback', 'AuthController::googleCallback');
$routes->get('profile', 'ProfileController::index');
