<?php
// components/head.php
// Variables expected: $pageTitle, $bodyClass (optional), $extraCss (optional)
if (!isset($pageTitle)) {
    $pageTitle = APP_NAME;
}

if (!isset($bodyClass)) {
    $bodyClass = '';
}

atlas_send_security_headers('html');
$csrfMetaToken = class_exists('Auth') ? Auth::csrfToken() : '';
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Atlas Talents - Plateforme de detection de talents sportifs par intelligence artificielle.">
<?php if ($csrfMetaToken !== ''): ?><meta name="csrf-token" content="<?= htmlspecialchars($csrfMetaToken) ?>"><?php endif; ?>
<title><?= htmlspecialchars($pageTitle) ?> - <?= APP_NAME ?></title>

<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">

<!-- CSS -->
<link rel="stylesheet" href="<?= APP_URL ?>/public/css/global.css">
<?php if (!empty($extraCss)): foreach ($extraCss as $css): ?>
<link rel="stylesheet" href="<?= APP_URL ?>/public/css/<?= $css ?>">
<?php endforeach; endif; ?>

<!-- Favicon -->
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'><rect width='24' height='24' rx='6' fill='%23C8102E'/><circle cx='12' cy='12' r='7' stroke='white' stroke-width='2' fill='none'/><circle cx='12' cy='12' r='3.5' stroke='white' stroke-width='2' fill='none'/><circle cx='12' cy='12' r='1.2' fill='white'/></svg>">
</head>
<body class="<?= htmlspecialchars($bodyClass) ?>">
