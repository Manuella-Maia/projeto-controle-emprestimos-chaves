<?php

/**
 * bootstrap.php
 *
 * "Ritual de inicialização" da aplicação. Este arquivo não contém
 * rotas nem regras de negócio: ele apenas prepara o ambiente para
 * que o restante do backend (routes -> controller -> service ->
 * repository) possa rodar de forma previsível.
 *
 * Deve ser incluído uma única vez, no topo do index.php, antes de
 * qualquer roteamento acontecer.
 */

// -----------------------------------------------------------------
// 1. Sessão PHP
// -----------------------------------------------------------------
// A autenticação do sistema usa $_SESSION (sem JWT, como definido
// no escopo do projeto). A sessão precisa ser iniciada antes de
// qualquer saída (echo/print), por isso é o primeiro passo aqui.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// -----------------------------------------------------------------
// 2. Cabeçalhos padrão da API
// -----------------------------------------------------------------
// Toda resposta da API é JSON, então já deixamos isso fixo aqui em
// vez de repetir em cada controller.
header('Content-Type: application/json; charset=utf-8');

// Cabeçalhos de CORS: úteis caso o frontend seja aberto por uma
// origem/porta diferente da do Apache (ex.: Live Server). Em um
// ambiente 100% dentro do htdocs isso normalmente nem é necessário,
// mas evita dor de cabeça durante o desenvolvimento.
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Requisições "preflight" (enviadas automaticamente pelo navegador
// antes de um POST/PATCH com JSON) não precisam chegar até as
// rotas: respondemos com 204 e encerramos aqui mesmo.
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// -----------------------------------------------------------------
// 3. Tratamento básico de erros
// -----------------------------------------------------------------
// Por padrão o PHP pode imprimir warnings/notices como HTML no meio
// da resposta, o que quebraria o JSON. Aqui garantimos que qualquer
// erro não tratado vire uma resposta JSON consistente em vez de
// vazar HTML ou detalhes internos para o usuário.
error_reporting(E_ALL);
ini_set('display_errors', '0'); // nunca imprimir erro cru na resposta

set_exception_handler(function (Throwable $excecao): void {
    // Em desenvolvimento, registrar o erro real no log do Apache
    // ajuda a debugar sem expor isso para quem está usando a API.
    error_log($excecao->getMessage());

    http_response_code(500);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro interno no servidor.',
    ]);
    exit;
});

set_error_handler(function (int $codigo, string $mensagem, string $arquivo, int $linha): bool {
    // Converte warnings/notices em exceções para caírem no mesmo
    // tratamento acima, em vez de aparecerem soltos na resposta.
    throw new ErrorException($mensagem, 0, $codigo, $arquivo, $linha);
});

// -----------------------------------------------------------------
// 4. Conexão com o banco
// -----------------------------------------------------------------
// A partir daqui, qualquer arquivo que incluir bootstrap.php já tem
// acesso à variável $pdo, pronta para ser usada pelos repositories.
require_once __DIR__ . '/../config/database.php';