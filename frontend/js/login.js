// =====================================================================
// login.js
// =====================================================================
// Orquestra a página de login: pega o que o usuário digitou, chama
// api.login(), e usa ui.js para mostrar o resultado. Não sabe como a
// requisição é feita (isso é api.js) nem como a mensagem de erro é
// desenhada (isso é ui.js) — só decide "quando" chamar cada uma.
// =====================================================================

import { login } from './api.js';
import { mostrarMensagem, limparMensagem } from './ui.js';

const formulario = document.getElementById('form-login');
const botaoEntrar = document.getElementById('botao-entrar');
const mensagem = document.getElementById('mensagem-login');

formulario.addEventListener('submit', async (evento) => {
    evento.preventDefault();

    const email = document.getElementById('email').value.trim();
    const senha = document.getElementById('senha').value;

    botaoEntrar.disabled = true;
    limparMensagem(mensagem);

    const { corpo } = await login(email, senha);

    if (corpo.sucesso) {
        // Guardamos só o nome pra exibir no topo do sistema; quem
        // realmente controla a sessão é o cookie do PHP, isto aqui é
        // só um detalhe de exibição.
        sessionStorage.setItem('nomeUsuario', corpo.dados.nome);
        window.location.href = 'sistema.html';
        return;
    }

    botaoEntrar.disabled = false;
    mostrarMensagem(mensagem, corpo.mensagem || 'Não foi possível entrar.', 'erro');
});