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
| `Gerente de Departamento` | Gestão de processos da própria OM                                      |
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

## 📁 Estrutura de Arquivos

```bash
📂 system/
├── painel.php                # Painel principal do sistema
├── login.php                 # Tela de login com proteção
├── process_create.php        # Formulário para criação de processo
├── protocol.php              # Protocolo de documentos
├── andamento.php             # Linha do tempo dos processos
├── view_process.php          # Visualização e exclusão de documentos
├── admin.php                 # Painel de administração
├── overview.php              # Página de FAQ e ajuda
