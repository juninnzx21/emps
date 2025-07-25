# EMPS Framework - Teste Técnico

## 🛠️ Parte 4 – Teste com EMPS Framework

### ✅ Objetivo:
Validar autonomia técnica e capacidade de seguir documentação/códigos diversos.

---

## 📋 Etapas realizadas

### 1. Clonagem do repositório

Clonei o repositório oficial do EMPS6:

```bash
git clone https://github.com/AlexGnatko/EMPS6
```

2. Instalação e execução local

 * Coloquei o projeto na raiz do meu servidor local (por exemplo, htdocs/emps no XAMPP ou WAMP).

 * Certifiquei-me de que o PHP estava rodando corretamente (versão mínima recomendada: PHP 7.4+).

 * Acessei o projeto no navegador via:

```bash
http://localhost/emps/
```

## Alternativa via terminal (caso o Apache não funcione):
  Execute o comando abaixo no terminal (na pasta raiz do projeto):

 ```bash
php -S localhost:8000 -t www

```

E acesse:

```bash
http://localhost:8000/
```

3. Criação de nova rota com “Hello World”
  Criei uma nova rota acessível via:

```bash
http://localhost/emps/hello/
```

ou

```bash
http://localhost:8000/hello
```

📁 Caminho do arquivo:

```bash
/modules/hello/index.php
```

📄 Conteúdo do arquivo:

```bash
<?php
global $smarty;

$smarty->assign("msg", "Hello World");
$smarty->display("hello/hello.tpl");
```

4. Versionamento com Git
 * Inicializei o versionamento com Git no diretório

 * Comitei todas as alterações, incluindo a nova rota

📄 Documentação usada
Utilizei a documentação oficial do EMPS Framework:
🔗 https://emps.ag38.ru

✅ Conclusão
Todas as etapas solicitadas foram concluídas com sucesso:

✔️ Projeto clonado

✔️ Framework funcionando localmente

✔️ Nova rota com “Hello World”

✔️ Código versionado com Git

✔️ README criado e explicativo

