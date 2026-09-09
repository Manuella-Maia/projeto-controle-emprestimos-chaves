// =====================================================================
// ui.js
// =====================================================================
// Única camada do frontend que manipula o DOM. Nenhuma função aqui
// faz fetch() ou sabe o que é a API — cada uma recebe os dados já
// prontos (do jeito que api.js devolveu) e um "callback" opcional
// para quando o usuário clicar em algum botão gerado.
//
// Separar assim deixa claro, numa leitura rápida do arquivo, "isso
// aqui é só desenho de tela" — os cliques disparam eventos que quem
// chamou (login.js/sistema.js) é quem decide o que fazer.
// =====================================================================

/**
 * Formata uma data vinda do banco ("2026-09-08 14:30:00") para o
 * formato brasileiro de data e hora.
 */
export function formatarData(dataBanco) {
    return new Date(dataBanco.replace(' ', 'T')).toLocaleString('pt-BR');
}

export function mostrarMensagem(elemento, texto, tipo = 'erro') {
    elemento.className = `mensagem ${tipo}`;
    elemento.textContent = texto;
}

export function limparMensagem(elemento) {
    elemento.className = 'mensagem';
    elemento.textContent = '';
}

/**
 * Desenha a lista de chaves. `aoClicarRetirar` é chamado com o id da
 * chave quando o usuário clica em "Retirar" — quem decide o que
 * acontece depois (chamar a API, recarregar as listas) é
 * sistema.js, não esta função.
 */
export function renderizarChaves(container, chaves, aoClicarRetirar) {
    if (chaves.length === 0) {
        container.innerHTML = '<p class="estado-vazio">Nenhuma chave cadastrada.</p>';
        return;
    }

    container.innerHTML = chaves.map((chave) => `
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

    container.querySelectorAll('.botao-retirar').forEach((botao) => {
        botao.addEventListener('click', () => aoClicarRetirar(botao.dataset.chaveId));
    });
}

/**
 * Desenha o painel "Meu empréstimo". Se `emprestimo` for null, mostra
 * a mensagem de estado vazio pedida na seção 4 do prompt.
 * `aoClicarDevolver` é chamado com o id do empréstimo.
 */
export function renderizarMeuEmprestimo(container, emprestimo, aoClicarDevolver) {
    if (emprestimo === null) {
        container.innerHTML = '<p class="estado-vazio">Você não possui nenhuma chave emprestada.</p>';
        return;
    }

    container.innerHTML = `
        <div class="emprestimo-ativo">
            <div class="emprestimo-ativo-info">
                <strong>${emprestimo.chave_nome}</strong>
                <span>${emprestimo.chave_local} · retirada em ${formatarData(emprestimo.data_retirada)}</span>
            </div>
            <button type="button" class="botao-devolver" data-emprestimo-id="${emprestimo.id}">Devolver</button>
        </div>
    `;

    container.querySelector('.botao-devolver')
        .addEventListener('click', () => aoClicarDevolver(emprestimo.id));
}

/**
 * Desenha as linhas da tabela de histórico. Não tem interação
 * (nenhum botão), então não recebe callback.
 */
export function renderizarHistorico(corpoTabela, historico) {
    if (historico.length === 0) {
        corpoTabela.innerHTML = '<tr><td colspan="5" class="estado-vazio">Nenhum empréstimo até o momento.</td></tr>';
        return;
    }

    corpoTabela.innerHTML = historico.map((item) => {
        const retirada = formatarData(item.data_retirada);
        const devolucao = item.data_devolucao ? formatarData(item.data_devolucao) : '—';
        const corDoStatus = item.status === 'ativo' ? 'disponivel' : 'emprestada';

        return `
            <tr>
                <td>${item.chave_nome}</td>
                <td>${item.chave_local}</td>
                <td>${retirada}</td>
                <td>${devolucao}</td>
                <td><span class="status-tag ${corDoStatus}">${item.status}</span></td>
            </tr>
        `;
    }).join('');
}