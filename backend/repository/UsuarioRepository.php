<?php

/**
 * UsuarioRepository
 *
 * Camada de acesso a dados da tabela `usuarios`. Só sabe conversar
 * com o banco (SELECT/INSERT/UPDATE via PDO + prepared statements).
 * Não decide se um login é válido, não faz password_verify, não
 * sabe nada sobre sessão — isso é responsabilidade do AuthService.
 */
class UsuarioRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Busca um usuário pelo e-mail informado no login.
     * Retorna o array associativo da linha ou null se não existir.
     */
    public function buscarPorEmail(string $email): ?array
    {
        $sql = 'SELECT id, nome, email, senha, tipo FROM usuarios WHERE email = ?';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$email]);

        $usuario = $stmt->fetch();

        // fetch() retorna false quando não encontra nada; normalizamos
        // para null, que é mais explícito para quem consome o retorno.
        return $usuario !== false ? $usuario : null;
    }

    /**
     * Busca um usuário pelo id. Usado, por exemplo, para recarregar
     * os dados do usuário logado a partir do que está em $_SESSION.
     */
    public function buscarPorId(int $id): ?array
    {
        $sql = 'SELECT id, nome, email, tipo FROM usuarios WHERE id = ?';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);

        $usuario = $stmt->fetch();

        return $usuario !== false ? $usuario : null;
    }
}