<?php

require_once __DIR__ . '/../repository/EmprestimoRepository.php';
require_once __DIR__ . '/../repository/ChaveRepository.php';
require_once __DIR__ . '/AuthService.php';

/**
 * EmprestimoService
 *
 * Concentra as regras de negócio de retirada e devolução de chaves.
 * Nesta etapa, apenas retirar() (Regra 5). devolver() entra na
 * Etapa 8, reaproveitando os mesmos repositories.
 */
class EmprestimoService
{
    private EmprestimoRepository $emprestimoRepository;
    private ChaveRepository $chaveRepository;
    private AuthService $authService;
    private PDO $pdo;

    public function __construct(
        EmprestimoRepository $emprestimoRepository,
        ChaveRepository $chaveRepository,
        AuthService $authService,
        PDO $pdo
    ) {
        $this->emprestimoRepository = $emprestimoRepository;
        $this->chaveRepository = $chaveRepository;
        $this->authService = $authService;
        $this->pdo = $pdo;
    }

    /**
     * Executa a retirada de uma chave, seguindo o fluxo da Regra 5:
     *   1. verificar autenticação
     *   2. validar chave (chave_id foi enviado?)
     *   3. verificar existência da chave
     *   4. verificar disponibilidade
     *   5. verificar empréstimo ativo do usuário
     *   6. criar empréstimo
     *   7. atualizar chave para "emprestada"
     *
     * Os passos 6 e 7 alteram duas tabelas diferentes e precisam
     * acontecer juntos ou não acontecer — por isso rodam dentro de
     * uma transação (evita, por exemplo, o empréstimo ser criado
     * mas a chave continuar marcada como "disponivel" caso algo
     * falhe no meio do caminho).
     */
    public function retirar(?int $chaveId): array
    {
        // 1. autenticação
        $usuarioId = $this->authService->idUsuarioLogado();

        // 2. validação básica do dado recebido
        if (empty($chaveId)) {
            throw new RuntimeException('O campo chave_id é obrigatório.');
        }

        // 3. existência da chave
        $chave = $this->chaveRepository->buscarPorId($chaveId);

        if ($chave === null) {
            throw new RuntimeException('Chave não encontrada.');
        }

        // 4. disponibilidade da chave
        if ($chave['status'] !== 'disponivel') {
            throw new RuntimeException('A chave já está emprestada.');
        }

        // 5. usuário não pode ter outro empréstimo ativo
        $emprestimoAtivo = $this->emprestimoRepository->buscarAtivoPorUsuario($usuarioId);

        if ($emprestimoAtivo !== null) {
            throw new RuntimeException('Você já possui uma chave emprestada.');
        }

        // 6. e 7. criação do empréstimo + atualização da chave,
        // como uma única operação atômica.
        try {
            $this->pdo->beginTransaction();

            $emprestimoId = $this->emprestimoRepository->criar($usuarioId, $chaveId);
            $this->chaveRepository->atualizarStatus($chaveId, 'emprestada');

            $this->pdo->commit();
        } catch (Throwable $erro) {
            $this->pdo->rollBack();
            throw $erro;
        }

        return [
            'id' => $emprestimoId,
            'chave_id' => $chaveId,
            'chave_nome' => $chave['nome'],
            'usuario_id' => $usuarioId,
        ];
    }

    /**
     * Executa a devolução de uma chave, seguindo o fluxo da Regra 6:
     *   1. verificar autenticação
     *   2. localizar empréstimo
     *   3. verificar se pertence ao usuário
     *   4. verificar se está ativo
     *   5. registrar data de devolução
     *   6. alterar empréstimo para "devolvido"
     *   7. alterar chave para "disponivel"
     *
     * Assim como na retirada, os passos 5–7 (que na prática são
     * duas escritas: marcar o empréstimo e liberar a chave) rodam
     * dentro de uma transação.
     */
    public function devolver(?int $emprestimoId): array
    {
        // 1. autenticação
        $usuarioId = $this->authService->idUsuarioLogado();

        if (empty($emprestimoId)) {
            throw new RuntimeException('O campo emprestimo_id é obrigatório.');
        }

        // 2. localizar empréstimo
        $emprestimo = $this->emprestimoRepository->buscarPorId($emprestimoId);

        if ($emprestimo === null) {
            throw new RuntimeException('Empréstimo não encontrado.');
        }

        // 3. um usuário não pode devolver o empréstimo de outro
        // (Regra 6, último parágrafo). Usamos 403 aqui em vez de 404
        // para deixar claro, do lado do controller, que o recurso
        // existe mas não pertence a quem está pedindo.
        if ($emprestimo['usuario_id'] !== $usuarioId) {
            throw new RuntimeException('Este empréstimo não pertence a você.');
        }

        // 4. só é possível devolver um empréstimo que ainda está ativo
        if ($emprestimo['status'] !== 'ativo') {
            throw new RuntimeException('Este empréstimo já foi devolvido.');
        }

        // 5., 6. e 7. — marcar devolução e liberar a chave juntos.
        try {
            $this->pdo->beginTransaction();

            $this->emprestimoRepository->marcarDevolvido($emprestimoId);
            $this->chaveRepository->atualizarStatus($emprestimo['chave_id'], 'disponivel');

            $this->pdo->commit();
        } catch (Throwable $erro) {
            $this->pdo->rollBack();
            throw $erro;
        }

        return [
            'id' => $emprestimoId,
            'chave_id' => $emprestimo['chave_id'],
            'usuario_id' => $usuarioId,
        ];
    }

    /**
     * Retorna o empréstimo ativo do usuário logado, já com o nome e
     * local da chave (útil pra tela mostrar direto, sem o frontend
     * precisar cruzar com a listagem de chaves). Retorna null se o
     * usuário não tiver nenhum empréstimo ativo no momento — isso
     * não é um erro, é o "Você não possui nenhuma chave emprestada."
     * da seção 4 do prompt.
     */
    public function meuEmprestimo(): ?array
    {
        $usuarioId = $this->authService->idUsuarioLogado();

        $emprestimo = $this->emprestimoRepository->buscarAtivoPorUsuario($usuarioId);

        if ($emprestimo === null) {
            return null;
        }

        $chave = $this->chaveRepository->buscarPorId($emprestimo['chave_id']);

        return [
            'id' => $emprestimo['id'],
            'chave_id' => $emprestimo['chave_id'],
            'chave_nome' => $chave['nome'] ?? null,
            'chave_local' => $chave['local'] ?? null,
            'data_retirada' => $emprestimo['data_retirada'],
        ];
    }

    /**
     * Retorna o histórico completo (ativos + devolvidos) do usuário
     * logado, já com nome/local da chave de cada empréstimo. Só
     * repassa para o Repository, que já faz o JOIN necessário.
     */
    public function historico(): array
    {
        $usuarioId = $this->authService->idUsuarioLogado();

        return $this->emprestimoRepository->listarPorUsuario($usuarioId);
    }
}