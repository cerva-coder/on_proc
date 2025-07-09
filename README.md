# 🗂️ ON_Proc – Sistema de Gestão de Processos Administrativos

O **ON_Proc** é um sistema web moderno para **controle, gestão e acompanhamento de processos administrativos**. Desenvolvido com foco na segurança, usabilidade e rastreabilidade, ele é ideal para ambientes institucionais ou organizacionais que necessitam de fluxo documental estruturado.

---

## 🚀 Funcionalidades Principais

- ✅ **Criação de Processos** com NUP automático e tipos personalizados.
- 📎 **Protocolo de Documentos** em PDF com criptografia AES-256-CBC.
- 🕓 **Linha do Tempo dos Andamentos**, com visualização e download.
- 📊 **Painel com Filtros Avançados**, ações rápidas e estatísticas.
- 👥 **Gestão de Usuários, Departamentos e Permissões.**
- 🔐 **Segurança avançada** com controle de sessões, hashing e validações.

---

## 👤 Perfis de Usuário

| Perfil                | Permissões                                                                 |
|----------------------|----------------------------------------------------------------------------|
| `Administrador`       | Acesso total a todos os módulos e usuários                                |
| `Gerente de Departamento` | Gestão de processos pelo departamento                                 |
| `Protocolador`        | Anexar documentos aos processos designados                                |
| `Visualizador`        | Acesso apenas para leitura                                                 |

---

## 🛠️ Módulos do Sistema

### 🔐 Login Seguro (`login.php`)
- Hash com `password_hash()` e `password_verify()`
- Proteção contra força bruta com limite de tentativas e timeout
- Sessões e redirecionamento após login

### 🧭 Painel Principal (`painel.php`)
- Exibe todos os processos visíveis ao usuário
- Ações por perfil: protocolar, visualizar, editar, excluir
- Filtros por NUP, polo passivo, encarregado
- Paginação dinâmica e consulta SQL otimizada

### 📝 Criação de Processos (`process_create.php`)
- Formulário com campos obrigatórios: assunto, polo passivo, encarregado, tipo
- Geração automática do **NUP** (número único de protocolo)
- Tipos de processo com agrupamento e suporte a campo livre

### 📥 Protocolo de Documentos (`protocol.php`)
- Upload de PDFs até 50MB
- Criptografia com AES-256
- Campos obrigatórios e confirmação de senha
- Ação irreversível

### 📄 Visualização de Andamentos (`andamento.php`)
- Linha do tempo com todas as movimentações
- Visualização e download de PDFs
- Botão de baixar todo processo em .ZIP

### 🔎 Consulta e Ações em Processos (`view_process.php`)
- Lista de documentos por processo
- Ações: abrir, excluir (com geração de justificativa criptografada)
- Controles de permissão por sessão e perfil

---

## ⚙️ Módulo Administrativo (`admin.php`)

Apenas acessível para usuários com perfil `admin` e parcialmente para `gerente`.

- 👤 **Usuários** (`usuarios.php`) – Cadastro, edição, ativação
- 🏢 **Departamentos** (`departamentos.php`) – Adição, edição, exclusão
- 🗂️ **Tipos de Processo** (`admin_process_types.php`) – Gerenciamento por categoria
- 📌 **Atribuição de Processos** (`atribuir_processos.php`) – Vínculo de usuários a processos
- 🎨 **Aparência do Sistema** (`aparencia.php`) – Editor HTML integrado para cabeçalho
- 📊 **Estatísticas** (`estatisticas.php`) – Dashboard de dados por OM
- 🔍 **Verificação de Documentos** (`verificar_documentos.php`) – Controle de checagem

---

## 🔒 Segurança

- Todos os PDFs são criptografados com `AES-256-CBC` antes do armazenamento.
- Confirmação de senha obrigatória para ações sensíveis.
- Controle de acesso com permissões por função e departamento.
- Proteção contra ataques de força bruta no login.
- Exclusão de arquivos gera justificativa criptografada (com `FPDF`).

---

## 🖼️ Tecnologias Utilizadas

- PHP 7+
- MySQL / MariaDB
- Bootstrap 5 + Bootstrap Icons
- Select2 para campos dinâmicos
- FPDF para geração de documentos
- Sistema de sessões nativo em PHP

---

# ⚙️ ON_Proc – Instalação do Sistema

Bem-vindo ao **ON_Proc**, um sistema de gestão eletrônica de processos.  
Siga os passos abaixo para instalar e configurar o sistema no seu servidor.  

---

## 📥 1. Baixar os Arquivos

Faça upload de todos os arquivos do sistema para o seu servidor.  
Você pode usar um cliente **FTP** (como FileZilla) ou o **Gerenciador de Arquivos** do cPanel.

---

## 🗃️ 2. Importar o Banco de Dados

1. Acesse o **phpMyAdmin** ou outro gerenciador de banco MySQL.
2. Crie um novo banco de dados com o nome: `on_proc`
3. Importe o arquivo `on_proc.sql`, localizado na raiz do sistema.

---

## 🔐 3. Acessar o Sistema


Abra seu navegador e acesse a URL onde você enviou os arquivos.

Use as seguintes credenciais padrão para login:

- **Usuário:** `admin`  
- **Senha:** `onproc@123`

---

## 4️⃣ Altere a senha

Por segurança, após o primeiro acesso, vá até a área administrativa do sistema e **altere a senha do usuário admin**.

---

✅ Pronto! O sistema está instalado e pronto para uso.




