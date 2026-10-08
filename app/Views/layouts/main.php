<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= esc($pageTitle ?? $title ?? 'CANNAL Envios') ?> - CANNAL Envios</title>

<!-- ============================================ -->
<!-- CSS LIBRARIES (CDN) - Centralized -->
<!-- ============================================ -->

<!-- Bootstrap 5.3.3 (Latest Stable) -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

<!-- Google Fonts - Roboto -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600;700&family=Roboto+Condensed:wght@700&display=swap" rel="stylesheet">

<!-- Font Awesome 6.7.2 (Latest) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />

<!-- Alertify 1.14.0 (Latest) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/alertifyjs@1.14.0/build/css/alertify.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/alertifyjs@1.14.0/build/css/themes/bootstrap.min.css" />

<!-- Select2 4.1.0-rc.0 + Bootstrap 5 Theme -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<!-- Tempus Dominus 6.10.4 (Date/Time Picker) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@eonasdan/tempus-dominus@6.10.4/dist/css/tempus-dominus.min.css" crossorigin="anonymous">

<!-- Cropper.js 1.6.2 (Image Cropping) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css">

<!-- CKEditor 5 (Local) -->
<link rel="stylesheet" href="<?= base_url('assets/js/ckeditor5/ckeditor5.css') ?>" />

<!-- ============================================ -->
<!-- CUSTOM CSS - Application Specific -->
<!-- ============================================ -->

<link rel="stylesheet" href="<?= base_url('assets/css/cannal.css') ?>?v=<?= filemtime(FCPATH . 'assets/css/cannal.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/layout.css') ?>?v=<?= filemtime(FCPATH . 'assets/css/layout.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/message-edit.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/message-wizard.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/ckeditor5.css') ?>" />

<!-- Favicons -->
<link href="<?= base_url('assets/images/icon.png') ?>" rel="icon" media="(prefers-color-scheme: light)">
<link href="<?= base_url('assets/images/icon_neg.png') ?>" rel="icon" media="(prefers-color-scheme: dark)">

