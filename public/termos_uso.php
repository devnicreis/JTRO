<?php

require_once __DIR__ . '/../src/Core/SecurityHeaders.php';
require_once __DIR__ . '/../src/Core/PrivacySettings.php';

SecurityHeaders::send();

$pageTitle = 'Termos de Uso - JTRO';
$documentoVersao = PrivacySettings::termosVersao();

require_once __DIR__ . '/../src/Views/legal/termos_uso.php';
