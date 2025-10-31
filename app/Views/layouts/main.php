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

    <!-- Custom CSS -->
    <style>
        :root {
            --color-primary: #084c6e;
            --color-secondary: #ed6d22;
            --color-danger: #c04c4c;
            --color-white: #f8f8f7;
            --color-dark: #333333;
            --sidebar-width: 260px;
            --header-height: 70px;
        }

        body {
            font-family: Roboto, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
            background-color: var(--color-white);
            color: var(--color-dark);
        }

        h1, h2, h3, h4, h5, h6 {
            text-transform: uppercase;
            font-weight: 700;
            color: var(--color-primary);
        }

        .text-primary {
            color: var(--color-primary) !important;
        }

        .text-secondary {
            color: var(--color-secondary) !important;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: var(--color-primary);
            color: var(--color-white);
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 2px 0 16px rgba(0, 0, 0, 0.15);
        }

        .sidebar-brand {
            padding: 24px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-brand img {
            max-width: 180px;
        }

        .sidebar-menu {
            padding: 24px 0;
        }

        .sidebar-menu-item {
            padding: 14px 24px;
            color: var(--color-white);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }

        .sidebar-menu-item i {
            font-size: 18px;
            color: var(--color-secondary);
        }

        .sidebar-menu-item span {
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.8px;
        }

        .sidebar-menu-item:hover {
            background: rgba(255, 255, 255, 0.08);
            color: var(--color-white);
            border-left-color: var(--color-secondary);
        }

        .sidebar-menu-item.active {
            background: rgba(237, 109, 34, 0.15);
            color: var(--color-white);
            border-left-color: var(--color-secondary);
        }

        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            background: var(--color-white);
        }

        .top-header {
            background: var(--color-white);
            height: var(--header-height);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            border-bottom: 1px solid rgba(8, 76, 110, 0.1);
            position: sticky;
            top: 0;
            z-index: 999;
        }

        .page-content {
            padding: 32px;
        }

        .card {
            border: none;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            border-radius: 16px;
            background: #ffffff;
        }

        .btn-primary {
            background: var(--color-primary);
            border-color: var(--color-primary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary:hover {
            background: #063753;
            border-color: #063753;
        }

        .btn-outline-primary {
            color: var(--color-primary);
            border-color: var(--color-primary);
            text-transform: uppercase;
        }

        .btn-outline-primary:hover {
            background: var(--color-primary);
            color: var(--color-white);
        }

        .btn-secondary {
            background: var(--color-secondary);
            border-color: var(--color-secondary);
        }

        .btn-secondary:hover {
            background: #c85712;
            border-color: #c85712;
        }

        .badge.bg-primary {
            background-color: var(--color-primary) !important;
        }

        .badge.bg-warning {
            background-color: var(--color-secondary) !important;
            color: #fff !important;
        }

        .badge.bg-danger {
            background-color: var(--color-danger) !important;
        }

        .section-title {
            font-size: 1.25rem;
            letter-spacing: 1px;
        }

        .section-subtitle {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--color-primary);
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .status-chip {
            background: rgba(8, 76, 110, 0.06);
            border-radius: 12px;
            padding: 12px 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 500;
        }

        .status-chip.status-success {
            border: 1px solid rgba(8, 76, 110, 0.3);
        }

        .status-chip.status-danger {
            border: 1px solid rgba(192, 76, 76, 0.4);
            color: var(--color-danger);
        }

        .table thead {
            background: rgba(8, 76, 110, 0.05);
        }

        .table thead th {
            color: var(--color-primary);
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .mobile-menu-toggle {
                display: block !important;
            }
        }

        .mobile-menu-toggle {
            display: none;
            cursor: pointer;
            font-size: 24px;
            color: var(--color-primary);
        }

        .header-icon {
            width: 36px;
            height: 36px;
        }
    </style>
    
    <?= $this->renderSection('styles') ?>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <img src="<?= base_url('assets/images/logo.png') ?>" alt="CANNAL Mídias Digitais" class="img-fluid">
        </div>
        <?php $activeMenu = $activeMenu ?? ''; ?>
        <div class="sidebar-menu">
            <a href="<?= base_url('dashboard') ?>" class="sidebar-menu-item <?= $activeMenu === 'dashboard' ? 'active' : '' ?>">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            
            <a href="<?= base_url('campaigns') ?>" class="sidebar-menu-item <?= $activeMenu === 'campaigns' ? 'active' : '' ?>">
                <i class="fas fa-bullhorn"></i>
                <span>Campanhas</span>
            </a>
            
            <a href="<?= base_url('messages') ?>" class="sidebar-menu-item <?= $activeMenu === 'messages' ? 'active' : '' ?>">
                <i class="fas fa-paper-plane"></i>
                <span>Mensagens</span>
            </a>
            
            <a href="<?= base_url('contacts') ?>" class="sidebar-menu-item <?= $activeMenu === 'contacts' ? 'active' : '' ?>">
                <i class="fas fa-users"></i>
                <span>Contatos</span>
            </a>
            
            <a href="<?= base_url('templates') ?>" class="sidebar-menu-item <?= $activeMenu === 'templates' ? 'active' : '' ?>">
                <i class="fas fa-file-code"></i>
                <span>Templates</span>
            </a>
            
            <a href="<?= base_url('senders') ?>" class="sidebar-menu-item <?= $activeMenu === 'senders' ? 'active' : '' ?>">
                <i class="fas fa-at"></i>
                <span>Remetentes</span>
            </a>
            
            <a href="<?= base_url('tracking') ?>" class="sidebar-menu-item <?= $activeMenu === 'tracking' ? 'active' : '' ?>">
                <i class="fas fa-chart-line"></i>
                <span>Análises</span>
            </a>
            
            <a href="<?= base_url('settings') ?>" class="sidebar-menu-item <?= $activeMenu === 'settings' ? 'active' : '' ?>">
                <i class="fas fa-cog"></i>
                <span>Configurações</span>
            </a>
        </div>
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
    
    <script>
        // Toggle sidebar mobile
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('show');
        }
        
        // Configuração padrão do Alertify
        alertify.defaults.theme.ok = "btn btn-primary";
        alertify.defaults.theme.cancel = "btn btn-secondary";
        alertify.defaults.theme.input = "form-control";
    </script>
    
    <?= $this->renderSection('scripts') ?>
</body>
</html>
