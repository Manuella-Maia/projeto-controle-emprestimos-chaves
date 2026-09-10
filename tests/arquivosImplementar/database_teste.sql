-- =============================================================
-- Guarda-Chaves Digital — BANCO DE TESTES (k6)
-- =============================================================
-- Cópia exata de database.sql, isolada em outro banco
-- (guarda_chaves_teste), usada apenas pelo teste de carga
-- (tests/teste_carga.js). O objetivo é o teste de carga nunca
-- tocar no banco "guarda_chaves" usado na demonstração ao vivo,
-- mesmo que ele seja rodado durante a apresentação.
--
-- Se o schema de database.sql mudar (nova coluna, nova tabela),
-- repita este arquivo a partir dele:
--   sed 's/guarda_chaves/guarda_chaves_teste/g' database.sql > database_teste.sql
-- =============================================================
-- Este script cria o banco "guarda_chaves_teste", suas três tabelas
-- (usuarios, chaves, emprestimos), os relacionamentos entre elas
-- e insere dados iniciais para permitir a demonstração do sistema
-- sem precisar de cadastro público (que não faz parte do escopo).
-- =============================================================

-- -------------------------------------------------------------
-- Criação do banco
-- -------------------------------------------------------------
CREATE DATABASE IF NOT EXISTS guarda_chaves_teste
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE guarda_chaves_teste;

-- -------------------------------------------------------------
-- Tabela: usuarios
-- Guarda quem pode logar no sistema e retirar/devolver chaves.
-- O campo "senha" nunca armazena texto puro: sempre o hash
-- gerado por password_hash() no PHP.
-- -------------------------------------------------------------
DROP TABLE IF EXISTS emprestimos;
DROP TABLE IF EXISTS chaves;
DROP TABLE IF EXISTS usuarios;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('usuario', 'admin') NOT NULL DEFAULT 'usuario'
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- Tabela: chaves
-- Representa as chaves físicas disponíveis para empréstimo.
-- O status é a "fonte da verdade" sobre a disponibilidade da
-- chave no momento; quem garante a consistência dele é o
-- EmprestimoService, nunca o frontend.
-- -------------------------------------------------------------
CREATE TABLE chaves (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    local VARCHAR(100) NOT NULL,
    status ENUM('disponivel', 'emprestada') NOT NULL DEFAULT 'disponivel'
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- Tabela: emprestimos
-- Registra cada retirada de chave, sua devolução (quando houver)
-- e o status atual do empréstimo. Um usuário pode ter vários
-- empréstimos ao longo do tempo, mas apenas um "ativo" por vez;
-- essa regra é validada em EmprestimoService, não no banco.
-- -------------------------------------------------------------
CREATE TABLE emprestimos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    chave_id INT NOT NULL,
    data_retirada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_devolucao DATETIME NULL,
    status ENUM('ativo', 'devolvido') NOT NULL DEFAULT 'ativo',

    CONSTRAINT fk_emprestimo_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id),

    CONSTRAINT fk_emprestimo_chave
        FOREIGN KEY (chave_id) REFERENCES chaves(id)
) ENGINE=InnoDB;

-- Índice auxiliar: acelera a verificação de "usuário já possui
-- empréstimo ativo?", que é consultada a cada retirada.
CREATE INDEX idx_emprestimos_usuario_status ON emprestimos (usuario_id, status);

-- Índice auxiliar: acelera a verificação de "chave já está
-- emprestada?" e a montagem do histórico por chave.
CREATE INDEX idx_emprestimos_chave_status ON emprestimos (chave_id, status);

-- =============================================================
-- Dados iniciais para demonstração
-- =============================================================

-- -------------------------------------------------------------
-- Usuários de teste
-- As senhas abaixo são hashes bcrypt reais, gerados com o
-- equivalente de password_hash($senha, PASSWORD_DEFAULT) do PHP.
-- Senhas em texto puro (apenas para os testes/demonstração):
--   admin@guardachaves.com  -> admin123
--   joao@guardachaves.com   -> joao123
--   maria@guardachaves.com  -> maria123
-- -------------------------------------------------------------
INSERT INTO usuarios (nome, email, senha, tipo) VALUES
('Administrador', 'admin@guardachaves.com', '$2b$10$RcGNQT7urYPdZW9erzlS6ugA4oxk6Iux/JLG.8bAtYDKnsSbMJQe6', 'admin'),
('João Lira', 'joao@guardachaves.com', '$2b$10$ilxAlR0LTt2E3YM.c2k6BOqK7Lo3uQQIDIsXfUjQmpS..Zy2KlpN.', 'usuario'),
('Maria Souza', 'maria@guardachaves.com', '$2b$10$2NUHqU3E03PT8K1ufSn3S.sERwDEDBGznpWHKzUK3qL8zlQU1AudC', 'usuario');

-- -------------------------------------------------------------
-- Chaves disponíveis para empréstimo
-- -------------------------------------------------------------
INSERT INTO chaves (nome, local, status) VALUES
('Chave da Sala 01', 'Bloco A - Sala 01', 'disponivel'),
('Chave do Laboratório', 'Bloco B - Laboratório de Informática', 'disponivel'),
('Chave da Sala de Reunião', 'Bloco A - 2º andar', 'disponivel'),
('Chave do Almoxarifado', 'Bloco C - Térreo', 'disponivel');

-- -------------------------------------------------------------
-- Um empréstimo de exemplo já ativo, para que a demonstração
-- possa mostrar imediatamente uma chave indisponível e o fluxo
-- de devolução sem precisar retirar antes.
-- João Lira (id 2) está com a "Chave do Almoxarifado" (id 4).
-- -------------------------------------------------------------
UPDATE chaves SET status = 'emprestada' WHERE nome = 'Chave do Almoxarifado';

INSERT INTO emprestimos (usuario_id, chave_id, data_retirada, status)
VALUES (
    2,
    (SELECT id FROM chaves WHERE nome = 'Chave do Almoxarifado'),
    NOW(),
    'ativo'
);
