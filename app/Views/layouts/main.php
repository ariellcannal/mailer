<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Mailer' ?> - Sistema de Email Marketing</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Alertify CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/css/alertify.min.css"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/css/themes/bootstrap.min.css"/>
    
    <!-- Icones -->
    <link href="<?= base_url('assets/images/icon.png') ?>" rel="icon" media="(prefers-color-scheme: light)">
	<link href="<?= base_url('assets/images/icon_neg.png') ?>" rel="icon" media="(prefers-color-scheme: dark)">

    <link rel="stylesheet" href="<?= base_url('assets/css/layout.css') ?>">
    
    <?= $this->renderSection('styles') ?>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar d-flex flex-column flex-shrink-0 p-3" id="sidebar">
        <a href="<?= base_url('dashboard') ?>" class="sidebar-brand d-flex align-items-center mb-3 text-decoration-none text-white">
            <img src="<?= base_url('assets/images/logo.png') ?>" alt="CANNAL Mídias Digitais" class="img-fluid">
        </a>
        <?php $activeMenu = $activeMenu ?? ''; ?>
        <hr class="sidebar-divider">
        <ul class="nav nav-pills flex-column mb-auto sidebar-menu">
            <li class="nav-item">
                <a href="<?= base_url('dashboard') ?>" class="sidebar-menu-item nav-link <?= $activeMenu === 'dashboard' ? 'active' : '' ?>">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= base_url('campaigns') ?>" class="sidebar-menu-item nav-link <?= $activeMenu === 'campaigns' ? 'active' : '' ?>">
                    <i class="fas fa-bullhorn"></i>
                    <span>Campanhas</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= base_url('messages') ?>" class="sidebar-menu-item nav-link <?= $activeMenu === 'messages' ? 'active' : '' ?>">
                    <i class="fas fa-paper-plane"></i>
                    <span>Mensagens</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= base_url('contacts') ?>" class="sidebar-menu-item nav-link <?= $activeMenu === 'contacts' ? 'active' : '' ?>">
                    <i class="fas fa-users"></i>
                    <span>Contatos</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= base_url('contact-lists') ?>" class="sidebar-menu-item nav-link <?= $activeMenu === 'contact_lists' ? 'active' : '' ?>">
                    <i class="fas fa-list-ul"></i>
                    <span>Listas</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= base_url('templates') ?>" class="sidebar-menu-item nav-link <?= $activeMenu === 'templates' ? 'active' : '' ?>">
                    <i class="fas fa-file-code"></i>
                    <span>Templates</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= base_url('senders') ?>" class="sidebar-menu-item nav-link <?= $activeMenu === 'senders' ? 'active' : '' ?>">
                    <i class="fas fa-at"></i>
                    <span>Remetentes</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= base_url('tracking') ?>" class="sidebar-menu-item nav-link <?= $activeMenu === 'tracking' ? 'active' : '' ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Análises</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= base_url('settings') ?>" class="sidebar-menu-item nav-link <?= $activeMenu === 'settings' ? 'active' : '' ?>">
                    <i class="fas fa-cog"></i>
                    <span>Configurações</span>
                </a>
            </li>
        </ul>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Header -->
        <div class="top-header">
            <div class="d-flex align-items-center">
                <div class="mobile-menu-toggle me-3" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </div>
                <img src="<?= base_url('assets/images/icon.png') ?>" alt="Ícone CANNAL" class="header-icon me-2">
                <h5 class="mb-0 text-primary"><?= mb_strtoupper($pageTitle ?? 'Dashboard') ?></h5>
            </div>
            
            <div class="d-flex align-items-center">
                <div class="dropdown">
                    <button class="btn btn-link text-dark dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle fa-lg"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?= base_url('profile') ?>"><i class="fas fa-user me-2"></i> Perfil</a></li>
                        <li><a class="dropdown-item" href="<?= base_url('settings') ?>"><i class="fas fa-cog me-2"></i> Configurações</a></li>
                        <li><a class="dropdown-item" href="<?= base_url('profile#password') ?>"><i class="fas fa-key me-2"></i> Alterar senha</a></li>
                        <li>
                            <form action="<?= base_url('profile/unlink-google') ?>" method="POST" class="px-3 py-1">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-link dropdown-item text-danger p-0"><i class="fab fa-google me-2"></i> Desvincular Google</button>
                            </form>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= base_url('logout') ?>"><i class="fas fa-sign-out-alt me-2"></i> Sair</a></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Page Content -->
        <div class="page-content">
            <?= $this->renderSection('content') ?>
        </div>
    </div>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Alertify JS -->
    <script src="https://cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/alertify.min.js"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.umd.min.js"></script>
    <script src="<?= base_url('assets/js/layout.js') ?>" defer></script>
    
    <?= $this->renderSection('scripts') ?>
</body>
</html>
