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
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/css/alertify.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/css/themes/bootstrap.min.css" />

<!-- Icones -->
<link href="<?= base_url('assets/images/icon.png') ?>" rel="icon" media="(prefers-color-scheme: light)">
<link href="<?= base_url('assets/images/icon_neg.png') ?>" rel="icon" media="(prefers-color-scheme: dark)">

<link rel="stylesheet" href="<?= base_url('assets/css/layout.css') ?>">
    
    <?= $this->renderSection('styles') ?>
</head>
<body>
	<!-- Sidebar -->
	<div class="d-flex flex-column flex-shrink-0 p-3 text-white bg-dark sidebar" id="sidebar">
		<a href="<?= base_url('dashboard') ?>" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none sidebar-brand"> <img src="<?= base_url('assets/images/logo.png') ?>" alt="CANNAL Mídias Digitais" class="img-fluid">
		</a>
		<hr>
        <?php $activeMenu = $activeMenu ?? ''; ?>
        
        <ul class="nav nav-pills flex-column mb-auto sidebar-menu">
			<li class="nav-item"><a href="<?= base_url('dashboard') ?>" class="nav-link <?= $activeMenu === 'dashboard' ? 'active' : '' ?> sidebar-menu-item"> <i class="fas fa-home fa-fw bi me-2"></i> <span>Dashboard</span>
			</a></li>
			<li class="nav-item"><a href="<?= base_url('campaigns') ?>" class="nav-link <?= $activeMenu === 'campaigns' ? 'active' : '' ?> sidebar-menu-item"> <i class="fas fa-bullhorn fa-fw bi me-2"></i> <span>Campanhas</span>
			</a></li>
			<li class="nav-item"><a href="<?= base_url('messages') ?>" class="nav-link <?= $activeMenu === 'messages' ? 'active' : '' ?> sidebar-menu-item"> <i class="fas fa-paper-plane fa-fw bi me-2"></i> <span>Mensagens</span>
			</a></li>
			<li class="nav-item"><a href="<?= base_url('contacts') ?>" class="nav-link <?= $activeMenu === 'contacts' ? 'active' : '' ?> sidebar-menu-item"> <i class="fas fa-users fa-fw bi me-2"></i> <span>Contatos</span>
			</a></li>
			<li class="nav-item"><a href="<?= base_url('contact-lists') ?>" class="nav-link <?= $activeMenu === 'contact_lists' ? 'active' : '' ?> sidebar-menu-item"> <i class="fas fa-list-ul fa-fw bi me-2"></i> <span>Listas</span>
			</a></li>
			<li class="nav-item"><a href="<?= base_url('templates') ?>" class="nav-link <?= $activeMenu === 'templates' ? 'active' : '' ?> sidebar-menu-item"> <i class="fas fa-file-code fa-fw bi me-2"></i> <span>Templates</span>
			</a></li>
			<li class="nav-item"><a href="<?= base_url('senders') ?>" class="nav-link <?= $activeMenu === 'senders' ? 'active' : '' ?> sidebar-menu-item"> <i class="fas fa-at fa-fw bi me-2"></i> <span>Remetentes</span>
			</a></li>
			<li class="nav-item"><a href="<?= base_url('tracking') ?>" class="nav-link <?= $activeMenu === 'tracking' ? 'active' : '' ?> sidebar-menu-item"> <i class="fas fa-chart-line fa-fw bi me-2"></i> <span>Análises</span>
			</a></li>
			<li class="nav-item"><a href="<?= base_url('receita') ?>" class="nav-link <?= $activeMenu === 'receita' ? 'active' : '' ?> sidebar-menu-item"> <i class="fas fa-database fa-fw bi me-2"></i> <span>Receita</span>
			</a></li>
			<li class="nav-item"><a href="<?= base_url('settings') ?>" class="nav-link <?= $activeMenu === 'settings' ? 'active' : '' ?> sidebar-menu-item"> <i class="fas fa-cog fa-fw bi me-2"></i> <span>Configurações</span>
			</a></li>
		</ul>
		<hr>
                <div class="dropdown">
                        <?php
                        $avatarPath = $userAvatar ?? session('user_avatar');
                        $avatarSrc = ($avatarPath && str_starts_with((string) $avatarPath, 'http'))
                            ? $avatarPath
                            : base_url($avatarPath ?: 'assets/images/icon_neg.png');
                        $displayName = $userName ?? session('user_name') ?? 'Mailer';
                        ?>
                        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false"> <img src="<?= esc($avatarSrc) ?>" alt="" width="32" height="32" class="rounded-circle me-2"> <strong><?= esc($displayName) ?></strong>
                        </a>
			<ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser">
				<li><a class="dropdown-item" href="<?= base_url('profile') ?>"><i class="fas fa-user me-2"></i> Perfil</a></li>
				<li><hr class="dropdown-divider"></li>
				<li><a class="dropdown-item" href="<?= base_url('logout') ?>"><i class="fas fa-sign-out-alt me-2"></i> Sair</a></li>
			</ul>
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
