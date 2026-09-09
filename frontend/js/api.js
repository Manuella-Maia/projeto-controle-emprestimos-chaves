// =====================================================================
// api.js
// =====================================================================
// Única camada do frontend que sabe que existe uma API HTTP. Todas as
// funções aqui só fazem fetch() e devolvem { status, corpo } — nenhuma
// delas toca no DOM. Quem decide o que fazer com o resultado (mostrar
// na tela, redirecionar etc.) é o código de página (login.js/sistema.js),
// usando as funções de ui.js.
//
// Isso espelha, no frontend, a mesma ideia de "camadas" do backend:
// aqui é o equivalente ao Repository (só busca/envia dado).
// =====================================================================

// Caminho relativo até a API. Como frontend/ e backend/ são pastas
// irmãs dentro de sa_projeto_integracao/, isso funciona independente
// de onde o projeto for colocado dentro de htdocs.
const API_BASE = '../backend/api';

/**
 * Wrapper fino sobre fetch(): já manda/recebe JSON e inclui o cookie
 * de sessão do PHP (necessário para o backend saber quem está
 * logado). Respostas de erro da API (400/401/409...) não são
 * tratadas como falha de fetch — são respostas válidas, só com
 * `sucesso: false` no corpo.
 */
async function chamarApi(caminho, opcoes = {}) {
    const resposta = await fetch(`${API_BASE}${caminho}`, {
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        ...opcoes,
    });

    const corpo = await resposta.json().catch(() => ({}));

    return { status: resposta.status, corpo };
}

export function login(email, senha) {
    return chamarApi('/login', {
        method: 'POST',
        body: JSON.stringify({ email, senha }),
    });
}

export function logout() {
    return chamarApi('/logout', { method: 'POST' });
}

export function listarChaves() {
    return chamarApi('/chaves');
}

export function retirarChave(chaveId) {
    return chamarApi('/emprestimos', {
        method: 'POST',
        body: JSON.stringify({ chave_id: Number(chaveId) }),
    });
}

export function devolverChave(emprestimoId) {
    return chamarApi('/emprestimos', {
        method: 'PATCH',
        body: JSON.stringify({ emprestimo_id: Number(emprestimoId) }),
    });
}

export function buscarMeuEmprestimo() {
    return chamarApi('/meu-emprestimo');
}

export function buscarHistorico() {
    return chamarApi('/historico');
}