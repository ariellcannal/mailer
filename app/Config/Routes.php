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

// Deploy route
$routes->post('deploy', 'DeployController::index');

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
    $routes->get('preview/(:num)', 'MessageController::preview/$1');
    $routes->get('edit/(:num)', 'MessageController::edit/$1');
    $routes->post('update/(:num)', 'MessageController::update/$1');
    $routes->post('save-progress', 'MessageController::saveProgress');
    $routes->post('duplicate/(:num)', 'MessageController::duplicate/$1');
    $routes->post('cancel/(:num)', 'MessageController::cancel/$1');
    $routes->post('reschedule/(:num)', 'MessageController::reschedule/$1');
    $routes->post('send/(:num)', 'MessageController::send/$1');
    $routes->post('delete/(:num)', 'MessageController::delete/$1');
    $routes->post('convert-to-draft/(:num)', 'MessageController::convertToDraft/$1');
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
    $routes->get('imports', 'ContactController::imports');
    $routes->post('bulk-assign', 'ContactController::bulkAssignLists');
});

// Contact Lists
$routes->group('contact-lists', function($routes) {
    $routes->get('/', 'ContactListController::index');
    $routes->get('create', 'ContactListController::create');
    $routes->get('edit/(:num)', 'ContactListController::edit/$1');
    $routes->get('view/(:num)', 'ContactListController::view/$1');
    $routes->post('store', 'ContactListController::store');
    $routes->post('update/(:num)', 'ContactListController::update/$1');
    $routes->post('delete/(:num)', 'ContactListController::delete/$1');
    $routes->post('detach-contact/(:num)/(:num)', 'ContactListController::detachContact/$1/$2');
    $routes->post('buscar-contatos', 'ContactListController::buscarContatos');
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
    $routes->get('search', 'TemplateController::search');
});

// Receita Federal
$routes->group('receita', function($routes) {
    $routes->get('/', 'ReceitaController::index');                    // Página principal de configuração
    $routes->get('tasks', 'ReceitaController::tasks');                 // Listagem de tarefas
    $routes->get('tasks-data', 'ReceitaController::tasksData');        // Dados das tarefas (JSON)
    $routes->post('schedule', 'ReceitaController::schedule');          // Agendar nova tarefa
    $routes->post('pause-task/(:num)', 'ReceitaController::pauseTask/$1');         // Pausar tarefa
    $routes->post('start-task/(:num)', 'ReceitaController::startTask/$1');         // Iniciar tarefa
    $routes->post('restart-task/(:num)', 'ReceitaController::restartTask/$1');     // Reiniciar tarefa
    $routes->get('duplicate-task/(:num)', 'ReceitaController::duplicateTask/$1');  // Carregar dados para duplicar
    $routes->post('delete-task/(:num)', 'ReceitaController::deleteTask/$1');       // Excluir tarefa
    $routes->get('buscarCnaes', 'ReceitaController::buscarCnaes');     // Busca AJAX para o Select2
    $routes->get('empresas', 'ReceitaController::empresas');           // Página de consulta de empresas
    $routes->get('buscarEmpresas', 'ReceitaController::buscarEmpresas'); // Buscar empresas com filtros
    $routes->get('empresa/(:segment)/(:segment)/(:segment)', 'ReceitaController::empresa/$1/$2/$3'); // Detalhes da empresa
});

// Gerenciador de arquivos
$routes->group('files', function($routes) {
    $routes->get('list', 'FileManagerController::list');
    $routes->post('upload', 'FileManagerController::upload');
});
$routes->get('imagens/(:segment)', 'TrackController::getImage/$1');

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

// Processamento da fila (CLI ou cron)
$routes->cli('queue/process', 'QueueController::process');
$routes->get('queue/process', 'QueueController::process');
$routes->cli('queue/process-bounces', 'QueueController::processBounces');   
$routes->get('queue/process-bounces', 'QueueController::processBounces');

// Auth routes (to be implemented)
$routes->get('login', 'AuthController::login');
$routes->post('login', 'AuthController::authenticate');
$routes->get('register', 'AuthController::registerForm');
$routes->post('register', 'AuthController::register');
$routes->post('auth/forgot-password', 'AuthController::forgotPassword');
$routes->post('auth/reset-password', 'AuthController::resetPassword');
$routes->get('logout', 'AuthController::logout');
$routes->get('auth/google', 'AuthController::google');
$routes->get('auth/google/callback', 'AuthController::googleCallback');
$routes->get('profile', 'ProfileController::index');
$routes->post('profile/update', 'ProfileController::updateProfile');
$routes->post('profile/request-email-code', 'ProfileController::requestEmailCode');
$routes->post('profile/confirm-email', 'ProfileController::confirmEmailChange');
$routes->post('profile/link-google', 'ProfileController::linkGoogle');
$routes->post('profile/change-password', 'ProfileController::changePassword');
$routes->post('profile/unlink-google', 'ProfileController::unlinkGoogle');
