<?php

require_once __DIR__ . '/../controller/ChaveController.php';

/**
 * routes/chaves.php
 *
 * Mapeia os endpoints de chaves para o ChaveController. Assim como
 * routes/auth.php, espera $metodo, $caminho e $pdo já definidos pelo
 * index.php, e retorna true/false para indicar se tratou a rota.
 */

$chaveController = new ChaveController($pdo);

if ($caminho === '/api/chaves' && $metodo === 'GET') {
    $chaveController->listar();
    return true;
}

return false;