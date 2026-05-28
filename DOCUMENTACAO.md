# GeEstoque — Sistema de Almoxarifado

> **GeEstoque** é o nome do sistema (exibido no login, no menu e nos títulos, via `APP_NAME`).

Sistema web de **controle de estoque** (almoxarifado) para uma empresa com os setores
**Almoxarifado, Metalúrgica, Marcenaria e Expedição**. Controla entrada, saída e ajuste de
materiais, com saldo, estoque mínimo, valor em estoque, relatórios, etiquetas (código de
barras/QR) e importação de notas fiscais (NF-e em XML/PDF) e de planilha.

---

## 1. Tecnologias

- **Laravel 13** + **PHP 8.5**
- Banco **SQLite** (arquivo `database/database.sqlite` — carrega todos os dados)
- **Tailwind CSS v4** + **Vite** (front-end), **Chart.js** (gráficos), **html5-qrcode** (câmera)
- Bibliotecas: `phpoffice/phpspreadsheet` (Excel), `barryvdh/laravel-dompdf` (PDF),
  `picqer/php-barcode-generator` (Code 128), `bacon/bacon-qr-code` (QR), `smalot/pdfparser` (ler PDF)
- Pasta do projeto: diretório raiz da aplicação

## 2. Como rodar (desenvolvimento)

```
php artisan serve
```
Acesse **http://localhost:8000**. Para acesso na rede local: `php artisan serve --host=0.0.0.0 --port=8000`.

Se editar telas/estilos: `npm run build` (recompila CSS/JS). Para limpar caches: `php artisan optimize:clear`.

**Acesso inicial (Admin):** `admin@almoxarifado.local` / `Almox@2026` — **troque a senha** em Usuários.

## Configurações (Admin)
Pela **engrenagem** no header (só administradores) abre a central de **Configurações**, com:
- **Dados da empresa** — nome, CNPJ, telefone, e-mail e **logo** (upload PNG/JPG até 2 MB).
  - **Logo** substitui o monograma "Ge" no **login** e no **menu lateral** (se houver logo enviada).
  - **Nome, CNPJ e logo** no **cabeçalho dos relatórios em PDF**.
  - Configurações ficam numa tabela `settings` (chave-valor). O upload exige `php artisan storage:link`.
- **Setores** — CRUD dos setores (ativar/inativar; não remove setor com materiais).
- **Unidades** — CRUD das unidades de medida (não remove unidade em uso por materiais).
- **Parâmetros** — ajustes gerais: linhas máximas no PDF (padrão 2.000), período padrão dos
  relatórios em dias (padrão 30), período do gráfico do painel (padrão 14) e quantidade no
  "Top itens" do painel (padrão 8).

## Cabeçalho (header) e conta
O topo do sistema tem um cabeçalho com gradiente da cor da marca e, no canto direito:
- **Sino de notificações** — mostra itens abaixo do mínimo e (para administradores) requisições pendentes.
- **Ajuda (?)** — abre a página de Ajuda com o guia rápido.
- **Menu do usuário** (avatar com as iniciais) — abre **Meu perfil** e **Sair**.

Em **Meu perfil**, cada usuário atualiza o próprio nome/e-mail e troca a senha (exige a senha atual).
O perfil de acesso (Admin/Operador) só é alterado por um administrador na tela de Usuários.

## 3. Perfis de acesso

- **Administrador:** acesso total (materiais, categorias, usuários, tudo).
- **Operador:** vê materiais, registra movimentações, usa relatórios e importação de NF-e;
  **não** cria/edita materiais, categorias nem usuários.

## 4. Funcionalidades

### Painel
KPIs (materiais ativos, abaixo do mínimo, movimentações hoje, **valor em estoque**), 4 gráficos
(movimentações por dia, consumo por setor, materiais por categoria, top itens) e últimas movimentações.

