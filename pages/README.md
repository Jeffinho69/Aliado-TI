# 🏛️ Aliado TI - Sistema Integrado da Câmara Municipal

![Status](https://img.shields.io/badge/Status-Em_Desenvolvimento-yellow)
![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=flat&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1?style=flat&logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5-7952B3?style=flat&logo=bootstrap&logoColor=white)

Sistema web completo desenvolvido para a gestão de TI e processos administrativos da Câmara Municipal de Vitória de Santo Antão. O projeto centraliza suporte técnico (Helpdesk), gestão de ativos, solicitações oficiais e conta com assistência de Inteligência Artificial.

## 🚀 Funcionalidades Principais

### 🎧 Service Desk & Suporte
- **Abertura de Chamados:** Sistema de tickets com prioridades (Baixa, Média, Alta, Crítica).
- **Workflow:** Atribuição de técnicos, chat interno no chamado e histórico de interações.
- **Dashboard:** Visão geral com métricas e usuários online em tempo real.

### 🤖 Inteligência Artificial (Aliada TI)
- **Chatbot Integrado:** Assistente virtual baseada no Google Gemini para tirar dúvidas e realizar triagem automática de problemas.
- **Abertura Automática:** A IA detecta problemas técnicos no chat e pode abrir chamados automaticamente via JSON.
- **Digitalização Jurídica:** Módulo de OCR que lê PDFs/Imagens e transcreve para Word/HTML formatado.

### 📄 Solicitações Oficiais
- **Ofícios e Memos:** Criação de documentos oficiais com layout padrão da Câmara.
- **Fluxo de Aprovação:** Níveis de permissão para Solicitante, Compras e Diretoria.
- **Assinatura Digital:** Validação visual e impressão formatada para folha A4.

### 🛠️ Gestão de TI
- **Inventário:** Controle de ativos (computadores, impressoras), localização e termos de empréstimo.
- **Cofre de Senhas:** Armazenamento criptografado (AES-256) de credenciais de servidores e serviços.
- **Monitoramento (NOC):** Verificação de status (Ping/Porta) de servidores locais e externos.
- **Manutenção:** Checklists para manutenção preventiva de equipamentos.

## 💻 Tecnologias Utilizadas

- **Back-end:** PHP (Nativo/Vanilla)
- **Banco de Dados:** MySQL / MariaDB
- **Front-end:** HTML5, CSS3, JavaScript, Bootstrap 5
- **APIs:** Google Gemini (Generative AI)
- **Servidor:** Apache

## ⚙️ Instalação e Configuração

### 1. Requisitos
- Servidor Web (XAMPP, Laragon ou Hospedagem Linux)
- PHP 7.4 ou superior
- MySQL

### 2. Banco de Dados
1. Crie um banco de dados chamado `sicat` (ou o nome de sua preferência).
2. Importe o arquivo `database.sql` (disponível na raiz do projeto) para criar as tabelas e usuários iniciais.

### 3. Configuração
1. Renomeie o arquivo `config/db.example.php` para `config/db.php`.
2. Configure as credenciais do banco:
```php
$host = 'localhost';
$dbname = 'seu_banco';
$user = 'root';
$pass = '';