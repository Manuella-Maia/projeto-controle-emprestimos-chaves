<?php

/**
 * EmprestimoRepository
 *
 * Camada de acesso a dados da tabela `emprestimos`. Só consulta e
 * grava linhas; não decide se uma retirada/devolução é permitida —
 * isso é sempre do EmprestimoService.
 */
class EmprestimoRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Busca o empréstimo ativo (status = 'ativo') de um usuário,
     * se existir. Usado para checar a Regra 4 (um empréstimo ativo
     * por usuário) antes de uma retirada.
     */
    public function buscarAtivoPorUsuario(int $usuarioId): ?array
    {
        $sql = "SELECT id, usuario_id, chave_id, data_retirada, data_devolucao, status
                FROM emprestimos
                WHERE usuario_id = ? AND status = 'ativo'
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$usuarioId]);

        $emprestimo = $stmt->fetch();

        return $emprestimo !== false ? $emprestimo : null;
    }

    /**
     * Busca um empréstimo pelo id, independentemente do status.
     * Usado na devolução para conferir se ele existe, se pertence
     * ao usuário e se ainda está ativo.
     */
    public function buscarPorId(int $id): ?array
    {
        $sql = "SELECT id, usuario_id, chave_id, data_retirada, data_devolucao, status
                FROM emprestimos
                WHERE id = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);

        $emprestimo = $stmt->fetch();

        return $emprestimo !== false ? $emprestimo : null;
    }

    /**
     * Cria um novo empréstimo com status 'ativo' e data de retirada
     * agora. Retorna o id gerado, para o Service poder devolver a
     * resposta completa ao frontend.
     */
    public function criar(int $usuarioId, int $chaveId): int
    {
        $sql = "INSERT INTO emprestimos (usuario_id, chave_id, data_retirada, status)
                VALUES (?, ?, NOW(), 'ativo')";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$usuarioId, $chaveId]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Lista o histórico completo de empréstimos de um usuário
     * (ativos e devolvidos), do mais recente para o mais antigo.
     * Usado por GET /api/historico (Etapa 9), já deixado pronto
     * aqui por reaproveitar a mesma tabela/consulta base.
     */
    public function listarPorUsuario(int $usuarioId): array
    {
        $sql = "SELECT e.id, e.chave_id, c.nome AS chave_nome, c.local AS chave_local,
                       e.data_retirada, e.data_devolucao, e.status
                FROM emprestimos e
                INNER JOIN chaves c ON c.id = e.chave_id
                WHERE e.usuario_id = ?
                ORDER BY e.data_retirada DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$usuarioId]);

        return $stmt->fetchAll();
    }

    /**
     * Marca um empréstimo como devolvido, registrando a data de
     * devolução agora. Usado por EmprestimoService::devolver()
     * (Etapa 8).
     */
    public function marcarDevolvido(int $id): void
    {
        $sql = "UPDATE emprestimos
                SET status = 'devolvido', data_devolucao = NOW()
                WHERE id = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
    }
}