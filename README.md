# Guarda-Chaves Digital

Sistema web para controle de empréstimo e devolução de chaves físicas (salas, laboratórios, almoxarifado etc.), desenvolvido como projeto didático de **integração de sistemas**: frontend, backend, API, regras de negócio e banco de dados conversando de ponta a ponta, de um jeito simples o suficiente para todo o fluxo ser explicado numa apresentação.

> Documentação relacionada: [`docs/arquitetura-backend.md`](docs/arquitetura-backend.md), [`docs/arquitetura-frontend.md`](docs/arquitetura-frontend.md) e [`docs/integracao-sistemas.md`](docs/integracao-sistemas.md).

---

## 1. Ideia do projeto

Muitos ambientes (escolas, empresas, laboratórios) ainda controlam o empréstimo de chaves físicas numa planilha ou num caderno na portaria. O Guarda-Chaves Digital resolve isso com um fluxo simples:

1. o usuário loga no sistema;
2. vê quais chaves estão disponíveis;
3. retira a que precisa;
4. o sistema impede que duas pessoas peguem a mesma chave, ou que uma pessoa pegue duas chaves ao mesmo tempo — inclusive sob concorrência real (ver seção 12, teste de carga);
5. quando termina de usar, devolve pelo próprio sistema;
6. tudo fica registrado num histórico.

O projeto **não** tenta ser um sistema completo de gestão patrimonial — o objetivo é demonstrar a arquitetura de integração, não a quantidade de funcionalidades (ver seção 21 do prompt original em `docs/`).

---

## 2. Tecnologias utilizadas

