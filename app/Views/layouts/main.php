<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Mailer' ?> - Sistema de Email Marketing</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Alertify CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/css/alertify.min.css"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/css/themes/bootstrap.min.css"/>
    
    <!-- Custom CSS -->
    <style>
        :root {
            --sidebar-width: 260px;
            --header-height: 60px;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f8f9fa;
        }
        
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
            color: #ecf0f1;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-brand {
            padding: 20px;
            font-size: 24px;
            font-weight: 700;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            color: #fff;
        }
        
        .sidebar-brand i {
            color: #3498db;
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .sidebar-menu-item {
            padding: 12px 20px;
            color: #ecf0f1;
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .sidebar-menu-item:hover {
            background: rgba(255,255,255,0.1);
            color: #fff;
            border-left-color: #3498db;
        }
        
        .sidebar-menu-item.active {
            background: rgba(52, 152, 219, 0.2);
            color: #fff;
            border-left-color: #3498db;
        }
        
        .sidebar-menu-item i {
            width: 25px;
            margin-right: 12px;
            font-size: 16px;
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }
        
        .top-header {
            background: #fff;
            height: var(--header-height);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            position: sticky;
            top: 0;
            z-index: 999;
        }
        
        .page-content {
            padding: 30px;
        }
        
        .stat-card {
            background: #fff;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .stat-card-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }
        
        .stat-card-value {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-card-label {
            color: #6c757d;
            font-size: 14px;
        }
        
        .btn-primary {
            background: #3498db;
            border: none;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .card {
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-radius: 10px;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .badge {
            padding: 6px 12px;
            font-weight: 500;
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
        }
    </style>
    
    <?= $this->renderSection('styles') ?>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-envelope"></i> Mailer
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
                <h5 class="mb-0"><?= $pageTitle ?? 'Dashboard' ?></h5>
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
