<?php

/**
 * ChaveRepository
 *
 * Camada de acesso a dados da tabela `chaves`. Só sabe consultar e
 * atualizar linhas no banco; não decide se uma chave "pode" ser
 * retirada — essa decisão é do EmprestimoService, que vai reutilizar
 * este repository mais adiante (Etapa 7).
 */
class ChaveRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Lista todas as chaves cadastradas, com o status atual de cada
     * uma. É essa lista que o frontend usa para mostrar o botão
     * "Retirar" apenas nas chaves disponíveis.
     */
    public function listarTodas(): array
    {
        $sql = 'SELECT id, nome, local, status FROM chaves ORDER BY nome';

        $stmt = $this->pdo->query($sql);

        return $stmt->fetchAll();
    }

    /**
     * Busca uma única chave pelo id. Usado pelo EmprestimoService
     * para conferir existência e status antes de uma retirada.
     */
    public function buscarPorId(int $id): ?array
    {
        $sql = 'SELECT id, nome, local, status FROM chaves WHERE id = ?';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);

        $chave = $stmt->fetch();

        return $chave !== false ? $chave : null;
    }

    /**
     * Atualiza o status de uma chave (disponivel <-> emprestada).
     * Usado pelo EmprestimoService dentro da transação de retirada
     * e de devolução.
     */
    public function atualizarStatus(int $id, string $status): void
    {
        $sql = 'UPDATE chaves SET status = ? WHERE id = ?';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$status, $id]);
    }
}