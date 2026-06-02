<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

Auth::check(ROLE_ADMIN);

header('Location: ' . atlasDashboardUrlForRole(ROLE_MANAGER) . '?msg=' . urlencode('Espace manager charge.'));
exit;
