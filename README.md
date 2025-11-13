# Sistema de Agendamento de Aulas - Forjados Music Studio

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)

Este repositório contém o código-fonte do projeto de TCC: um sistema web completo para o gerenciamento de agendamentos de aulas, desenvolvido para o estúdio de música **Forjados Music Studio**.

A plataforma permite que administradores, professores e alunos interajam em um ambiente digital, facilitando a marcação, visualização e gerenciamento de aulas.

## 🌟 Principais Funcionalidades

O sistema é dividido em três níveis de acesso, cada um com suas próprias funcionalidades:

### 👤 Nível Administrador (`admin`)
* **Gestão Total de Usuários:** Cadastrar, editar e excluir perfis de **Alunos** e **Professores**.
* **Controle de Acesso:** Redefinir senhas de usuários e forçar a troca no primeiro login.
* **Visualização Completa:** Acesso a todas as aulas agendadas no sistema, podendo filtrar por status (Agendado, Realizado, Cancelado) ou por data (Aulas de Hoje).
* **Gerenciamento de Solicitações:** Aprovar ou rejeitar solicitações de alteração de dados críticos (como nome, CPF, email) enviadas por alunos e professores.
* **Dashboard de Estatísticas:** Visualização rápida do número total de professores ativos, alunos ativos e aulas marcadas para o dia.

### 👨‍🏫 Nível Professor (`professor`)
* **Agendamento de Aulas:** Marcar novas aulas para seus alunos, com verificação de conflito de horário em tempo real (tanto para o professor quanto para o aluno).
* **Gerenciamento de Aulas:**
    * Marcar **presença** ou **falta** para aulas realizadas.
    * **Cancelar** aulas (com motivo obrigatório).
    * **Reagendar** aulas (com verificação de conflito).
* **Dashboard Pessoal:** Visualização das suas próximas aulas e estatísticas de aulas para o dia.
* **Gestão de Perfil:** Editar suas próprias informações não-críticas (como biografia, telefone, endereço) e alterar a própria senha.
* **Solicitação de Alterações:** Enviar pedidos formais para o administrador alterar dados críticos.

### 🎓 Nível Aluno (`aluno`)
* **Visualização de Aulas:** Acesso a um painel com o histórico de suas aulas. (Funcionalidade de `pages/chamadas.php` no modo aluno).
* **Gestão de Perfil:** Editar suas próprias informações não-críticas (instrumento, nível, objetivos, etc.) e alterar a própria senha.
* **Solicitação de Alterações:** Enviar pedidos formais para o administrador alterar dados críticos (Nome, CPF, etc.).

## 💻 Tecnologias Utilizadas

Este projeto foi construído de forma "nativa" (sem um framework PHP principal), utilizando as seguintes tecnologias:

* **Backend:** **PHP 8+** (utilizando `mysqli` para conexão com o banco).
* **Frontend:** HTML5, CSS3 (com um design "glassmorphism" moderno) e JavaScript (Vanilla JS).
* **Banco de Dados:** **MySQL**.
* **Bibliotecas JavaScript:**
    * **DataTables.js:** Para paginação, busca e ordenação avançada das tabelas.
    * **Flatpickr:** Para seleção de data e hora (calendários e relógios).
    * **TomSelect:** Para caixas de seleção (dropdowns) mais amigáveis e com busca.
    * **jQuery:** Como dependência principal para o DataTables.

## 🚀 Como Executar o Projeto Localmente

Para rodar este projeto em sua máquina local, você precisará de um ambiente de servidor PHP/MySQL (como XAMPP, WAMP ou MAMP).

### 1. Preparação do Ambiente
1.  **Clone o repositório:**
    ```bash
    git clone [https://github.com/seu-usuario/TCC-main.git](https://github.com/seu-usuario/TCC-main.git)
    ```
2.  Mova a pasta `TCC-main` para o diretório do seu servidor local (ex: `C:/xampp/htdocs/`).
3.  Inicie os serviços **Apache** e **MySQL** no painel de controle do seu servidor.

### 2. Configuração do Banco de Dados
1.  Acesse o **phpMyAdmin** (geralmente `http://localhost/phpmyadmin`).
2.  Crie um novo banco de dados. O nome recomendado (usado no arquivo de configuração) é `tcc_local`.
3.  Selecione o banco `tcc_local`, vá para a aba **Importar**.
4.  Clique em "Escolher arquivo" e selecione o arquivo `config/tcc_local.sql` do projeto.
5.  Clique em **Executar**. Isso criará todas as tabelas e importará alguns dados de exemplo (usuários admin, professor e aluno).

### 3. Configuração da Conexão
1.  Abra o arquivo `config/database.php` no seu editor de código.
2.  Verifique se as constantes `DB_HOST`, `DB_NAME`, `DB_USER` e `DB_PASS` correspondem à sua configuração local do MySQL.

    ```php
    // --- Configurações do Banco de Dados Local ---
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'tcc_local'); // O nome do banco que você criou
    define('DB_USER', 'root');       // Usuário padrão do XAMPP/WAMP
    define('DB_PASS', '');           // Senha padrão do XAMPP/WAMP (geralmente vazia)
    ```

### 4. Acesso ao Sistema
Pronto! Agora você pode acessar o sistema pelo seu navegador.

* **Página de Login:** `http://localhost/TCC-main/login.php`
* **Página do Dashboard (após login):** `http://localhost/TCC-main/dashboard.php`

## 🔑 Acesso de Teste (Usuários Padrão)

O arquivo `tcc_local.sql` já inclui usuários de teste para cada nível de acesso.

**Importante:** A senha de todos os usuários padrão é `12345678`.

* **Administrador:**
    * **Email:** `admin@sistema.com`
    * **Senha:** `12345678`
* **Professor:**
    * **Email:** `prof@sistema.com`
    * **Senha:** `12345678`
* **Aluno:**
    * **Email:** `aluno@sistema.com`
    * **Senha:** `12345678`

*(Nota: Se as senhas acima não funcionarem, utilize o script `dev_script.php` para definir uma nova senha para o admin).*

### 🛠️ Script de Desenvolvimento

O projeto inclui um script de desenvolvimento em `dev_script.php`. **NÃO USE EM PRODUÇÃO.**

Acesse `http://localhost/TCC-main/dev_script.php` para:
1.  **Criar usuários em massa** (alunos, professores ou admins) para testes de performance e capacidade.
2.  **Editar o login de um Admin,** permitindo que você defina um novo email e uma nova senha para uma conta de administrador (útil para recuperar o acesso).

## 🗃️ Estrutura de Arquivos
