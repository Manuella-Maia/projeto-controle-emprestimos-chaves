<?php

require_once __DIR__ . '/../repository/UsuarioRepository.php';

/**
 * AuthService
 *
 * Concentra as regras de negócio de autenticação: validar e-mail e
 * senha recebidos, conferir o hash com password_verify() e decidir
 * o que entra em $_SESSION. O Controller só repassa os dados da
 * requisição para cá e devolve o resultado; o Repository só busca
 * a linha do usuário no banco.
 */
class AuthService
{
    private UsuarioRepository $usuarioRepository;

    public function __construct(UsuarioRepository $usuarioRepository)
    {
        $this->usuarioRepository = $usuarioRepository;
    }

    /**
     * Executa o login (Regra 1 do prompt):
     *  - confere se e-mail e senha foram informados;
     *  - busca o usuário pelo e-mail;
     *  - confere a senha com password_verify();
     *  - se tudo bater, grava os dados essenciais em $_SESSION.
     *
     * Lança RuntimeException com a mensagem de erro apropriada
     * quando alguma validação falha; quem decide o status HTTP a
     * partir dessa mensagem é o AuthController.
     */
    public function login(?string $email, ?string $senha): array
    {
        if (empty($email) || empty($senha)) {
            throw new RuntimeException('E-mail e senha são obrigatórios.');
        }

        $usuario = $this->usuarioRepository->buscarPorEmail($email);

        // Propositalmente usamos a mesma mensagem tanto para "e-mail
        // não encontrado" quanto para "senha incorreta". Isso evita
        // que a API revele para um atacante se um e-mail existe ou
        // não na base (evita enumeração de usuários).
        if ($usuario === null || !password_verify($senha, $usuario['senha'])) {
            throw new RuntimeException('E-mail ou senha inválidos.');
        }

        // A partir daqui o login é válido: guardamos na sessão só o
        // necessário para identificar o usuário nas próximas
        // requisições, nunca o hash da senha.
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_tipo'] = $usuario['tipo'];

        return [
            'id' => $usuario['id'],
            'nome' => $usuario['nome'],
            'email' => $usuario['email'],
            'tipo' => $usuario['tipo'],
        ];
    }

    /**
     * Encerra a sessão do usuário atual.
     */
    public function logout(): void
    {
        $_SESSION = [];

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    /**
     * Confere se existe um usuário autenticado na sessão atual.
     * Usado pelos outros services (ChaveService, EmprestimoService)
     * antes de qualquer operação que exija login (Regra 2).
     */
    public function usuarioAutenticado(): bool
    {
        return isset($_SESSION['usuario_id']);
    }

    /**
     * Retorna o id do usuário logado, ou lança exceção se não
     * houver ninguém autenticado. Os demais services chamam este
     * método no início de cada operação protegida.
     */
    public function idUsuarioLogado(): int
    {
        if (!$this->usuarioAutenticado()) {
            throw new RuntimeException('Usuário não autenticado.');
        }

        return (int) $_SESSION['usuario_id'];
    }
}