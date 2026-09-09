<?php

require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../repository/UsuarioRepository.php';

/**
 * AuthController
 *
 * Ponto de entrada HTTP para autenticação. Só faz três coisas:
 *  1. extrai os dados enviados pelo frontend (JSON do corpo da requisição);
 *  2. chama o AuthService, que tem a regra de negócio;
 *  3. traduz o resultado (ou a exceção) em uma resposta HTTP/JSON.
 *
 * Nenhuma regra de negócio (validação de senha, o que entra na
 * sessão etc.) deve aparecer aqui — isso fica no AuthService.
 */
class AuthController
{
    private AuthService $authService;

    public function __construct(PDO $pdo)
    {
        $usuarioRepository = new UsuarioRepository($pdo);
        $this->authService = new AuthService($usuarioRepository);
    }

    /**
     * POST /api/login
     */
    public function login(): void
    {
        // O frontend envia { "email": "...", "senha": "..." } no
        // corpo da requisição, então lemos o corpo cru e decodificamos.
        $dados = $this->lerCorpoJson();

        $email = $dados['email'] ?? null;
        $senha = $dados['senha'] ?? null;

        try {
            $usuario = $this->authService->login($email, $senha);

            http_response_code(200);
            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'Login realizado com sucesso.',
                'dados' => $usuario,
            ]);
        } catch (RuntimeException $excecao) {
            // Diferenciamos "faltou preencher campo" (erro do cliente,
            // 400) de "credenciais erradas" (401) só pela mensagem,
            // já que o projeto evita criar hierarquia de exceções
            // para algo tão simples quanto isso.
            $status = str_contains($excecao->getMessage(), 'obrigatórios') ? 400 : 401;

            http_response_code($status);
            echo json_encode([
                'sucesso' => false,
                'mensagem' => $excecao->getMessage(),
            ]);
        }
    }

    /**
     * POST /api/logout
     */
    public function logout(): void
    {
        $this->authService->logout();

        http_response_code(200);
        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Logout realizado com sucesso.',
        ]);
    }

    /**
     * Lê e decodifica o corpo JSON da requisição atual.
     * Centralizado aqui para não repetir json_decode(file_get_contents(...))
     * em cada método/controller que precisar ler o corpo da requisição.
     */
    private function lerCorpoJson(): array
    {
        $corpo = file_get_contents('php://input');
        $dados = json_decode($corpo, true);

        return is_array($dados) ? $dados : [];
    }
}