### Materiais
Cadastro com: código interno, código de barras (EAN), nome, descrição, **categoria**,
**setor responsável**, unidade, tipo (Matéria-prima/Consumível), localização, **estoque mínimo**,
saldo atual, **preço unitário** e **preço máximo** (rastreado automaticamente).
- Lista com busca e filtros por categoria, setor, tipo e status — incluindo **"Sem categoria"** e
  **"Sem setor"** para achar itens incompletos.
- **Ordenação por coluna**: clique no cabeçalho (Código, Nome, Setor, Categoria, Tipo, Saldo, Mínimo)
  para ordenar; clique de novo inverte (crescente/decrescente). A seta indica a coluna/ordem ativa.
  Numéricos (saldo/mínimo) ordenam por valor; texto, alfabeticamente. Os filtros são preservados.
- O **saldo** só muda por movimentação (nunca editado direto).
- **Desativar/Reativar**: na tela do material há o botão **Desativar** (e **Reativar**). Use para
  materiais que não podem ser removidos (têm movimentações), como duplicados — ficam ocultos em
  movimentações/requisições, mas o histórico é preservado.
- **Valor em estoque** = saldo × preço unitário.
- Botão **"Imprimir etiqueta"** (código de barras + QR).

#### Localização (endereçamento)
Cada material tem um endereço estruturado: **Corredor**, **Prateleira** e **Posição** (campos
separados), mais um **Complemento** livre opcional (ex.: "galpão dos fundos"). O sistema exibe o
endereço montado (ex.: "Corredor A · Prateleira 3 · Posição 02"); se nenhum campo estruturado
estiver preenchido, mostra o complemento. Materiais antigos (texto livre) continuam funcionando
como complemento até serem reendereçados.

#### Precificação por peso (categorias de kg)
Materiais cujas categorias são marcadas como **"Por peso (kg)"** (ex.: Chapas, Tubos na Metalúrgica)
usam uma lógica diferente de precificação:
- A categoria tem o campo **`is_weight_category`** — ao marcá-la, **todos os itens já vinculados são sincronizados imediatamente** (`priced_by_weight` + `unit_price`).
- O cadastro do material exibe o campo **Preço (R$/kg)** em vez de preço unitário.
- Fórmula: **valor em estoque = saldo (kg) × R$/kg** (sem multiplicação dupla — a unidade já é KG).
- Aviso visual no formulário se a categoria é por peso mas a unidade selecionada não é KG.
- O campo `unit_price` é auto-sincronizado com `price_per_kg` no hook `saving` do model.

#### Limites de estoque (todos os materiais)
- **Estoque mínimo** (`min_stock`): alerta quando o saldo fica igual ou abaixo — linha âmbar na listagem.
- **Estoque máximo** (`max_stock`): alerta quando o saldo ultrapassa o limite — linha laranja na listagem.
  Disponível para **qualquer material** (não só por peso). Deixar em branco = sem limite.
- Validação: o sistema rejeita cadastros onde `max_stock < min_stock`.

#### Saldo inicial no cadastro
Ao **criar** um material, há um campo opcional **"Saldo atual em estoque"**. Se preenchido com valor > 0,
o sistema cria automaticamente um ajuste de inventário (tipo "Ajuste/Inventário") para registrar o
ponto de partida — mantendo o histórico de movimentações completo desde o primeiro dia.

### Categorias / Setores / Usuários (Admin)
- **Categorias:** CRUD; checkbox **"Categoria por peso (kg)"** para habilitar precificação R$/kg;
  remover não apaga materiais (só desvincula).
  - **Categorias por setor (hierarquia):** cada categoria pertence a **um setor** ou é **global**
    (visível a todos). Ao cadastrar/editar um material, o campo Categoria mostra apenas as
    **categorias do setor escolhido + as globais**. Material sem setor vê só as globais.
  - O **nome** é único **por setor** — "Consumíveis" pode existir em vários setores.
  - Mudar o setor de uma categoria **não altera** os materiais já cadastrados (eles mantêm a categoria atual).
- **Setores:** Almoxarifado, Metalúrgica, Marcenaria, Expedição (pré-cadastrados).
- **Usuários:** CRUD com perfil; não é possível remover o próprio usuário.

