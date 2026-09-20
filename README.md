# Guarda-Chaves Digital

Sistema web para controle de empréstimo e devolução de chaves físicas (salas, laboratórios, almoxarifado etc.), com **integração completa entre frontend, backend, API, regras de negócio e banco de dados**.

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

---

## 2. Tecnologias utilizadas

| Camada | Tecnologia |
|---|---|
| Frontend | HTML5, CSS3, JavaScript (ES Modules), `fetch()` |
| Backend | PHP puro (sem framework) |
| Comunicação | API HTTP/REST, JSON |
| Banco de dados | MySQL (via XAMPP/Laragon) |
| Acesso a dados | PDO com *prepared statements* |
| Autenticação | Sessão PHP (`$_SESSION`) |
| Ambiente | XAMPP ou Laragon (Apache + MySQL) |

---

## 3. Pré-requisitos

- [XAMPP](https://www.apachefriends.org/) **ou** [Laragon](https://laragon.org/download) instalado (Apache + MySQL + PHP 8+) — escolha um dos dois, não precisa dos dois ao mesmo tempo
- Um navegador atual (Chrome, Firefox, Edge) — o frontend usa ES Modules, que exigem um navegador moderno
- [Git](https://git-scm.com/) (ou GitHub Desktop), para clonar o projeto
- Opcional: Postman ou Insomnia, para testar a API diretamente

> **XAMPP ou Laragon?** Os dois rodam Apache + MySQL + PHP e servem para este projeto igualmente bem. XAMPP funciona em Windows, Linux e macOS; Laragon é exclusivo para **Windows**, mas costuma ser mais leve e tem alguns recursos extras (como domínios locais automáticos, ex. `http://guardachaves.test`). O restante deste README trata os dois como equivalentes — troque apenas o caminho da pasta conforme a ferramenta que você usa.

---

## 4. Como clonar o projeto

Com Git instalado, clone direto dentro da pasta pública do XAMPP (`htdocs`) ou do Laragon (`www`) — evita ter que mover a pasta depois:

**XAMPP:**
```bash
cd C:\xampp\htdocs        # Windows — ajuste para o seu caminho do htdocs
git clone <URL-do-repositorio> projeto-controle-emprestimos-chaves
```

**Laragon (Windows):**
```bash
cd C:\laragon\www
git clone <URL-do-repositorio> projeto-controle-emprestimos-chaves
```

Se preferir GitHub Desktop: **File → Clone repository**, e escolha como destino local a pasta `htdocs` do XAMPP ou a pasta `www` do Laragon.

Sem Git, também dá pra baixar o `.zip` do repositório (botão **Code → Download ZIP** no GitHub) e extrair diretamente dentro do `htdocs` ou do `www`.

> **O nome da pasta importa.** O backend usa um `.htaccess` com `RewriteBase` apontando para o caminho exato do projeto dentro do `htdocs`/`www` (ver seção 5.1). Se você clonar com um nome de pasta diferente de `projeto-controle-emprestimos-chaves`, precisa atualizar essa linha — veja o aviso na seção 5.1.

---

## 5. Como configurar e rodar

### 5.1. Confirmar que o projeto está dentro do `htdocs` (XAMPP) ou `www` (Laragon)

O Apache só enxerga o que está dentro dessa pasta:

| Sistema | Ferramenta | Caminho padrão |
|---|---|---|
| Windows | XAMPP | `C:\xampp\htdocs\` |
| Windows | Laragon | `C:\laragon\www\` |
| Linux | XAMPP (LAMPP) | `/opt/lampp/htdocs/` |
| macOS | XAMPP | `/Applications/XAMPP/htdocs/` |

Resultado esperado (o nome da pasta raiz muda conforme a ferramenta — `htdocs` no XAMPP, `www` no Laragon — mas a estrutura dentro dela é igual):

```
htdocs/  (ou www/, no Laragon)
└── projeto-controle-emprestimos-chaves/
    ├── backend/
    ├── frontend/
    └── docs/
```

> **Se você renomear essa pasta**, abra `backend/.htaccess` e atualize a linha `RewriteBase` para o novo caminho:
> ```apache
> RewriteBase /nome-da-sua-pasta/backend/
> ```
> Esquecer esse ajuste é a causa mais comum de erro `404` ao chamar a API — o Apache tenta montar uma URL que não bate com o caminho real. Não precisa reiniciar o Apache depois de editar o `.htaccess`, ele é lido a cada requisição. Isso vale igualmente para XAMPP e Laragon.

### 5.2. Ligar Apache e MySQL

Abra o painel de controle do XAMPP ou o Laragon e clique em **Start** em **Apache** e em **MySQL**.

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
(por padrão, tanto o XAMPP quanto o Laragon usam usuário `root` sem senha, então normalmente é só apertar Enter quando pedir a senha)

Isso cria o banco `guarda_chaves`, as três tabelas (`usuarios`, `chaves`, `emprestimos`) e já insere dados de demonstração (ver seção 9).

> Se for rodar o teste de carga (seção 12), importe também `docs/database_teste.sql` — cria um segundo banco (`guarda_chaves_teste`), isolado, usado só pelo k6.

### 5.4. Conferir a conexão

Se o seu MySQL local tiver usuário/senha diferentes do padrão (root sem senha, usado tanto pelo XAMPP quanto pelo Laragon), ajuste em `backend/config/database.php`:

```php
$usuario = 'root';
$senha = '';
```

### 5.5. Acessar o sistema

Com Apache e MySQL rodando e o banco criado, abra no navegador:

```
http://localhost/projeto-controle-emprestimos-chaves/frontend/index.html
```

Esse endereço funciona igual no XAMPP e no Laragon.

> O caminho é longo porque o Apache serve tudo a partir do `htdocs`/`www`. Se quiser um endereço curto (ex.: `http://guardachaves.local/`), configure um Virtual Host — veja `docs/httpd-vhosts-exemplo.conf` para um modelo pronto. No Laragon isso é ainda mais simples: com o **Auto Virtual Hosts** ativado, basta o projeto estar dentro do `www` para ficar disponível automaticamente em `http://projeto-controle-emprestimos-chaves.test/frontend/index.html`.

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
│   ├── anotacaoFluxo.md
│   ├── anotacaoHtaccess.md
│   ├── imgs/
│   │   ├── arquitetura.webp
│   │   └── fluxoIntegracao.png
│   ├── integracao-sistemas.md
│   ├── modelo_fisico_BD.txt
│   └── testesAPI.md
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

> Antes de qualquer uso em produção, remova o bloco de credenciais visível no rodapé de `frontend/index.html` — ele só existe pra facilitar o desenvolvimento.

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

Ainda não implementados nesta versão do projeto. Quando forem escritos, devem cobrir principalmente as regras de negócio da seção 8 — login válido/inválido, retirada de chave disponível/indisponível, limite de um empréstimo ativo por usuário, e devolução própria vs. de terceiros.

---

## 12. Como contribuir

1. **Respeite a separação de camadas.** Regra de negócio nova → `services/`. SQL novo → `repository/`. Nada de SQL dentro de controller, e nada de regra de negócio dentro de rota.
2. **Um endpoint novo = 4 passos**: método no Repository (se precisar de SQL novo) → método no Service (regra de negócio) → método no Controller (tradução HTTP/JSON) → uma condição a mais no arquivo de `routes/` correspondente. Se for um recurso totalmente novo, adicione o arquivo de rota também à lista `$arquivosDeRota` em `backend/index.php`.
3. **Siga o padrão de resposta já usado**: `{ "sucesso": bool, "mensagem": string, "dados"?: ... }`, com o código HTTP condizente (`400`/`401`/`403`/`404`/`409`).
4. **No frontend, mantenha a separação `api.js` / `ui.js`.** Chamada de rede nova → `api.js`. Renderização nova → `ui.js`. O arquivo da página (`login.js`/`sistema.js`) só orquestra as duas.
5. **Nomeação**: nomes de domínio (variáveis, tabelas, pastas específicas do projeto) em português; termos da linguagem/biblioteca (`fetch`, `PDO`, `try/catch`) permanecem em inglês, como já usado em todo o código.
6. **Comentários explicam intenção**, não repetem o código — veja qualquer arquivo existente como referência de tom.
7. **Se renomear a pasta do projeto**, atualize `backend/.htaccess` (`RewriteBase`) e a constante `BASE_URL` de `tests/teste_carga.js` — são os dois lugares onde o caminho fica "hardcoded". Isso vale para quem usa XAMPP ou Laragon.