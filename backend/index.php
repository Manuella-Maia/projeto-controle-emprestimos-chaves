<?php

/**
 * index.php
 *
 * Ponto de entrada único do backend. Todas as requisições para
 * /api/* devem ser reescritas pelo Apache para cair aqui (ver
 * .htaccess). Este arquivo não conhece regra de negócio nenhuma:
 * ele só descobre "qual caminho e método foram chamados" e entrega
 * a decisão para o arquivo de rota correspondente em routes/.
 *
 *   index.php
 *       ↓ inclui
 *   startup/bootstrap.php   (sessão, headers, conexão $pdo, erros)
 *       ↓ define $metodo e $caminho
 *   routes/*.php            (decide qual Controller chamar)
 *       ↓
 *   controller → service → repository → MySQL
 */

require_once __DIR__ . '/startup/bootstrap.php';

// -----------------------------------------------------------------
// Descobrir método HTTP e caminho da requisição
// -----------------------------------------------------------------
$metodo = $_SERVER['REQUEST_METHOD'];

// parse_url remove a query string (?foo=bar) caso exista.
$caminhoCompleto = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// O projeto pode estar em qualquer subpasta dentro de htdocs (ex.:
// /sa_projeto_integracao/backend/api/login). Para as rotas não
// dependerem de onde o projeto foi colocado, usamos só a parte da
// URL a partir do primeiro "/api" encontrado.
$posicaoApi = strpos($caminhoCompleto, '/api');
$caminho = $posicaoApi !== false ? substr($caminhoCompleto, $posicaoApi) : $caminhoCompleto;

// rtrim remove uma eventual barra final para "/api/chaves/" e
// "/api/chaves" caírem no mesmo caminho.
$caminho = rtrim($caminho, '/');

// -----------------------------------------------------------------
// Arquivos de rota disponíveis
// -----------------------------------------------------------------
// Cada arquivo listado aqui recebe $metodo, $caminho e $pdo (já
// disponíveis neste escopo) e retorna true se tiver tratado a
// requisição, ou false para tentarmos o próximo arquivo.
//
// Conforme as próximas etapas forem implementadas, basta acrescentar
// os novos arquivos aqui, por exemplo:
//   __DIR__ . '/routes/chaves.php',
//   __DIR__ . '/routes/emprestimos.php',
$arquivosDeRota = [
    __DIR__ . '/routes/auth.php',
    __DIR__ . '/routes/chaves.php',
    __DIR__ . '/routes/emprestimos.php',
];

$rotaEncontrada = false;

foreach ($arquivosDeRota as $arquivo) {
    if ((require $arquivo) === true) {
        $rotaEncontrada = true;
        break;
    }
}

// -----------------------------------------------------------------
// Nenhuma rota bateu com o caminho/método pedido
// -----------------------------------------------------------------
if (!$rotaEncontrada) {
    http_response_code(404);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Rota não encontrada.',
    ]);
}