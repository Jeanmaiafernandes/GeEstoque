@echo off
REM Backup do banco do Almoxarifado. Agende no Agendador de Tarefas do Windows (diario).
REM Se o "php" nao estiver no PATH, troque por: C:\tools\php85\php.exe
cd /d "%~dp0"
php artisan almoxarifado:backup
