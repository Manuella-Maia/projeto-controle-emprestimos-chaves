<?php

/**
 * config/database.php
 *
 * Responsável exclusivamente por criar a conexão PDO com o MySQL
 * e deixá-la disponível através da variável $pdo. Nenhuma regra de
 * negócio ou SQL de tabela específica deve entrar aqui: quem usa
 * $pdo são os repositories.
 *
 * Este arquivo é incluído pelo bootstrap.php, então não deve ser
 * chamado diretamente por rotas ou controllers.
 */

// -----------------------------------------------------------------
// Dados de conexão
// -----------------------------------------------------------------
// XAMPP por padrão sobe o MySQL em localhost:3306 com o usuário
// "root" e sem senha. Ajuste aqui se o ambiente local for diferente.
$host = 'localhost';
$porta = '3306';
$usuario = 'root';
$senha = '';

// O teste de carga (k6) manda um header próprio para ser direcionado
// a um banco de testes isolado (guarda_chaves_teste), em vez do banco
// usado pela demonstração ao vivo (guarda_chaves). Assim, mesmo que o
// teste de carga seja executado durante a apresentação, ele nunca
// altera os dados que estão sendo mostrados na tela.
//
// Importante: isso é uma simplificação deliberada para o ambiente de
// desenvolvimento/apresentação deste projeto. Um sistema em produção
// nunca deveria confiar em um header vindo do cliente para decidir
// qual banco usar — o certo seria uma variável de ambiente do próprio
// servidor. Aqui optamos pelo header por ser simples de demonstrar e
// por rodar tudo no mesmo Apache/XAMPP, sem precisar de um segundo
// Virtual Host só para isso.
$emAmbienteDeTeste = ($_SERVER['HTTP_X_AMBIENTE_TESTE'] ?? null) === 'true';
$banco = $emAmbienteDeTeste ? 'guarda_chaves_teste' : 'guarda_chaves';

$dsn = "mysql:host={$host};port={$porta};dbname={$banco};charset=utf8mb4";

// -----------------------------------------------------------------
// Opções do PDO
// -----------------------------------------------------------------
$opcoes = [
    // Erros do banco viram exceções (PDOException) em vez de warnings
    // silenciosos — assim eles caem no set_exception_handler definido
    // no bootstrap.php e nunca vazam para o usuário final.
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

    // Cada linha retornada vira um array associativo (['id' => 1, ...])
    // em vez de vir duplicada com índices numéricos também.
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

    // Usa prepared statements de verdade do MySQL, em vez do PDO
    // simulá-los no lado do PHP — mais seguro contra SQL Injection.
    PDO::ATTR_EMULATE_PREPARES => false,
];

// -----------------------------------------------------------------
// Criação da conexão
// -----------------------------------------------------------------
try {
    $pdo = new PDO($dsn, $usuario, $senha, $opcoes);
} catch (PDOException $excecao) {
    // Não repassamos a mensagem original do PDO para o usuário
    // (poderia expor detalhes internos do banco). O detalhe real
    // fica só no log do servidor.
    error_log('Falha ao conectar ao banco: ' . $excecao->getMessage());

    http_response_code(500);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Não foi possível conectar ao banco de dados.',
    ]);
    exit;
}
