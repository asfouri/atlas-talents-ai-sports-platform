<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
Auth::init();
Auth::logout($_POST['_token'] ?? null);
