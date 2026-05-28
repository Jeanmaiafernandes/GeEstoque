# Implantação — Servidor fixo (Laragon + HTTPS + Backup)

Guia para colocar o Almoxarifado como **servidor da empresa**: acessível na rede local,
com **HTTPS** (para a câmera), **backup automático** e **iniciar sozinho** ao ligar o PC.

> Os comandos `php artisan ...` rodam **dentro da pasta do projeto** (`C:\laragon\www\almoxarifado`, após o passo 2).

---

## 0. Antes de começar
- Escolha o **PC servidor** (fica ligado no horário de trabalho).
- Defina um **IP fixo** para ele (no Windows ou reservando por MAC no roteador). Anote, ex.: `192.168.0.10`.

## 1. Instalar o Laragon
1. Baixe o **Laragon Full** em https://laragon.org e instale.
2. Confirme o **PHP 8.3+** (Menu → PHP → Version). Garanta as extensões: `pdo_sqlite, gd, zip, mbstring, fileinfo, openssl, dom` (Laragon já costuma trazer).

## 2. Colocar o projeto
1. Copie a pasta do sistema para **`C:\laragon\www\almoxarifado`** (leve junto `vendor/`, `public/build/` e `database/database.sqlite` — assim vão os dados).
2. O Laragon cria o site automático **`http://almoxarifado.test`** (apontando para a pasta `public`).
   - Menu → **Recarregar** (Reload) para registrar o site.

## 3. Ajustes de produção (.env)
Edite o arquivo `.env`:
```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://almoxarifado.test
BACKUP_DIR_SECONDARY=E:\backups        # pasta de rede/pendrive (ex.: \\SERVIDOR\backups)
BACKUP_KEEP=14
```
Depois rode (na pasta do projeto):
```
php artisan optimize:clear
php artisan migrate --force
php artisan optimize          # cacheia config, rotas e views (mais rápido)
npm run build                 # só se ainda não tiver o public/build
```
> Sempre que **editar o .env** depois, rode de novo `php artisan optimize:clear` e `php artisan optimize`.

## 4. HTTPS com mkcert (para a câmera nos celulares)
A câmera do navegador só funciona em **HTTPS**. Vamos gerar um certificado **confiável na rede**.
1. Instale o **mkcert** (no PC servidor): com Chocolatey `choco install mkcert`, ou baixe o `mkcert.exe`.
2. Crie a Autoridade local e o certificado (use o IP do servidor):
   ```
   mkcert -install
   mkcert almoxarifado.test 192.168.0.10 localhost
   ```
   Isso gera dois arquivos `.pem` (certificado e chave).
3. No Laragon: **Menu → Apache → SSL** (ativa HTTPS). Depois aponte o certificado: edite o vhost do site
   (Menu → Apache → sites-enabled → `almoxarifado.test.conf`) trocando os caminhos `SSLCertificateFile` /
   `SSLCertificateKeyFile` pelos arquivos do mkcert. Reinicie o Apache.
4. **Nos celulares/tablets:** copie o arquivo `rootCA.pem` (pasta mostrada por `mkcert -CAROOT`) para o
   aparelho e instale como **certificado confiável** (Android: Ajustes → Segurança → Instalar certificado;
   iPhone: instale o perfil e ative em Ajustes → Geral → Sobre → Confiança em certificados).
5. Os celulares acessam por **`https://192.168.0.10`** (o nome `almoxarifado.test` só resolve no servidor).

## 5. Liberar no Firewall
No PC servidor, abra o **Firewall do Windows → Regras de Entrada → Nova Regra → Porta** e libere a **443** (e **80**) TCP.

## 6. Acesso pela equipe
- **Outros computadores e celulares:** `https://192.168.0.10` (o IP do servidor).
- **No próprio servidor:** `https://almoxarifado.test`.
- Login inicial: `admin@almoxarifado.local` / `Almox@2026` — **troque a senha** e cadastre os usuários (perfil Operador).

## 7. Iniciar sozinho ao ligar o PC
No Laragon: **Menu → Preferências (Preferences)** → marque **"Run Laragon when Windows starts"** e
**"Auto start"** (inicia o Apache automaticamente). Assim o sistema sobe sozinho no boot.

## 8. Backup automático
O backup já está pronto (comando `php artisan almoxarifado:backup`): copia o banco para `storage/app/backups`
e para a pasta secundária do `.env` (rede/pendrive), mantendo os últimos `BACKUP_KEEP` (14) backups.
1. Teste manualmente: `php artisan almoxarifado:backup` (confira os arquivos nas pastas).
2. Agende no **Agendador de Tarefas do Windows**:
   - Criar Tarefa Básica → **Diariamente** (ex.: 20:00).
   - Ação: **Iniciar um programa** → Programa: `C:\laragon\www\almoxarifado\backup.bat`.
   - Marque "Executar estando o usuário conectado ou não".
3. (Opcional) Se o `php` não estiver no PATH, edite o `backup.bat` e troque `php` pelo caminho completo (ex.: `C:\laragon\bin\php\php-8.x\php.exe`).

**Restaurar um backup:** pare o uso, copie um arquivo `almoxarifado-AAAA-MM-DD_HHMMSS.sqlite` por cima de
`database/database.sqlite` (renomeando para `database.sqlite`).

## 9. Atualizar o sistema no futuro
Substitua os arquivos do projeto e rode:
```
composer install --no-dev
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
```

## 10. Checklist final
- [ ] Laragon instalado, PHP 8.3+ com extensões.
- [ ] Projeto em `C:\laragon\www\almoxarifado` (com o banco).
- [ ] `.env` de produção + `php artisan optimize`.
- [ ] HTTPS (mkcert) e CA instalado nos celulares.
- [ ] Firewall liberado (443/80).
- [ ] Laragon iniciando com o Windows.
- [ ] Tarefa de backup agendada e testada.
- [ ] Senha do admin trocada e usuários criados.