<!-- Page-specific styles -->
<?= $this->renderSection('styles') ?>
</head>
<body>
	<!-- Sidebar -->
	<div class="d-flex flex-column flex-shrink-0 p-3 text-white bg-dark sidebar" id="sidebar">
		<a href="<?= base_url('dashboard') ?>" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none sidebar-brand">
			<img src="<?= base_url('assets/images/logo.png') ?>" alt="CANNAL Mídias Digitais" class="img-fluid">
		</a>
		<hr>
        <?php $activeMenu = $activeMenu ?? ''; ?>
        
		<?php
		$currentPath = trim(uri_string(), '/');
		$contactsActive = in_array($activeMenu, ['contacts', 'contact_lists'], true);
		$emailActive = in_array($activeMenu, ['messages', 'templates'], true);
		$whatsappSettingsActive = $activeMenu === 'whatsapp-settings';
		$whatsappActive = $activeMenu === 'whatsapp';
		$receitaActive = in_array($activeMenu, ['receita', 'receita-index', 'receita-empresas', 'receita-tasks'], true);
		$settingsActive = in_array($activeMenu, ['settings', 'senders', 'whatsapp-settings'], true);

		$headerLinks = [];
		if ($contactsActive) {
			$contactSubmenu = str_starts_with($currentPath, 'contact-lists') ? 'lists' : (str_starts_with($currentPath, 'contacts/import') ? 'import' : 'contacts');
			$headerLinks = [
				['Contatos', 'contacts', $contactSubmenu === 'contacts'],
				['Listas de Contatos', 'contact-lists', $contactSubmenu === 'lists'],
				['Importar', 'contacts/imports', $contactSubmenu === 'import'],
			];
		} elseif ($emailActive) {
			$headerLinks = [
				['Envios', 'messages', $activeMenu === 'messages'],
				['Templates de E-mail', 'templates', $activeMenu === 'templates'],
			];
		} elseif ($whatsappActive) {
			$headerLinks = [
				['Envios', 'whatsapp', $currentPath === 'whatsapp'],
				['Templates do WhatsApp', 'whatsapp/templates', str_starts_with($currentPath, 'whatsapp/templates')],
			];
		} elseif ($receitaActive) {
			$receitaSubmenu = str_starts_with($currentPath, 'receita/tasks') ? 'tasks' : (str_starts_with($currentPath, 'receita/empresas') || str_starts_with($currentPath, 'receita/empresa/') ? 'imported' : 'new');
			$headerLinks = [
				['Nova Importação', 'receita', $receitaSubmenu === 'new'],
				['Tarefas', 'receita/tasks', $receitaSubmenu === 'tasks'],
				['Importados', 'receita/empresas', $receitaSubmenu === 'imported'],
			];
		} elseif ($settingsActive) {
			$headerLinks = [
				['SMTP', 'settings', $activeMenu === 'settings'],
				['Remetentes', 'senders', $activeMenu === 'senders'],
				['WhatsApp', 'settings/whatsapp', $whatsappSettingsActive],
			];
		}
		?>
		<ul class="nav nav-pills flex-column mb-auto sidebar-menu">
			<li class="nav-item"><a href="<?= base_url('dashboard') ?>" class="nav-link <?= $activeMenu === 'dashboard' ? 'active' : '' ?> sidebar-menu-item"><i class="fas fa-home fa-fw"></i><span>Dashboard</span></a></li>
			<li class="nav-item"><a href="<?= base_url('campaigns') ?>" class="nav-link <?= $activeMenu === 'campaigns' ? 'active' : '' ?> sidebar-menu-item"><i class="fas fa-bullhorn fa-fw"></i><span>Campanhas</span></a></li>
			<li class="nav-item"><a href="<?= base_url('contacts') ?>" class="nav-link <?= $contactsActive ? 'active' : '' ?> sidebar-menu-item"><i class="fas fa-users fa-fw"></i><span>Contatos</span></a></li>
			<li class="sidebar-divider" aria-hidden="true"><hr></li>
			<li class="nav-item"><a href="<?= base_url('messages') ?>" class="nav-link <?= $emailActive ? 'active' : '' ?> sidebar-menu-item"><i class="fas fa-envelope fa-fw"></i><span>E-mail</span></a></li>
			<li class="nav-item"><a href="<?= base_url('whatsapp') ?>" class="nav-link <?= $whatsappActive ? 'active' : '' ?> sidebar-menu-item"><i class="fab fa-whatsapp fa-fw"></i><span>WhatsApp</span></a></li>
			<li class="sidebar-divider" aria-hidden="true"><hr></li>
			<li class="nav-item"><a href="<?= base_url('receita') ?>" class="nav-link <?= $receitaActive ? 'active' : '' ?> sidebar-menu-item"><i class="fas fa-database fa-fw"></i><span>Importar da RFB</span></a></li>
			<li class="sidebar-divider" aria-hidden="true"><hr></li>
			<li class="nav-item"><a href="<?= base_url('settings') ?>" class="nav-link <?= $settingsActive ? 'active' : '' ?> sidebar-menu-item"><i class="fas fa-cog fa-fw"></i><span>Configurações</span></a></li>
		</ul>
		<hr>
		<div class="dropdown">
            <?php
            $avatarPath = $userAvatar ?? session('user_avatar');
            $avatarSrc = ($avatarPath && str_starts_with((string) $avatarPath, 'http')) ? $avatarPath : base_url($avatarPath ?: 'assets/images/icon_neg.png');
			$displayName = $userName ?? session('user_name') ?? 'CANNAL Envios';
			$loggedProfiles = (array) session('logged_profiles');
			$activeUserId = (int) session('user_id');
			$addProfileUrl = base_url('login') . '?add_profile=1&redirect=' . rawurlencode(current_url());
            ?>
            <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
            	<img src="<?= esc($avatarSrc) ?>" alt="" width="32" height="32" class="rounded-circle me-2">
            	<strong><?= esc($displayName) ?></strong>
			</a>
			<ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser">
				<li><h6 class="dropdown-header">Perfis autenticados</h6></li>
				<?php foreach ($loggedProfiles as $profile): ?>
					<li>
						<?php if ((int) $profile['id'] === $activeUserId): ?>
							<span class="dropdown-item active" aria-current="true">
								<i class="fas fa-check me-2"></i><?= esc($profile['name']) ?>
								<small class="d-block ms-4"><?= esc($profile['email']) ?></small>
							</span>
						<?php else: ?>
							<form action="<?= base_url('switch-profile') ?>" method="POST">
								<?= csrf_field() ?>
								<input type="hidden" name="user_id" value="<?= (int) $profile['id'] ?>">
								<button type="submit" class="dropdown-item">
									<i class="fas fa-user me-2"></i><?= esc($profile['name']) ?>
									<small class="d-block ms-4"><?= esc($profile['email']) ?></small>
								</button>
							</form>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
				<li><a class="dropdown-item" href="<?= esc($addProfileUrl) ?>"><i class="fas fa-user-plus me-2"></i>Adicionar Perfil</a></li>
				<li><hr class="dropdown-divider"></li>
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
				<h5 class="mb-0 text-primary"><?= esc($pageTitle ?? 'Dashboard') ?></h5>
			</div>
			<?php if ($headerLinks !== []): ?>
				<nav class="header-subnav" aria-label="Submenu da seção">
					<div class="btn-group btn-group-sm" role="group">
						<?php foreach ($headerLinks as [$label, $path, $isCurrent]): ?>
							<a href="<?= base_url($path) ?>" class="btn <?= $isCurrent ? 'btn-primary' : 'btn-outline-secondary' ?>" <?= $isCurrent ? 'aria-current="page"' : '' ?>><?= esc($label) ?></a>
						<?php endforeach; ?>
					</div>
				</nav>
			<?php endif; ?>
		</div>

		<!-- Page Content -->
		<div class="page-content">
			<?php if (session()->getFlashdata('db_updated')): ?>
				<?php $dbUpdate = session()->getFlashdata('db_updated'); ?>
				<div class="alert alert-info alert-dismissible fade show" role="alert">
					<i class="fas fa-database me-2"></i>
					<strong>Banco de Dados Atualizado!</strong>
					A estrutura do banco de dados foi atualizada para a versão <strong><?= $dbUpdate['to_version'] ?></strong>.
					<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
				</div>
			<?php endif; ?>
            <?= $this->renderSection('content') ?>
        </div>
	</div>

	<!-- ============================================ -->
	<!-- JAVASCRIPT LIBRARIES (CDN) - Centralized -->
	<!-- ============================================ -->

	<!-- jQuery 3.7.1 (Latest 3.x) -->
	<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

	<!-- Bootstrap 5.3.3 JS -->
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

	<!-- Alertify 1.14.0 JS -->
	<script src="https://cdn.jsdelivr.net/npm/alertifyjs@1.14.0/build/alertify.min.js"></script>

	<!-- Select2 4.1.0-rc.0 JS + i18n pt-BR -->
	<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/i18n/pt-BR.js"></script>
	<script src="<?= base_url('assets/js/select2-init.js') ?>"></script>

	<!-- Popper.js 2.11.8 (Required by Tempus Dominus) -->
	<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>

	<!-- Tempus Dominus 6.10.4 JS + Locale pt-BR -->
	<script src="https://cdn.jsdelivr.net/npm/@eonasdan/tempus-dominus@6.10.4/dist/js/tempus-dominus.min.js" crossorigin="anonymous"></script>
	<script src="https://cdn.jsdelivr.net/npm/@eonasdan/tempus-dominus@6.10.4/dist/locales/pt-PT.js" crossorigin="anonymous"></script>

	<!-- Chart.js 4.4.7 (Latest) -->
	<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>

	<!-- Cropper.js 1.6.2 -->
	<script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>

	<!-- CKEditor 5 (Local) -->
	<script src="<?= base_url('assets/js/ckeditor5/ckeditor5.umd.js') ?>"></script>
	<script src="<?= base_url('assets/js/ckeditor5/translations/pt-br.umd.js') ?>"></script>

	<!-- ============================================ -->
	<!-- CUSTOM JAVASCRIPT - Application Specific -->
	<!-- ============================================ -->

	<script src="<?= base_url('assets/js/layout.js') ?>" defer></script>
	<script src="<?= base_url('assets/js/message-edit.js') ?>"></script>
	<script src="<?= base_url('assets/js/rich-editor.js').'?v='.time() ?>" defer></script>
	<script src="<?= base_url('assets/js/messages-form.js') ?>" defer></script>
	<script src="<?= base_url('assets/js/contacts-form.js') ?>" defer></script>
	<script src="<?= base_url('assets/js/contact-phone-mask.js') ?>" defer></script>
	<script src="<?= base_url('assets/js/contacts-index.js') ?>" defer></script>
	<script src="<?= base_url('assets/js/dashboard.js') ?>" defer></script>
	<script src="<?= base_url('assets/js/sender-view.js') ?>" defer></script>
	<script src="<?= base_url('assets/js/settings-limits.js') ?>" defer></script>
	<script src="<?= base_url('assets/js/template-form.js') ?>" defer></script>

	<!-- Page-specific scripts -->
    <?= $this->renderSection('scripts') ?>
</body>
</html>
