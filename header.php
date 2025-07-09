<?php
// header.php
date_default_timezone_set('America/Sao_Paulo');

// Cabeçalhos de segurança (se não forem enviados por .htaccess)
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: no-referrer");
header("Permissions-Policy: camera=(), microphone=(), geolocation=()");
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; img-src 'self' data:; font-src 'self' https://cdn.jsdelivr.net;");

// Se quiser impedir cache em páginas sensíveis (como login)
// header("Cache-Control: no-store, no-cache, must-revalidate");
// header("Pragma: no-cache");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="language" content="pt-BR">
    <meta name="robots" content="noindex, nofollow">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>ON_Proc</title>

    <!-- Estilos e ícones -->
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<header>
    <br>
    <div class="text-center mb-4">
        <h1 class="display-4 fw-bold olive">
            <i class="bi bi-shield-lock"></i> ON_<span class="text-dark">Proc</span>
        </h1>
        <p class="text-muted">Sistema de Processo Administrativo Eletrônico</p>
    </div>
</header>
<main>
