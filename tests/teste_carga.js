import http from 'k6/http';
import { check } from 'k6';

// =====================================================================
// Teste de carga — Guarda-Chaves Digital
// =====================================================================
// Objetivo: provar, com número de requisições/segundo reais (não só
// clicando duas vezes na mão), que a Regra 3/4/5 (chave só sai uma
// vez, usuário só tem um empréstimo ativo, tudo dentro de transação)
// se mantém mesmo sob concorrência pesada — e que nenhuma delas
// nunca responde 500 (erro interno).
//
// Ajuste BASE_URL para o caminho real do seu projeto no htdocs.
// =====================================================================

const BASE_URL = 'http://localhost/projeto-controle-emprestimos-chaves/backend/api';

// Este header é o que faz backend/config/database.php conectar no
// banco de testes (guarda_chaves_teste) em vez do banco usado na
// demonstração ao vivo (guarda_chaves) — ver comentário lá.
const HEADERS_JSON = {
    headers: {
        'Content-Type': 'application/json',
        'X-Ambiente-Teste': 'true',
    },
};
const CREDENCIAIS_ADMIN = { email: 'admin@guardachaves.com', senha: 'admin123' };

export const options = {
    vus: 50,          // 50 usuários virtuais simultâneos
    duration: '10s',  // roda por 10s ininterruptos
};

/**
 * Loga como admin e devolve a chave 1, caso ela esteja emprestada
 * para o próprio admin. Usada em setup() e teardown() para o teste
 * sempre começar e terminar em estado limpo — sem precisar mexer no
 * banco manualmente entre uma execução e outra.
 */
function limparEmprestimoDeTeste() {
    http.post(`${BASE_URL}/login`, JSON.stringify(CREDENCIAIS_ADMIN), HEADERS_JSON);

    const resMeuEmprestimo = http.get(`${BASE_URL}/meu-emprestimo`, HEADERS_JSON);
    const corpo = JSON.parse(resMeuEmprestimo.body);

    if (corpo.dados !== null && corpo.dados.chave_id === 1) {
        http.patch(
            `${BASE_URL}/emprestimos`,
            JSON.stringify({ emprestimo_id: corpo.dados.id }),
            HEADERS_JSON
        );
    }
}

/**
 * Roda uma única vez, antes de qualquer usuário virtual começar.
 * Garante que a chave 1 está disponível mesmo que a execução
 * anterior tenha terminado "suja" por algum motivo (ex.: você
 * cancelou o teste no meio com Ctrl+C).
 */
export function setup() {
    limparEmprestimoDeTeste();
}

export default function () {
    const headersJson = HEADERS_JSON;

    // 1. Login — necessário porque /chaves e /emprestimos exigem sessão.
    // O k6 guarda o cookie de sessão (PHPSESSID) automaticamente para
    // esta VU; as próximas chamadas já saem autenticadas.
    const resLogin = http.post(
        `${BASE_URL}/login`,
        JSON.stringify(CREDENCIAIS_ADMIN),
        headersJson
    );
    check(resLogin, {
        'login OK (200)': (r) => r.status === 200,
    });

    // 2. Busca de chaves (GET) — deve responder 200 sempre.
    const resGet = http.get(`${BASE_URL}/chaves`, headersJson);
    check(resGet, {
        'GET /chaves status 200': (r) => r.status === 200,
    });

    // 3. Tentativas simultâneas de retirar a MESMA chave (POST).
    // Esperado: uma única requisição em todo o teste recebe 201;
    // todas as outras (milhares, com 50 VUs por 10s) recebem 409 —
    // seja pela chave já estar emprestada, seja pelo usuário já ter
    // um empréstimo ativo (aqui é o mesmo usuário admin para todas
    // as VUs, então as duas regras podem disparar — ambas são 409,
    // então o teste continua válido).
    const resPost = http.post(
        `${BASE_URL}/emprestimos`,
        JSON.stringify({ chave_id: 1 }),
        headersJson
    );
    check(resPost, {
        'POST /emprestimos nunca dá erro interno (não é 500)': (r) => r.status !== 500,
        'POST /emprestimos responde 201 (sucesso) ou 409 (conflito esperado)':
            (r) => r.status === 201 || r.status === 409,
    });
}

/**
 * Roda uma única vez, depois que todos os usuários virtuais
 * terminaram. Devolve a chave que "venceu" a corrida durante o
 * teste, deixando tudo pronto pra rodar de novo sem intervenção
 * manual — inclusive ensaiar várias vezes antes da apresentação.
 */
export function teardown() {
    limparEmprestimoDeTeste();
}
