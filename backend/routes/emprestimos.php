<?php

require_once __DIR__ . '/../controller/EmprestimoController.php';

/**
 * routes/emprestimos.php
 *
 * Mapeia os endpoints de empréstimos para o EmprestimoController:
 *   POST  /api/emprestimos     -> retirada          (Etapa 7)
 *   PATCH /api/emprestimos     -> devolução          (Etapa 8)
 *   GET   /api/meu-emprestimo  -> empréstimo ativo   (Etapa 9)
 *   GET   /api/historico       -> histórico completo (Etapa 9)
 */

$emprestimoController = new EmprestimoController($pdo);

if ($caminho === '/api/emprestimos' && $metodo === 'POST') {
    $emprestimoController->retirar();
    return true;
}

if ($caminho === '/api/emprestimos' && $metodo === 'PATCH') {
    $emprestimoController->devolver();
    return true;
}

if ($caminho === '/api/meu-emprestimo' && $metodo === 'GET') {
    $emprestimoController->meuEmprestimo();
    return true;
}

if ($caminho === '/api/historico' && $metodo === 'GET') {
    $emprestimoController->historico();
    return true;
}

return false;