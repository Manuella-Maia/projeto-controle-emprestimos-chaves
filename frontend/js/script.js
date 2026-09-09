// =====================================================================
// Guarda-Chaves Digital — script.js
// =====================================================================
// Este arquivo cobre as duas páginas do frontend (index.html e
// sistema.html). Ele detecta em qual página está pelos elementos
// presentes no HTML e liga os comportamentos correspondentes.
//
// O frontend não decide regra de negócio (se uma chave pode ser
// retirada, se o usuário pode devolver etc.) — ele só chama a API e
// mostra o que ela responde. Toda validação "de verdade" já está no
// backend (Service), como pede a seção 9 do prompt.
// =====================================================================

// Caminho relativo até a API. Como frontend/ e backend/ são pastas
// irmãs dentro de sa_projeto_integracao/, isso funciona independente
// de onde o projeto for colocado dentro de htdocs.
const API_BASE = '../backend/api';

/**
 * Wrapper fino sobre fetch(): já manda/recebe JSON, inclui o cookie
 * de sessão do PHP (necessário para o backend saber quem está
 * logado) e sempre devolve { status, corpo } em vez de lançar em
 * respostas de erro (400/401/409...), que aqui são respostas válidas
 * da API, não falhas de rede.
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

// ---------------------------------------------------------------
// Página de login (index.html)
// ---------------------------------------------------------------
function iniciarPaginaLogin() {
    const formulario = document.getElementById('form-login');
    const botaoEntrar = document.getElementById('botao-entrar');
    const mensagem = document.getElementById('mensagem-login');

    formulario.addEventListener('submit', async (evento) => {
        evento.preventDefault();

        const email = document.getElementById('email').value.trim();
        const senha = document.getElementById('senha').value;

        botaoEntrar.disabled = true;
        mensagem.className = 'mensagem';
        mensagem.textContent = '';

        const { corpo } = await chamarApi('/login', {
            method: 'POST',
            body: JSON.stringify({ email, senha }),
        });

        if (corpo.sucesso) {
            // Guardamos só o nome pra exibir no topo do sistema; quem
            // realmente controla a sessão é o cookie do PHP, isto
            // aqui é só um detalhe de exibição.
            sessionStorage.setItem('nomeUsuario', corpo.dados.nome);
            window.location.href = 'sistema.html';
            return;
        }

        botaoEntrar.disabled = false;
        mensagem.className = 'mensagem erro';
        mensagem.textContent = corpo.mensagem || 'Não foi possível entrar.';
    });
}

// ---------------------------------------------------------------
// Página do sistema (sistema.html)
// ---------------------------------------------------------------
function iniciarPaginaSistema() {
    document.getElementById('nome-usuario').textContent =
        sessionStorage.getItem('nomeUsuario') || 'Usuário';

    document.getElementById('botao-sair').addEventListener('click', async () => {
        await chamarApi('/logout', { method: 'POST' });
        sessionStorage.removeItem('nomeUsuario');
        window.location.href = 'index.html';
    });

    carregarChaves();
    carregarMeuEmprestimo();
    carregarHistorico();
}

/**
 * Se a API responder 401 em qualquer chamada, a sessão do PHP
 * expirou ou nunca existiu — manda de volta pro login em vez de
 * deixar a tela quebrada mostrando "Carregando...".
 */
function tratarNaoAutenticado(status) {
    if (status === 401) {
        window.location.href = 'index.html';
        return true;
    }

    return false;
}

function mostrarMensagemSistema(texto, tipo = 'erro') {
    const mensagem = document.getElementById('mensagem-sistema');
    mensagem.className = `mensagem ${tipo}`;
    mensagem.textContent = texto;
}

function limparMensagemSistema() {
    const mensagem = document.getElementById('mensagem-sistema');
    mensagem.className = 'mensagem';
    mensagem.textContent = '';
}

async function carregarChaves() {
    const lista = document.getElementById('lista-chaves');
    const { status, corpo } = await chamarApi('/chaves');

    if (tratarNaoAutenticado(status)) {
        return;
    }

    if (!corpo.sucesso) {
        lista.innerHTML = `<p class="estado-vazio">${corpo.mensagem}</p>`;
        return;
    }

    if (corpo.dados.length === 0) {
        lista.innerHTML = '<p class="estado-vazio">Nenhuma chave cadastrada.</p>';
        return;
    }

    lista.innerHTML = corpo.dados.map((chave) => `
        <div class="item-chave">
            <div class="item-chave-info">
                <strong>${chave.nome}</strong>
                <span>${chave.local}</span>
            </div>
            <div class="item-chave-acao">
                <span class="status-tag ${chave.status}">${chave.status}</span>
                ${chave.status === 'disponivel'
                    ? `<button type="button" class="botao-retirar" data-chave-id="${chave.id}">Retirar</button>`
                    : ''}
            </div>
        </div>
    `).join('');

    lista.querySelectorAll('.botao-retirar').forEach((botao) => {
        botao.addEventListener('click', () => retirarChave(botao.dataset.chaveId));
    });
}

