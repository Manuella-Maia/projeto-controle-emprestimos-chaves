Se a requisição recebida começar com /api/, encaminhe internamente para index.php
RewriteRule ^api/(.*)$ index.php [QSA,L]

Só faça o rewrite se o caminho solicitado não for um arquivo real (!-f) e não for um diretório real (!-d).
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

O Apache não sai direcionando tudo para o index.php

                 htdocs
                    │
                    ▼
       projeto-controle-emprestimos-chaves
                    │
                    ▼
                 backend
                    │
             encontra .htaccess
                    │
                    ▼
              regra de rewrite
                    │
                    ▼
              backend/index.php
                    │
                    ▼
                  routes


regra de rewrite: Quando chegar uma URL que tenha determinado formato, trate essa URL como se ela apontasse para outro caminho/arquivo.

O .htaccess possui uma regra de rewrite que encaminha internamente as requisições /api/* para o index.php