<?php

require_once __DIR__ . '/../services/ChaveService.php';
require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../repository/ChaveRepository.php';
require_once __DIR__ . '/../repository/UsuarioRepository.php';

/**
 * ChaveController
 *
 * Ponto de entrada HTTP para as operações sobre chaves. Nesta etapa,
 * só a listagem (GET /api/chaves). Segue o mesmo padrão do
 * AuthController: extrai o que for necessário da requisição, chama
 * o Service e traduz o resultado (ou exceção) em JSON.
 */
class ChaveController
{
    private ChaveService $chaveService;

    public function __construct(PDO $pdo)
    {
        $chaveRepository = new ChaveRepository($pdo);
        $usuarioRepository = new UsuarioRepository($pdo);
        $authService = new AuthService($usuarioRepository);

        $this->chaveService = new ChaveService($chaveRepository, $authService);
    }

    /**
     * GET /api/chaves
     */
    public function listar(): void
    {
        try {
            $chaves = $this->chaveService->listarChaves();

            http_response_code(200);
            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'Chaves listadas com sucesso.',
                'dados' => $chaves,
            ]);
        } catch (RuntimeException $excecao) {
            // Neste método, a única exceção possível vem de
            // idUsuarioLogado() dentro do ChaveService: usuário não
            // autenticado.
            http_response_code(401);
            echo json_encode([
                'sucesso' => false,
                'mensagem' => $excecao->getMessage(),
            ]);
        }
    }
}