### Movimentações
Um documento de movimentação tem **vários itens** (ex.: um pedido da Marcenaria com vários parafusos).
- **Entrada:** aumenta o saldo.
- **Saída:** reduz o saldo; exige **setor de destino**; bloqueia se faltar saldo.
- **Ajuste/Inventário:** informa a quantidade contada; o sistema ajusta o saldo e registra a diferença.
- Saldo atualizado em transação (seguro com vários operadores).
- **Leitura por câmera** (botão "Ler com câmera") preenche os itens pelo código de barras/QR
  (funciona em `localhost`; na rede exige HTTPS).
- Histórico com filtros (tipo, setor, período) e export (Excel e PDF).
  - O **PDF** é limitado a **2.000 linhas** (o dompdf é intensivo em memória). Acima disso o PDF é
    truncado com aviso — use o **Excel** (sem limite) ou refine os filtros para o relatório completo.

### Relatórios (com export Excel e PDF)
- **Saldo atual** (com preço, valor e total), **Itens abaixo do mínimo**, **Entradas por período**,
  **Consumo por setor**.
- Filtros por **setor**, **categoria** e (entradas/consumo) **período**.
- **Consumo por setor**: saídas agrupadas pelo **setor de destino** (centro de custo), com
  quantidade e valor consumido por material, subtotal por setor e total geral. Valor estimado
  pelo preço unitário atual do material.

### Ficha Kardex (histórico por material)
Na tela de um material, botão **"Histórico (Kardex)"** abre o histórico completo daquele item:
toda entrada/saída/ajuste com data, tipo, setor, quantidade (+/−), **saldo acumulado após cada
movimento**, usuário e observação. Filtro por período e exportação em Excel/PDF.

### Requisição de material (com aprovação)
Menu **Requisições**. Fluxo:
1. Qualquer usuário cria uma requisição: escolhe o **setor solicitante** (centro de custo),
   adiciona os itens e quantidades, e envia. Status **Pendente**.
2. Um **administrador** revisa e:
   - **Aprova e dá baixa** → o sistema gera uma **saída** (destino = setor solicitante) e baixa o
     estoque. Status **Atendida**. Se faltar saldo de algum item, a aprovação é bloqueada.
   - **Recusa** (com motivo opcional). Status **Recusada**.
3. O **solicitante** (ou um admin) pode **Cancelar** enquanto estiver pendente.

Um **administrador** pode **Editar** uma requisição enquanto ela está **pendente** (setor, itens,
quantidades e justificativa). Requisições já atendidas/recusadas/canceladas não são editáveis.

Botão **"Imprimir"** na requisição gera um documento imprimível (cabeçalho com nome/logo da empresa,
itens, justificativa e linhas de assinatura) — funciona em **qualquer status, inclusive pendente**.

A baixa gerada aparece nas Movimentações e na Ficha Kardex, e alimenta o relatório de Consumo por setor.

### Inventário rotativo guiado
Menu **Inventário**. Fluxo de contagem cíclica:
1. **Novo inventário** → escolha o escopo (setor e/ou categoria). O sistema cria a sessão e
   tira um *snapshot* do saldo atual de cada material ativo do escopo.
2. **Contagem** → lance a quantidade contada de cada item; a divergência aparece na hora.
   Pode **Salvar** o progresso e voltar depois.
3. **Finalizar** → o sistema aplica um único **Ajuste** apenas nos itens com divergência
   (saldo passa a ser o contado) e encerra a sessão. Itens não contados ficam intactos.

Há também o botão **Cancelar inventário**, que descarta a contagem e encerra a sessão
**sem alterar nenhum saldo**.

Os ajustes entram como movimentação "Ajuste/Inventário" (aparecem na Ficha Kardex de cada item).
Disponível para administradores e operadores.

### Etiquetas
Gera **Code 128 + QR** a partir do código interno do material. Tela **Etiquetas** filtra por
setor/categoria/busca e imprime uma **folha A4** com várias etiquetas; ou imprime uma etiqueta pelo material.

