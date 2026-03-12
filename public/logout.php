<?php

require_once __DIR__ . '/../src/Core/Auth.php';

Auth::logout();

header('Location: /login.php');
exit;