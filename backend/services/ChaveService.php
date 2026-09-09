<?php

require_once __DIR__ . '/../repository/ChaveRepository.php';
require_once __DIR__ . '/AuthService.php';

/**
 * ChaveService
 *
 * Regras de negócio relacionadas a chaves. Por enquanto só a
 * listagem (Etapa 6); a validação de disponibilidade/existência
 * usada na retirada (Regra 3) entra aqui também na Etapa 7, reaproveitando
 * o ChaveRepository já criado.
 */
class ChaveService
{
    private ChaveRepository $chaveRepository;
    private AuthService $authService;

    public function __construct(ChaveRepository $chaveRepository, AuthService $authService)
    {
        $this->chaveRepository = $chaveRepository;
        $this->authService = $authService;
    }

    /**
     * Lista as chaves cadastradas. A tela principal do sistema só
     * existe para quem já logou, então exigimos autenticação aqui
     * (mesma Regra 2 usada nas operações de empréstimo/devolução).
     */
    public function listarChaves(): array
    {
        // idUsuarioLogado() já lança RuntimeException se não houver
        // sessão ativa; não precisamos do id aqui, só da garantia.
        $this->authService->idUsuarioLogado();

        return $this->chaveRepository->listarTodas();
    }
}