### Importar NF-e (XML ou PDF)
Em **Movimentações → Importar NF-e**, envie o **XML** (exato) ou o **PDF/DANFE** (aproximado).
Na conferência, cada produto da nota vira um **material novo** (ou reaproveita pelo EAN), e você define
por linha: **Categoria, Setor, Localização, Código de barras, Quantidade e Preço**. Confirmar gera a entrada.
> O XML é a fonte confiável; o PDF é plano B (extração de itens aproximada, exige revisão).

## 5. Comandos Artisan

| Comando | O que faz |
|---|---|
| `php artisan almoxarifado:importar "caminho\arquivo.xlsx"` | Importa as abas (Almoxarifado/Metalúrgica/Marcenaria/Expedição) da planilha — cria/atualiza materiais, categorias, unidades, setor, saldo, mínimo e preço |
| `php artisan almoxarifado:saldo-inicial` | Cria movimento de "saldo inicial" para itens com saldo e sem movimentações |
| `php artisan almoxarifado:recalcular-saldo` | Recalcula o saldo de cada material a partir do histórico |
| `php artisan almoxarifado:categorizar-setores` | Atribui categorias por palavra-chave aos itens de setor sem categoria |
| `php artisan almoxarifado:backup` | Copia o banco para o destino de backup (com retenção e cópia secundária opcional) — ver `config/backup.php` e `backup.bat` |
| `php artisan test` | Roda a suíte de testes |

## 6. Dados atuais

- **492 materiais**: Almoxarifado 341, Marcenaria 98, Metalúrgica 44, Expedição 9 — todos com setor e categoria.
- Saldos iniciais importados da planilha; **167 itens com preço** (valor em estoque ≈ R$ 231 mil).

## 7. Testes

**202 testes** automatizados (`php artisan test`) cobrindo autenticação, permissões, materiais,
categorias, usuários, movimentações, relatórios/export, importação (planilha, NF-e XML/PDF),
preços, categorização, backup, filtros por período, índices de performance e **precificação por peso**
(`PrecoKgTest` — 20 testes: categoria por peso, auto-sync, fórmula sem dupla contagem, max_stock/alertas,
HTTP create/edit/show). Política: **testes primeiro** (TDD) — escreva o teste antes da mudança e rode a suíte;
o nome do teste indica o que quebrou.

## 8. Rodar em outro PC (resumo)

Copie a pasta inteira (já leva `vendor/`, `public/build/` e o banco), instale **PHP 8.3+** (ou Laragon)
com as extensões `pdo_sqlite, gd, zip, mbstring, fileinfo, openssl, dom`, e rode `php artisan serve`.

## 9. Implantação definitiva (Fase 6 — código pronto; execução no servidor pendente)

Para virar o servidor fixo da empresa: rodar via **Laragon** na rede, **HTTPS** (necessário para a
câmera nos celulares), **backup automático** do `database/database.sqlite` e iniciar com o Windows.

O **código e o guia já estão entregues** — falta apenas executar no servidor:
- Comando **`almoxarifado:backup`** + `config/backup.php` (variáveis `BACKUP_DIR`, `BACKUP_DIR_SECONDARY`,
  `BACKUP_KEEP`, `BACKUP_SOURCE`): cópia local + secundária + retenção.
- Agendamento diário (20:00) em `routes/console.php` e **`backup.bat`** para o Agendador de Tarefas do Windows.
- Guia passo a passo **`IMPLANTACAO.md`** (Laragon, HTTPS via **mkcert**, firewall, autostart, backup).

## 10. Decisões de escopo

- Identificação por código interno + etiqueta de barras/QR; leitura por câmera (exige HTTPS na rede).
- Escopo: matéria-prima + consumíveis/ferramentas. **Sem fornecedores.** Custo/valor: **ativado**
  (preço unitário, preço máximo, valor em estoque).
- SQLite (3–4 usuários simultâneos); migração para MySQL é simples se crescer.
- Interface em **pt-BR**.
