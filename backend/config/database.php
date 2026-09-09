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
$banco = 'guarda_chaves';
$usuario = 'root';
$senha = '';

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