| Camada | Tecnologia |
|---|---|
| Frontend | HTML5, CSS3, JavaScript (ES Modules), `fetch()` |
| Backend | PHP puro (sem framework) |
| Comunicação | API HTTP/REST, JSON |
| Banco de dados | MySQL (via XAMPP) |
| Acesso a dados | PDO com *prepared statements* |
| Autenticação | Sessão PHP (`$_SESSION`) |
| Ambiente | XAMPP (Apache + MySQL) |
| Teste de carga | [k6](https://k6.io/) |

Propositalmente **fora** do escopo: Laravel/Symfony, JWT, Docker, ORM, React/Vue/Angular, microsserviços — ver a justificativa em [`docs/arquitetura-backend.md`](docs/arquitetura-backend.md#por-que-sem-framework).

---

## 3. Pré-requisitos

- [XAMPP](https://www.apachefriends.org/) instalado (Apache + MySQL + PHP 8+)
- Um navegador atual (Chrome, Firefox, Edge) — o frontend usa ES Modules, que exigem um navegador moderno
- [Git](https://git-scm.com/) (ou GitHub Desktop), para clonar o projeto
- Opcional: Postman ou Insomnia, para testar a API diretamente
- Opcional: [k6](https://k6.io/docs/get-started/installation/), para rodar o teste de carga (seção 12)

---

## 4. Como clonar o projeto

Com Git instalado, clone direto dentro do `htdocs` do XAMPP (evita ter que mover a pasta depois):

```bash
cd C:\xampp\htdocs        # Windows — ajuste para o seu caminho do htdocs
git clone <URL-do-repositorio> projeto-controle-emprestimos-chaves
```

Se preferir GitHub Desktop: **File → Clone repository**, e escolha como destino local a pasta `htdocs` do XAMPP.

Sem Git, também dá pra baixar o `.zip` do repositório (botão **Code → Download ZIP** no GitHub) e extrair diretamente dentro do `htdocs`.

> **O nome da pasta importa.** O backend usa um `.htaccess` com `RewriteBase` apontando para o caminho exato do projeto dentro do `htdocs` (ver seção 5.1). Se você clonar com um nome de pasta diferente de `projeto-controle-emprestimos-chaves`, precisa atualizar essa linha — veja o aviso na seção 5.1.

---

## 5. Como configurar e rodar

### 5.1. Confirmar que o projeto está dentro do `htdocs`

O Apache só enxerga o que está dentro dessa pasta:

| Sistema | Caminho padrão do `htdocs` |
|---|---|
| Windows | `C:\xampp\htdocs\` |
| Linux | `/opt/lampp/htdocs/` |
| macOS | `/Applications/XAMPP/htdocs/` |

Resultado esperado:

```
htdocs/
└── projeto-controle-emprestimos-chaves/
    ├── backend/
    ├── frontend/
    ├── docs/
    └── tests/
```

> **Se você renomear essa pasta**, abra `backend/.htaccess` e atualize a linha `RewriteBase` para o novo caminho:
> ```apache
> RewriteBase /nome-da-sua-pasta/backend/
> ```
> Esquecer esse ajuste é a causa mais comum de erro `404` ao chamar a API — o Apache tenta montar uma URL que não bate com o caminho real. Não precisa reiniciar o Apache depois de editar o `.htaccess`, ele é lido a cada requisição.

### 5.2. Ligar Apache e MySQL

Abra o painel de controle do XAMPP e clique em **Start** em **Apache** e em **MySQL**.

### 5.3. Criar o banco de dados

Duas formas, escolha uma:

**Opção A — phpMyAdmin (mais visual):**
1. Acesse `http://localhost/phpmyadmin`
2. Vá em **Importar**
3. Selecione o arquivo `docs/database.sql`
4. Clique em **Executar**

**Opção B — linha de comando:**
```bash
mysql -u root -p < docs/database.sql
```
(por padrão o XAMPP usa usuário `root` sem senha, então normalmente é só apertar Enter quando pedir a senha)

Isso cria o banco `guarda_chaves`, as três tabelas (`usuarios`, `chaves`, `emprestimos`) e já insere dados de demonstração (ver seção 9).

> Se for rodar o teste de carga (seção 12), importe também `docs/database_teste.sql` — cria um segundo banco (`guarda_chaves_teste`), isolado, usado só pelo k6.

### 5.4. Conferir a conexão

Se o seu MySQL local tiver usuário/senha diferentes do padrão do XAMPP, ajuste em `backend/config/database.php`:

```php
$usuario = 'root';
$senha = '';
```

### 5.5. Acessar o sistema

Com Apache e MySQL rodando e o banco criado, abra no navegador:

```
http://localhost/projeto-controle-emprestimos-chaves/frontend/index.html
```

> O caminho é longo porque o Apache serve tudo a partir do `htdocs`. Se quiser um endereço curto (ex.: `http://guardachaves.local/`), configure um Virtual Host — veja `docs/httpd-vhosts-exemplo.conf` para um modelo pronto.

---

## 6. Estrutura de pastas

```
projeto-controle-emprestimos-chaves/
│
├── backend/
│   ├── config/
│   │   └── database.php        # conexão PDO (banco principal ou de teste)
│   ├── controller/              # recebe HTTP, chama o service, devolve JSON
│   │   ├── AuthController.php
│   │   ├── ChaveController.php
│   │   └── EmprestimoController.php
│   ├── repository/              # único lugar que roda SQL
│   │   ├── UsuarioRepository.php
│   │   ├── ChaveRepository.php
│   │   └── EmprestimoRepository.php
│   ├── routes/                  # mapeia endpoint -> controller
│   │   ├── auth.php
│   │   ├── chaves.php
│   │   └── emprestimos.php
│   ├── services/                # regras de negócio
│   │   ├── AuthService.php
│   │   ├── ChaveService.php
│   │   └── EmprestimoService.php
│   ├── startup/
│   │   └── bootstrap.php        # sessão, headers, erros, conexão
│   ├── .htaccess                # reescreve /api/* para index.php (RewriteBase!)
│   └── index.php                # ponto de entrada / roteador
│
├── docs/
│   ├── database.sql              # banco principal (demonstração)
│   ├── database_teste.sql        # banco isolado para o teste de carga
│   ├── httpd-vhosts-exemplo.conf # modelo de Virtual Host (caminho curto)
│   ├── arquitetura-backend.md
│   ├── arquitetura-frontend.md
│   └── integracao-sistemas.md
│
├── frontend/
│   ├── index.html               # login
│   ├── sistema.html             # chaves, meu empréstimo, histórico
│   ├── style.css
│   └── js/
│       ├── api.js               # única camada que faz fetch()
│       ├── ui.js                # única camada que mexe no DOM
│       ├── login.js             # orquestra a página de login
│       └── sistema.js           # orquestra a página do sistema
│
├── tests/
│   └── teste_carga.js           # teste de carga (k6)
│
└── README.md
```

Detalhamento de cada camada em [`docs/arquitetura-backend.md`](docs/arquitetura-backend.md) e [`docs/arquitetura-frontend.md`](docs/arquitetura-frontend.md).

---

## 7. Endpoints da API

Todas as respostas são JSON no formato `{ "sucesso": bool, "mensagem": string, "dados"?: ... }`.

| Método | Endpoint | O que faz | Autenticação |
|---|---|---|---|
| POST | `/api/login` | Autentica e inicia sessão | Não |
| POST | `/api/logout` | Encerra a sessão | Não |
| GET | `/api/chaves` | Lista todas as chaves e seus status | Sim |
| POST | `/api/emprestimos` | Retira uma chave (`{ "chave_id": n }`) | Sim |
| PATCH | `/api/emprestimos` | Devolve uma chave (`{ "emprestimo_id": n }`) | Sim |
| GET | `/api/meu-emprestimo` | Empréstimo ativo do usuário logado | Sim |
| GET | `/api/historico` | Histórico de empréstimos do usuário logado | Sim |

---

## 8. Regras de negócio (resumo)

1. **Login**: e-mail + senha conferidos com `password_verify()`.
2. **Autenticação obrigatória**: sem sessão ativa, `401` em qualquer operação protegida.
3. **Chave disponível**: só é possível retirar uma chave com `status = disponivel`; senão, `409`.
4. **Um empréstimo ativo por usuário**: quem já tem uma chave emprestada não pode retirar outra até devolver.
5. **Retirada e devolução são atômicas**: cada uma envolve duas tabelas (`emprestimos` e `chaves`) e roda dentro de uma transação, pra nunca ficar um estado inconsistente entre as duas.
6. **Um usuário só devolve o próprio empréstimo** — tentar devolver o de outra pessoa retorna `403`.

Fluxo completo, passo a passo, para retirada e devolução: [`docs/integracao-sistemas.md`](docs/integracao-sistemas.md).

---

## 9. Usuários de teste

Já inseridos pelo `docs/database.sql`, com hashes reais de `password_hash()`:

| E-mail | Senha | Tipo |
|---|---|---|
| `admin@guardachaves.com` | `admin123` | admin |
| `joao@guardachaves.com` | `joao123` | usuario |
| `maria@guardachaves.com` | `maria123` | usuario |

João Lira já entra com um empréstimo ativo (Chave do Almoxarifado), pra dar pra demonstrar a regra de "chave indisponível" sem precisar retirar nada antes.

> Antes de uma apresentação real, remova o bloco de credenciais visível no rodapé de `frontend/index.html` — ele só existe pra facilitar o desenvolvimento.

---

## 10. Segurança — resumo

- **Senhas**: nunca em texto puro — `password_hash()` na criação, `password_verify()` no login.
- **SQL Injection**: PDO com *prepared statements* em 100% das queries (`ATTR_EMULATE_PREPARES => false`, prepared statements nativos do MySQL).
- **Sessão**: autenticação via `$_SESSION`, sem token/JWT.
- **Autorização**: cada operação confere se o usuário está logado e, na devolução, se o empréstimo pertence a ele.
- **Erros**: mensagens internas do PDO nunca chegam ao usuário final; ficam só no log do Apache.

Detalhes de implementação: [`docs/arquitetura-backend.md`](docs/arquitetura-backend.md#segurança).

---

## 11. Testes automatizados

Ainda não implementados nesta versão do projeto (Etapa 12 do roteiro de desenvolvimento, pendente). Quando forem escritos, devem cobrir principalmente as regras de negócio da seção 8 — login válido/inválido, retirada de chave disponível/indisponível, limite de um empréstimo ativo por usuário, e devolução própria vs. de terceiros.

---

## 12. Teste de carga (k6)

Além dos testes funcionais (pendentes), o projeto tem um teste de **carga/concorrência** em `tests/teste_carga.js`, usando [k6](https://k6.io/). Ele prova, com número de requisições reais, que a regra "uma chave só pode ser retirada por vez" (seção 8, regras 3 a 5) se sustenta mesmo com 50 usuários simultâneos tentando retirar a mesma chave.

### 12.1. Banco isolado para o teste

O teste de carga **nunca** toca no banco `guarda_chaves` usado na demonstração ao vivo. Ele se conecta a um banco separado (`guarda_chaves_teste`), criado a partir de `docs/database_teste.sql` — uma cópia exata do banco principal, só com outro nome.

Isso funciona através de um header HTTP (`X-Ambiente-Teste: true`) que o script do k6 manda em toda chamada; `backend/config/database.php` olha esse header e escolhe o banco de acordo. O frontend normal nunca envia esse header, então o fluxo real de uso nunca é afetado.

> Essa é uma simplificação pensada para rodar tudo no mesmo Apache/XAMPP sem precisar de um segundo Virtual Host. Ver o aviso de segurança direto no comentário de `database.php`.

### 12.2. Instalar o k6

```bash
winget install k6 --source winget
```

(Ou veja outras opções de instalação em [k6.io/docs/get-started/installation](https://k6.io/docs/get-started/installation/).)

### 12.3. Rodar o teste

Com Apache, MySQL e os dois bancos (`guarda_chaves` e `guarda_chaves_teste`) já criados:

```bash
k6 run tests/teste_carga.js
```

Se o caminho do projeto no seu `htdocs` for diferente de `projeto-controle-emprestimos-chaves`, ajuste a constante `BASE_URL` no topo do arquivo antes de rodar.

O teste se limpa sozinho antes e depois de cada execução (`setup()`/`teardown()` devolvem a chave usada no teste automaticamente) — pode rodar várias vezes seguidas sem precisar mexer no banco manualmente.

---

## 13. Como contribuir

Este é um projeto de estudo, então "contribuir" aqui significa principalmente: manter a mesma lógica ao adicionar algo novo.

1. **Respeite a separação de camadas.** Regra de negócio nova → `services/`. SQL novo → `repository/`. Nada de SQL dentro de controller, e nada de regra de negócio dentro de rota.
2. **Um endpoint novo = 4 passos**: método no Repository (se precisar de SQL novo) → método no Service (regra de negócio) → método no Controller (tradução HTTP/JSON) → uma condição a mais no arquivo de `routes/` correspondente. Se for um recurso totalmente novo, adicione o arquivo de rota também à lista `$arquivosDeRota` em `backend/index.php`.
3. **Siga o padrão de resposta já usado**: `{ "sucesso": bool, "mensagem": string, "dados"?: ... }`, com o código HTTP condizente (`400`/`401`/`403`/`404`/`409`).
4. **No frontend, mantenha a separação `api.js` / `ui.js`.** Chamada de rede nova → `api.js`. Renderização nova → `ui.js`. O arquivo da página (`login.js`/`sistema.js`) só orquestra as duas.
5. **Nomeação**: nomes de domínio (variáveis, tabelas, pastas específicas do projeto) em português; termos da linguagem/biblioteca (`fetch`, `PDO`, `try/catch`) permanecem em inglês, como já usado em todo o código.
6. **Comentários explicam intenção**, não repetem o código — veja qualquer arquivo existente como referência de tom.
7. **Se renomear a pasta do projeto**, atualize `backend/.htaccess` (`RewriteBase`) e a constante `BASE_URL` de `tests/teste_carga.js` — são os dois lugares onde o caminho fica "hardcoded".
8. Antes de abrir mão da simplicidade (nova dependência, novo padrão de arquitetura), releia a seção "Restrições para evitar *overengineering*" do prompt original — o objetivo do projeto é ser pequeno o suficiente pra ser explicado por inteiro.