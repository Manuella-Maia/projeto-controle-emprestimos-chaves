<?php

require_once __DIR__ . '/../controller/AuthController.php';

/**
 * routes/auth.php
 *
 * Mapeia os endpoints de autenticação para os métodos do
 * AuthController. Este arquivo só decide "qual método chamar";
 * não interpreta corpo de requisição nem regra de negócio.
 *
 * Espera receber do index.php, já definidos:
 *   $metodo  -> string com o verbo HTTP (GET, POST, ...)
 *   $caminho -> string com o caminho da URL (ex.: "/api/login")
 *   $pdo     -> conexão PDO, vinda do bootstrap.php
 *
 * Retorna true se a rota foi tratada aqui, ou false para o
 * index.php seguir tentando outros arquivos de rota.
 */

$authController = new AuthController($pdo);

if ($caminho === '/api/login' && $metodo === 'POST') {
    $authController->login();
    return true;
}

if ($caminho === '/api/logout' && $metodo === 'POST') {
    $authController->logout();
    return true;
}

return false;