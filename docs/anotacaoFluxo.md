## Fluxo do sistema

### Fluxo da requisição

Frontend
   │
   │ GET /api/emprestimos
   ▼
Apache
   │
   │ .htaccess
   ▼
index.php
   │
   │ identifica método + caminho
   ▼
routes/emprestimos.php
   │
   │ "essa requisição é minha"
   ▼
EmprestimoController
   │
   │ processa a requisição
   ▼
Service
   │
   │ aplica regras de negócio
   ▼
Repository
   │
   │ consulta o banco
   ▼
MariaDB


### Fluxo da resposta

MariaDB
   ↓
Repository
   ↓
Service
   ↓
Controller
   ↓
Resposta HTTP + JSON
   ↓
Frontend

### Visualização de API X Back-end

API REST
────────────────────
GET    /api/chaves
POST   /api/chaves
GET    /api/emprestimos
DELETE /api/emprestimos/15

Métodos HTTP
Status HTTP
JSON
Formato das respostas


Backend
────────────────────
.htaccess
index.php
routes/
controllers/
services/
repositories/
configuração
banco de dados
regras de negócio


A API REST é uma interface do backend que permite que outros sistemas se comuniquem com ele.

A API define a interface externa; o backend contém a implementação interna.

A API REST define a interface de comunicação que o backend expõe para outros sistemas; o backend contém a implementação interna responsável por processar essas requisições e produzir as respostas da API.