1	Login	POST	
http://localhost/projeto-controle-emprestimos-chaves/backend/api/login	

{ 
    "email": "admin@guardachaves.com", 
    "senha": "admin123" 
}

2	Listar chaves	GET	

.../backend/api/chaves

3	Retirar chave	POST	
.../backend/api/emprestimos	

{ 
    "chave_id": 1 
}

4	Meu empréstimo	GET	
.../backend/api/meu-emprestimo

5	Devolver chave	PATCH 
.../backend/api/emprestimos	

{ 
    "emprestimo_id": 1 
}

6	Histórico	GET
.../backend/api/historico

7	Logout	POST	
.../backend/api/logout

... = http://localhost/projeto-controle-emprestimos-chaves/backend/api (mesmo prefixo de todas)