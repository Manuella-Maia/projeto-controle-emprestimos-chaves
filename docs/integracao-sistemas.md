# Integração entre Sistemas

Este documento explica como as peças separadas (frontend, backend,
banco) se conectam para formar um sistema só — que é o objetivo
central deste projeto (ver seção 20 do prompt original).

---

## O papel do `htdocs`

O Apache (que vem junto no XAMPP) só responde a requisições para
arquivos que estão dentro da pasta `htdocs`. É por isso que o
primeiro passo de qualquer configuração é colocar o projeto lá dentro
— fora dessa pasta, para o Apache, o projeto simplesmente não existe.

```
xampp/htdocs/
    └── sa_projeto_integracao/
        ├── backend/     → http://localhost/sa_projeto_integracao/backend/...
        └── frontend/    → http://localhost/sa_projeto_integracao/frontend/...
```

Frontend e backend são servidos pelo **mesmo** Apache, só em pastas
diferentes — não são dois servidores separados. Isso é o que permite
usar sessão PHP (cookie) sem se preocupar com CORS "de verdade": as
duas pontas estão na mesma origem (`http://localhost`).

## Do clique ao banco de dados: o caminho completo

Usando a retirada de uma chave como exemplo:

```
1. Usuário clica em "Retirar" na tela (sistema.html)
        │
2. ui.js dispara o callback aoClicarRetirar(chaveId)  [sistema.js]
        │
3. api.js faz fetch('POST', '../backend/api/emprestimos',
        { chave_id: 2 }, credentials: 'same-origin')
        │
        ▼  (o navegador manda o cookie PHPSESSID automaticamente)
4. Apache recebe a requisição em backend/
        │
5. backend/.htaccess reescreve para backend/index.php
        │  (a URL original — com toda a subpasta do htdocs — chega
        │   intacta em $_SERVER['REQUEST_URI'])
        │
6. index.php extrai o caminho a partir do primeiro "/api" e o método
        │
7. routes/emprestimos.php identifica POST /api/emprestimos
        │
8. EmprestimoController::retirar() lê o corpo JSON
        │
9. EmprestimoService::retirar() roda as regras de negócio:
        - AuthService lê $_SESSION (populado no login) e confirma
          que existe um usuário logado
        - ChaveRepository/EmprestimoRepository consultam o MySQL
        - se tudo estiver certo, abre uma transação, grava o novo
          empréstimo e atualiza o status da chave, e commita
        │
10. EmprestimoController devolve JSON: { sucesso, mensagem, dados }
        │
11. api.js recebe a resposta (sem lançar erro, mesmo se for 409/401)
        │
12. sistema.js decide o que fazer com o resultado:
        - se sucesso: mostra mensagem de sucesso e recarrega as 3
          seções da tela (chaves, meu-empréstimo, histórico)
        - se falha: mostra a mensagem de erro vinda da própria API
```

Do passo 1 ao 12, nenhuma regra de negócio "de verdade" acontece no
JavaScript — ele só coleta a intenção do usuário e reage ao que a API
devolve. Toda decisão (a chave está disponível? o usuário já tem
outra emprestada?) acontece dentro do passo 9, no PHP.

## Sessão: como o backend sabe quem está logado

1. No login bem-sucedido, `AuthService::login()` grava
   `$_SESSION['usuario_id']` (entre outros campos).
2. O PHP, por conta própria, manda ao navegador um cookie
   (`PHPSESSID`) identificando essa sessão.
3. Toda chamada seguinte do `fetch()` inclui `credentials:
   'same-origin'` — isso faz o navegador reenviar o cookie
   automaticamente, sem o JavaScript precisar guardar nem manipular
   nada relacionado a autenticação.
4. No backend, qualquer Service que precisa saber "quem está logado"
   chama `AuthService::idUsuarioLogado()`, que simplesmente lê
   `$_SESSION['usuario_id']` — se não existir, lança uma exceção que
   o Controller traduz em `401`.

Esse é o motivo de o projeto **não** usar JWT: a sessão PHP já
resolve o problema de "lembrar quem está logado entre requisições"
sem precisar que o frontend guarde e envie um token manualmente.

## Por que existe um `.htaccess`

Sem ele, uma requisição para `/backend/api/chaves` faria o Apache
procurar, literalmente, um arquivo ou pasta chamada `chaves` dentro
de `backend/api/` — que não existe — e devolveria `404` antes mesmo
do PHP rodar. O `.htaccess` reescreve qualquer caminho que não seja
um arquivo/pasta real para `index.php`, que então decide o que fazer
com a URL a partir do PHP.

## Formato de resposta combinado entre as duas pontas

Toda resposta da API segue o mesmo formato, e o frontend depende
disso para funcionar de forma previsível em qualquer tela:

```json
{ "sucesso": true,  "mensagem": "...", "dados": { } }
{ "sucesso": false, "mensagem": "..." }
```

`ui.js` nunca precisa adivinhar a estrutura de uma resposta — ela é
sempre a mesma, e é esse contrato (não a implementação de nenhum dos
dois lados) que faz frontend e backend poderem evoluir de forma
relativamente independente.

## Banco de dados: onde cada regra realmente é garantida

Vale reforçar: as *foreign keys* entre `emprestimos.usuario_id` →
`usuarios.id` e `emprestimos.chave_id` → `chaves.id` garantem
**integridade referencial** (não é possível um empréstimo apontar
para um usuário ou chave que não existe), mas regras como "só um
empréstimo ativo por usuário" ou "chave já emprestada" **não** são
constraints do MySQL — são verificadas em PHP, no
`EmprestimoService`, antes de qualquer escrita. O banco garante que
os dados são consistentes entre si; o Service garante que eles fazem
sentido dentro do fluxo do sistema.