async function retirarChave(chaveId) {
    limparMensagemSistema();

    const { status, corpo } = await chamarApi('/emprestimos', {
        method: 'POST',
        body: JSON.stringify({ chave_id: Number(chaveId) }),
    });

    if (tratarNaoAutenticado(status)) {
        return;
    }

    if (!corpo.sucesso) {
        mostrarMensagemSistema(corpo.mensagem, 'erro');
        return;
    }

    mostrarMensagemSistema(corpo.mensagem, 'sucesso');

    // A retirada afeta as três seções da tela: a chave sai da lista
    // de disponíveis, "meu empréstimo" passa a mostrar essa chave, e
    // o histórico ganha uma nova linha "ativo".
    carregarChaves();
    carregarMeuEmprestimo();
    carregarHistorico();
}

async function carregarMeuEmprestimo() {
    const container = document.getElementById('meu-emprestimo');
    const { status, corpo } = await chamarApi('/meu-emprestimo');

    if (tratarNaoAutenticado(status)) {
        return;
    }

    if (!corpo.sucesso) {
        container.innerHTML = `<p class="estado-vazio">${corpo.mensagem}</p>`;
        return;
    }

    if (corpo.dados === null) {
        container.innerHTML = '<p class="estado-vazio">Você não possui nenhuma chave emprestada.</p>';
        return;
    }

    const emprestimo = corpo.dados;
    const dataRetirada = new Date(emprestimo.data_retirada.replace(' ', 'T'))
        .toLocaleString('pt-BR');

    container.innerHTML = `
        <div class="emprestimo-ativo">
            <div class="emprestimo-ativo-info">
                <strong>${emprestimo.chave_nome}</strong>
                <span>${emprestimo.chave_local} · retirada em ${dataRetirada}</span>
            </div>
            <button type="button" class="botao-devolver" data-emprestimo-id="${emprestimo.id}">Devolver</button>
        </div>
    `;

    container.querySelector('.botao-devolver')
        .addEventListener('click', () => devolverChave(emprestimo.id));
}

async function devolverChave(emprestimoId) {
    limparMensagemSistema();

    const { status, corpo } = await chamarApi('/emprestimos', {
        method: 'PATCH',
        body: JSON.stringify({ emprestimo_id: Number(emprestimoId) }),
    });

    if (tratarNaoAutenticado(status)) {
        return;
    }

    if (!corpo.sucesso) {
        mostrarMensagemSistema(corpo.mensagem, 'erro');
        return;
    }

    mostrarMensagemSistema(corpo.mensagem, 'sucesso');

    carregarChaves();
    carregarMeuEmprestimo();
    carregarHistorico();
}

async function carregarHistorico() {
    const corpoTabela = document.getElementById('tabela-historico');
    const { status, corpo } = await chamarApi('/historico');

    if (tratarNaoAutenticado(status)) {
        return;
    }

    if (!corpo.sucesso) {
        corpoTabela.innerHTML = `<tr><td colspan="5" class="estado-vazio">${corpo.mensagem}</td></tr>`;
        return;
    }

    if (corpo.dados.length === 0) {
        corpoTabela.innerHTML = '<tr><td colspan="5" class="estado-vazio">Nenhum empréstimo até o momento.</td></tr>';
        return;
    }

    corpoTabela.innerHTML = corpo.dados.map((item) => {
        const retirada = new Date(item.data_retirada.replace(' ', 'T')).toLocaleString('pt-BR');
        const devolucao = item.data_devolucao
            ? new Date(item.data_devolucao.replace(' ', 'T')).toLocaleString('pt-BR')
            : '—';

        return `
            <tr>
                <td>${item.chave_nome}</td>
                <td>${item.chave_local}</td>
                <td>${retirada}</td>
                <td>${devolucao}</td>
                <td><span class="status-tag ${item.status === 'ativo' ? 'disponivel' : 'emprestada'}">${item.status}</span></td>
            </tr>
        `;
    }).join('');
}

// ---------------------------------------------------------------
// Ponto de entrada: decide qual página iniciar
// ---------------------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('form-login')) {
        iniciarPaginaLogin();
    } else if (document.getElementById('lista-chaves')) {
        iniciarPaginaSistema();
    }
});