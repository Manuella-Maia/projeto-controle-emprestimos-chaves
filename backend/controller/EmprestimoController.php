<?php

require_once __DIR__ . '/../services/EmprestimoService.php';
require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../repository/EmprestimoRepository.php';
require_once __DIR__ . '/../repository/ChaveRepository.php';
require_once __DIR__ . '/../repository/UsuarioRepository.php';

/**
 * EmprestimoController
 *
 * Ponto de entrada HTTP para retirada (e, na Etapa 8, devolução) de
 * chaves. Mesmo padrão dos demais controllers: extrai o dado da
 * requisição, chama o Service, traduz o resultado em JSON.
 */
class EmprestimoController
{
    private EmprestimoService $emprestimoService;

    public function __construct(PDO $pdo)
    {
        $emprestimoRepository = new EmprestimoRepository($pdo);
        $chaveRepository = new ChaveRepository($pdo);
        $usuarioRepository = new UsuarioRepository($pdo);
        $authService = new AuthService($usuarioRepository);

        $this->emprestimoService = new EmprestimoService(
            $emprestimoRepository,
            $chaveRepository,
            $authService,
            $pdo
        );
    }

    /**
     * POST /api/emprestimos
     */
    public function retirar(): void
    {
        $dados = $this->lerCorpoJson();
        $chaveId = isset($dados['chave_id']) ? (int) $dados['chave_id'] : null;

        try {
            $emprestimo = $this->emprestimoService->retirar($chaveId);

            http_response_code(201);
            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'Chave retirada com sucesso.',
                'dados' => $emprestimo,
            ]);
        } catch (RuntimeException $excecao) {
            http_response_code($this->statusParaMensagem($excecao->getMessage()));
            echo json_encode([
                'sucesso' => false,
                'mensagem' => $excecao->getMessage(),
            ]);
        }
    }

    /**
     * PATCH /api/emprestimos
     */
    public function devolver(): void
    {
        $dados = $this->lerCorpoJson();
        $emprestimoId = isset($dados['emprestimo_id']) ? (int) $dados['emprestimo_id'] : null;

        try {
            $emprestimo = $this->emprestimoService->devolver($emprestimoId);

            http_response_code(200);
            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'Chave devolvida com sucesso.',
                'dados' => $emprestimo,
            ]);
        } catch (RuntimeException $excecao) {
            http_response_code($this->statusParaMensagem($excecao->getMessage()));
            echo json_encode([
                'sucesso' => false,
                'mensagem' => $excecao->getMessage(),
            ]);
        }
    }

    /**
     * GET /api/meu-emprestimo
     */
    public function meuEmprestimo(): void
    {
        try {
            $emprestimo = $this->emprestimoService->meuEmprestimo();

            http_response_code(200);
            echo json_encode([
                'sucesso' => true,
                'mensagem' => $emprestimo !== null
                    ? 'Empréstimo ativo encontrado.'
                    : 'Você não possui nenhuma chave emprestada.',
                'dados' => $emprestimo,
            ]);
        } catch (RuntimeException $excecao) {
            // Única exceção possível aqui: usuário não autenticado.
            http_response_code(401);
            echo json_encode([
                'sucesso' => false,
                'mensagem' => $excecao->getMessage(),
            ]);
        }
    }

    /**
     * GET /api/historico
     */
    public function historico(): void
    {
        try {
            $historico = $this->emprestimoService->historico();

            http_response_code(200);
            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'Histórico listado com sucesso.',
                'dados' => $historico,
            ]);
        } catch (RuntimeException $excecao) {
            http_response_code(401);
            echo json_encode([
                'sucesso' => false,
                'mensagem' => $excecao->getMessage(),
            ]);
        }
    }

    /**
     * Traduz a mensagem de erro lançada pelo Service no código HTTP
     * correspondente. Mesma estratégia usada no AuthController: como
     * o projeto evita criar uma hierarquia de exceções só para isso,
     * a mensagem em si carrega a informação de "que tipo" de erro é.
     */
    private function statusParaMensagem(string $mensagem): int
    {
        if (str_contains($mensagem, 'não autenticado')) {
            return 401;
        }

        if (str_contains($mensagem, 'não pertence a você')) {
            return 403;
        }

        if (str_contains($mensagem, 'não encontrad')) {
            return 404;
        }

        if (str_contains($mensagem, 'obrigatório')) {
            return 400;
        }

        // "já está emprestada", "já possui uma chave emprestada" e
        // "já foi devolvido" são conflitos de estado -> 409.
        return 409;
    }

    private function lerCorpoJson(): array
    {
        $corpo = file_get_contents('php://input');
        $dados = json_decode($corpo, true);

        return is_array($dados) ? $dados : [];
    }
}