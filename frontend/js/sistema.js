// =====================================================================
// sistema.js
// =====================================================================
// Orquestra a página do sistema: decide QUANDO chamar a API (ao
// carregar a página, ao clicar em Retirar/Devolver/Sair) e QUANDO
// atualizar a tela — sem saber COMO nenhuma das duas coisas acontece
// por dentro. Esse "COMO" mora em api.js e ui.js.
// =====================================================================

import {
    logout,
    listarChaves,
    retirarChave,
    devolverChave,
    buscarMeuEmprestimo,
    buscarHistorico,
} from './api.js';
import { tocarSomErro, tocarSomSucesso } from './audio.js';

import {
    renderizarChaves,
    renderizarMeuEmprestimo,
    renderizarHistorico,
    mostrarMensagem,
    limparMensagem,
} from './ui.js';

const listaChavesEl = document.getElementById('lista-chaves');
const meuEmprestimoEl = document.getElementById('meu-emprestimo');
const tabelaHistoricoEl = document.getElementById('tabela-historico');
const mensagemSistemaEl = document.getElementById('mensagem-sistema');

// -----------------------------------------------------------------
// Inicialização da página
// -----------------------------------------------------------------
document.getElementById('nome-usuario').textContent =
    sessionStorage.getItem('nomeUsuario') || 'Usuário';

document.getElementById('botao-sair').addEventListener('click', async () => {
    await logout();
    sessionStorage.removeItem('nomeUsuario');
    window.location.href = 'index.html';
});

carregarChaves();
carregarMeuEmprestimo();
carregarHistorico();

// -----------------------------------------------------------------
// Chaves
// -----------------------------------------------------------------
async function carregarChaves() {
    const { status, corpo } = await listarChaves();

    if (tratarNaoAutenticado(status)) {
        return;
    }

    if (!corpo.sucesso) {
        listaChavesEl.innerHTML = `<p class="estado-vazio">${corpo.mensagem}</p>`;
        return;
    }

    renderizarChaves(listaChavesEl, corpo.dados, aoClicarRetirar);
}

async function aoClicarRetirar(chaveId) {
    limparMensagem(mensagemSistemaEl);

    const { status, corpo } = await retirarChave(chaveId);

    if (tratarNaoAutenticado(status)) {
        return;
    }

    if (!corpo.sucesso) {
        mostrarMensagem(mensagemSistemaEl, corpo.mensagem, 'erro');
        tocarSomErro();
        return;
    }

    mostrarMensagem(mensagemSistemaEl, corpo.mensagem, 'sucesso');
    tocarSomSucesso();
    atualizarTudo();
}

// -----------------------------------------------------------------
// Meu empréstimo
// -----------------------------------------------------------------
async function carregarMeuEmprestimo() {
    const { status, corpo } = await buscarMeuEmprestimo();

    if (tratarNaoAutenticado(status)) {
        return;
    }

    if (!corpo.sucesso) {
        meuEmprestimoEl.innerHTML = `<p class="estado-vazio">${corpo.mensagem}</p>`;
        return;
    }

    renderizarMeuEmprestimo(meuEmprestimoEl, corpo.dados, aoClicarDevolver);
}

async function aoClicarDevolver(emprestimoId) {
    limparMensagem(mensagemSistemaEl);

    const { status, corpo } = await devolverChave(emprestimoId);

    if (tratarNaoAutenticado(status)) {
        return;
    }

    if (!corpo.sucesso) {
        mostrarMensagem(mensagemSistemaEl, corpo.mensagem, 'erro');
        tocarSomErro();
        return;
    }

    mostrarMensagem(mensagemSistemaEl, corpo.mensagem, 'sucesso');
    tocarSomSucesso();
    atualizarTudo();
}

// -----------------------------------------------------------------
// Histórico
// -----------------------------------------------------------------
async function carregarHistorico() {
    const { status, corpo } = await buscarHistorico();

    if (tratarNaoAutenticado(status)) {
        return;
    }

    if (!corpo.sucesso) {
        tabelaHistoricoEl.innerHTML = `<tr><td colspan="5" class="estado-vazio">${corpo.mensagem}</td></tr>`;
        return;
    }

    renderizarHistorico(tabelaHistoricoEl, corpo.dados);
}

// -----------------------------------------------------------------
// Auxiliares
// -----------------------------------------------------------------

// Retirar e devolver afetam as três seções da tela ao mesmo tempo
// (a chave muda de status, "meu empréstimo" muda, e o histórico
// ganha uma linha nova) — por isso sempre recarregamos as três.
function atualizarTudo() {
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