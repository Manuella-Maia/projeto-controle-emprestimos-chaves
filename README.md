# Guarda-Chaves Digital

Sistema web para controle de empréstimo e devolução de chaves físicas (salas, laboratórios, almoxarifado etc.), desenvolvido como projeto didático de **integração de sistemas**: frontend, backend, API, regras de negócio e banco de dados conversando de ponta a ponta, de um jeito simples o suficiente para todo o fluxo ser explicado numa apresentação.

> Documentação relacionada: [`docs/arquitetura-backend.md`](docs/arquitetura-backend.md), [`docs/arquitetura-frontend.md`](docs/arquitetura-frontend.md) e [`docs/integracao-sistemas.md`](docs/integracao-sistemas.md).

---

## 1. Ideia do projeto

Muitos ambientes (escolas, empresas, laboratórios) ainda controlam o empréstimo de chaves físicas numa planilha ou num caderno na portaria. O Guarda-Chaves Digital resolve isso com um fluxo simples:

1. o usuário loga no sistema;
2. vê quais chaves estão disponíveis;
3. retira a que precisa;
4. o sistema impede que duas pessoas peguem a mesma chave, ou que uma pessoa pegue duas chaves ao mesmo tempo;
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

Propositalmente **fora** do escopo: Laravel/Symfony, JWT, Docker, ORM, React/Vue/Angular, microsserviços — ver a justificativa em [`docs/arquitetura-backend.md`](docs/arquitetura-backend.md#por-que-sem-framework).

---

## 3. Pré-requisitos

- [XAMPP](https://www.apachefriends.org/) instalado (Apache + MySQL + PHP 8+)
- Um navegador atual (Chrome, Firefox, Edge) — o frontend usa ES Modules, que exigem um navegador moderno
- Opcional: Postman ou Insomnia, para testar a API diretamente

---

## 4. Como configurar e rodar

### 4.1. Colocar o projeto dentro do `htdocs`

O Apache só enxerga o que está dentro da pasta `htdocs` do XAMPP. Copie (ou mova) a pasta inteira do projeto para lá:

| Sistema | Caminho padrão do `htdocs` |
|---|---|
| Windows | `C:\xampp\htdocs\` |
| Linux | `/opt/lampp/htdocs/` |
| macOS | `/Applications/XAMPP/htdocs/` |

Resultado esperado:

```
htdocs/
└── sa_projeto_integracao/
    ├── backend/
    ├── frontend/
    └── docs/
```

### 4.2. Ligar Apache e MySQL

Abra o painel de controle do XAMPP e clique em **Start** em **Apache** e em **MySQL**.

### 4.3. Criar o banco de dados

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

Isso cria o banco `guarda_chaves`, as três tabelas (`usuarios`, `chaves`, `emprestimos`) e já insere dados de demonstração (ver seção 8).

### 4.4. Conferir a conexão

Se o seu MySQL local tiver usuário/senha diferentes do padrão do XAMPP, ajuste em `backend/config/database.php`:

```php
$usuario = 'root';
$senha = '';
```

### 4.5. Acessar o sistema

Com Apache e MySQL rodando e o banco criado, abra no navegador:

```
http://localhost/sa_projeto_integracao/frontend/index.html
```

---

## 5. Estrutura de pastas

```
sa_projeto_integracao/
│
├── backend/
│   ├── config/
│   │   └── database.php        # conexão PDO
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
│   ├── .htaccess                # reescreve /api/* para index.php
│   └── index.php                # ponto de entrada / roteador
│
├── docs/
│   ├── database.sql
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
└── README.md
```

Detalhamento de cada camada em [`docs/arquitetura-backend.md`](docs/arquitetura-backend.md) e [`docs/arquitetura-frontend.md`](docs/arquitetura-frontend.md).

---

## 6. Endpoints da API

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

## 7. Regras de negócio (resumo)

1. **Login**: e-mail + senha conferidos com `password_verify()`.
2. **Autenticação obrigatória**: sem sessão ativa, `401` em qualquer operação protegida.
3. **Chave disponível**: só é possível retirar uma chave com `status = disponivel`; senão, `409`.
4. **Um empréstimo ativo por usuário**: quem já tem uma chave emprestada não pode retirar outra até devolver.
5. **Retirada e devolução são atômicas**: cada uma envolve duas tabelas (`emprestimos` e `chaves`) e roda dentro de uma transação, pra nunca ficar um estado inconsistente entre as duas.
6. **Um usuário só devolve o próprio empréstimo** — tentar devolver o de outra pessoa retorna `403`.

Fluxo completo, passo a passo, para retirada e devolução: [`docs/integracao-sistemas.md`](docs/integracao-sistemas.md).

---

## 8. Usuários de teste

Já inseridos pelo `docs/database.sql`, com hashes reais de `password_hash()`:

| E-mail | Senha | Tipo |
|---|---|---|
| `admin@guardachaves.com` | `admin123` | admin |
| `joao@guardachaves.com` | `joao123` | usuario |
| `maria@guardachaves.com` | `maria123` | usuario |

João Lira já entra com um empréstimo ativo (Chave do Almoxarifado), pra dar pra demonstrar a regra de "chave indisponível" sem precisar retirar nada antes.

> Antes de uma apresentação real, remova o bloco de credenciais visível no rodapé de `frontend/index.html` — ele só existe pra facilitar o desenvolvimento.

---

## 9. Segurança — resumo

- **Senhas**: nunca em texto puro — `password_hash()` na criação, `password_verify()` no login.
- **SQL Injection**: PDO com *prepared statements* em 100% das queries (`ATTR_EMULATE_PREPARES => false`, prepared statements nativos do MySQL).
- **Sessão**: autenticação via `$_SESSION`, sem token/JWT.
- **Autorização**: cada operação confere se o usuário está logado e, na devolução, se o empréstimo pertence a ele.
- **Erros**: mensagens internas do PDO nunca chegam ao usuário final; ficam só no log do Apache.

Detalhes de implementação: [`docs/arquitetura-backend.md`](docs/arquitetura-backend.md#segurança).

---

## 10. Testes

Ainda não implementados nesta versão do projeto (Etapa 12 do roteiro de desenvolvimento, pendente). Quando forem escritos, devem cobrir principalmente as regras de negócio da seção 7 — login válido/inválido, retirada de chave disponível/indisponível, limite de um empréstimo ativo por usuário, e devolução própria vs. de terceiros.

---

## 11. Como contribuir

Este é um projeto de estudo, então "contribuir" aqui significa principalmente: manter a mesma lógica ao adicionar algo novo.

1. **Respeite a separação de camadas.** Regra de negócio nova → `services/`. SQL novo → `repository/`. Nada de SQL dentro de controller, e nada de regra de negócio dentro de rota.
2. **Um endpoint novo = 4 passos**: método no Repository (se precisar de SQL novo) → método no Service (regra de negócio) → método no Controller (tradução HTTP/JSON) → uma condição a mais no arquivo de `routes/` correspondente. Se for um recurso totalmente novo, adicione o arquivo de rota também à lista `$arquivosDeRota` em `backend/index.php`.
3. **Siga o padrão de resposta já usado**: `{ "sucesso": bool, "mensagem": string, "dados"?: ... }`, com o código HTTP condizente (`400`/`401`/`403`/`404`/`409`).
4. **No frontend, mantenha a separação `api.js` / `ui.js`.** Chamada de rede nova → `api.js`. Renderização nova → `ui.js`. O arquivo da página (`login.js`/`sistema.js`) só orquestra as duas.
5. **Nomeação**: nomes de domínio (variáveis, tabelas, pastas específicas do projeto) em português; termos da linguagem/biblioteca (`fetch`, `PDO`, `try/catch`) permanecem em inglês, como já usado em todo o código.
6. **Comentários explicam intenção**, não repetem o código — veja qualquer arquivo existente como referência de tom.
7. Antes de abrir mão da simplicidade (nova dependência, novo padrão de arquitetura), releia a seção "Restrições para evitar *overengineering*" do prompt original — o objetivo do projeto é ser pequeno o suficiente pra ser explicado por inteiro.