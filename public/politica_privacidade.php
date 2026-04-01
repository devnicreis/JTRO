<?php

require_once __DIR__ . '/../src/Core/SecurityHeaders.php';
require_once __DIR__ . '/../src/Core/PrivacySettings.php';

SecurityHeaders::send();

$pageTitle = 'Politica de Privacidade - JTRO';
$documentoVersao = PrivacySettings::politicaVersao();
$supportContact = PrivacySettings::supportContact();

require_once __DIR__ . '/../src/Views/legal/politica_privacidade.php';
