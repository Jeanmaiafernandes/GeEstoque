# GeEstoque - Sistema de Controle de Estoque

Sistema web completo de **controle de almoxarifado** desenvolvido com Laravel 13, SQLite e Tailwind CSS. Gerencia materiais, movimentações de estoque, requisições com fluxo de aprovação, inventário rotativo, etiquetas com código de barras/QR e importação de notas fiscais.

## Funcionalidades

- **Painel com KPIs** — materiais ativos, itens abaixo do mínimo, movimentações do dia, valor total em estoque e 4 gráficos interativos (Chart.js)
- **Cadastro de materiais** — código interno, EAN, categorias por setor, localização estruturada (corredor/prateleira/posição), estoque mínimo e máximo, precificação por peso (R$/kg)
- **Movimentações** — entrada, saída e ajuste com suporte a múltiplos itens por documento, leitura de código de barras/QR por câmera e rastreamento completo (Kardex)
- **Requisições** — fluxo de solicitação e aprovação com baixa automática de estoque
- **Inventário rotativo** — contagem cíclica guiada com detecção de divergência em tempo real
- **Relatórios** — saldo atual, itens abaixo do mínimo, entradas por período, consumo por setor — exportáveis em Excel e PDF
- **Etiquetas** — geração de Code 128 + QR code para impressão em folha A4
- **Importação de NF-e** — leitura de XML (exato) e PDF/DANFE (aproximado) com conferência linha a linha
- **Controle de acesso** — perfis Administrador e Operador com permissões granulares
- **Configurações** — dados da empresa, logo, parâmetros do sistema e backup automatizado

## Stack

| Componente | Tecnologia |
|---|---|
| Back-end | Laravel 13 · PHP 8.3+ |
| Banco de dados | SQLite |
| Front-end | Tailwind CSS v4 · Vite · Chart.js |
| Leitura câmera | html5-qrcode |
| Relatórios | PhpSpreadsheet (Excel) · DomPDF (PDF) |
| Código de barras | picqer/php-barcode-generator · bacon/bacon-qr-code |
| Importação NF-e | smalot/pdfparser (PDF) · SimpleXML (XML) |

## Requisitos

- PHP 8.3+ com extensões: `pdo_sqlite`, `gd`, `zip`, `mbstring`, `fileinfo`, `openssl`, `dom`
- Composer
- Node.js 18+

## Instalação

```bash
# Clone o repositório
git clone https://github.com/seu-usuario/geestoque.git
cd geestoque

# Instale as dependências
composer install
npm install

# Configure o ambiente
cp .env.example .env
php artisan key:generate

# Crie o banco e rode as migrations
touch database/database.sqlite
php artisan migrate

# Compile os assets
npm run build

# Inicie o servidor
php artisan serve
```

Acesse **http://localhost:8000**

### Login padrão

| Campo | Valor |
|---|---|
| E-mail | `admin@almoxarifado.local` |
| Senha | `Almox@2026` |

> Troque a senha imediatamente em **Usuários**.

## Desenvolvimento

```bash
# Servidor + Vite + Queue + Logs (concorrente)
composer dev

# Rodar testes (202 testes automatizados)
composer test

# Build de produção
npm run build
```

## Estrutura do Projeto

```
app/
├── Console/Commands/     # Comandos Artisan (importação, backup, recálculo)
├── Enums/                # MovementType, ItemType, RequisitionStatus...
├── Http/Controllers/     # 17 controllers (CRUD, relatórios, importação)
├── Models/               # 12 models (Item, StockMovement, Requisition...)
├── Services/             # StockService (lógica de estoque transacional)
└── Support/              # BarcodeService, NfeParser, ReportExport...

database/migrations/      # 20 migrations incrementais
resources/views/          # Blade templates com Tailwind CSS
tests/Feature/            # 202 testes (auth, CRUD, relatórios, import, peso)
```

## Testes

O projeto segue a prática **TDD** (Test-Driven Development) com 202 testes automatizados cobrindo:

- Autenticação e autorização (Admin/Operador)
- CRUD de materiais, categorias, setores e usuários
- Movimentações de estoque e saldo transacional
- Precificação por peso (20 testes dedicados)
- Requisições com fluxo de aprovação
- Inventário rotativo com ajuste automático
- Relatórios e exportação (Excel/PDF)
- Importação de NF-e (XML e PDF)
- Backup com retenção
- Performance (índices de banco)

```bash
php artisan test
```

## Comandos Artisan

| Comando | Descrição |
|---|---|
| `almoxarifado:importar "arquivo.xlsx"` | Importa materiais de planilha Excel |
| `almoxarifado:saldo-inicial` | Cria movimentos de saldo inicial |
| `almoxarifado:recalcular-saldo` | Recalcula saldos a partir do histórico |
| `almoxarifado:categorizar-setores` | Atribui categorias por palavra-chave |
| `almoxarifado:backup` | Backup do banco com retenção configurável |

## Implantação

Para ambiente de produção (rede local com HTTPS para câmera), veja o guia em [`IMPLANTACAO.md`](IMPLANTACAO.md).

## Licença

Este projeto está licenciado sob a [MIT License](LICENSE).
