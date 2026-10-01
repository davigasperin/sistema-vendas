# Sistema de Vendas

Sistema completo de gerenciamento de vendas com controle de estoque, clientes, despesas e parcelamento.

## 🚀 Funcionalidades

- **Gestão de Vendas**: Registro de vendas com múltiplas formas de pagamento
- **Parcelamento**: Controle completo de parcelas com vencimentos
- **Produtos**: Cadastro, estoque baixo, ativa/inativo
- **Clientes**: Cadastro e histórico de compras
- **Despesas**: Controle de gastos com categorias
- **Dashboard**: Visão geral com estatísticas em tempo real
- **Relatórios**: Geração de PDFs de vendas
- **API REST**: Endpoints para integração externa
- **Autenticação**: Sistema completo com Laravel Breeze

## 🛠️ Tecnologias

- **Backend**: Laravel 12 (PHP 8.2+)
- **Frontend**: Blade Templates + TailwindCSS
- **Database**: MySQL
- **Autenticação**: Laravel Breeze + Sanctum
- **Docker**: MySQL em container

## 📋 Pré-requisitos

- PHP 8.2 ou superior
- Composer
- Node.js (para assets)
- Docker (para banco de dados MySQL)
- MySQL ou Docker

## 🔧 Instalação

### 1. Clone o repositório
```bash
git clone https://github.com/seu-usuario/sistema-vendas.git
cd sistema-vendas
```

### 2. Instale as dependências
```bash
composer install
npm install
```

### 3. Configure o ambiente
```bash
cp .env.example .env
```

Edite o arquivo `.env` com suas configurações de banco de dados:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=sistema_vendas
DB_USERNAME=root
DB_PASSWORD=sua_senha
```

### 4. Gere a chave da aplicação
```bash
php artisan key:generate
```

### 5. Execute as migrações
```bash
php artisan migrate
```

### 6. Execute os seeders (dados de exemplo)
```bash
php artisan db:seed
```

### 7. Compile os assets
```bash
npm run dev
```

### 8. Inicie o servidor
```bash
php artisan serve
```

Acesse: `http://localhost:8000`

## 👤 Credenciais de Acesso

Após executar os seeders, você pode criar um usuário através do sistema de registro ou usar as configurações padrão.

## 📁 Estrutura do Projeto

```
app/
├── Actions/          # Ações de negócio
├── Http/Controllers/ # Controladores
├── Models/           # Modelos Eloquent
├── Policies/         # Políticas de autorização
├── Providers/        # Provedores de serviço
├── Services/         # Serviços de negócio
└── Rules/           # Regras de validação
```

## 🔐 Segurança

- Middleware de headers de segurança
- Rate limiting em API
- Policies de autorização
- Proteção contra mass assignment
- Autenticação com Sanctum para API

## 📄 Licença

Este projeto está sob licença MIT.