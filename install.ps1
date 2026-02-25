# ===============================
# CONFIGURACIÓN
# ===============================
$InstallPath = "C:\Dashboard-RMService"
$phpUrl      = "https://windows.php.net/downloads/releases/php-8.5.1-Win32-vs17-x64.zip"
$apacheUrl   = "https://www.apachelounge.com/download/VS18/binaries/httpd-2.4.66-260131-Win64-VS18.zip"
$phpZip      = "$env:TEMP\php.zip"
$apacheZip   = "$env:TEMP\apache.zip"

$ErrorActionPreference = "Stop"

# VALIDAR ADMIN
if (-not ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    Write-Host "ERROR: Ejecuta como Administrador." -ForegroundColor Red
    exit
}

Write-Host "Iniciando instalacion..." -ForegroundColor Cyan
$serviceExists = sc.exe query "Apache2.4" 2>$null

if ($serviceExists -match "SERVICE_NAME" -OR $serviceExists -match "NOMBRE_SERVICIO") {
    Write-Host "Servicio existente detectado. Deteniendo servicio..." -ForegroundColor Yellow
    sc.exe stop "Apache2.4" 2>$null
}

# CREAR DIRECTORIO
if (-not (Test-Path $InstallPath)) { New-Item -ItemType Directory -Force -Path $InstallPath | Out-Null }

# DESCARGAR Y EXTRAER
$ua = "Mozilla/5.0 (Windows NT 10.0; Win64; x64)"
Write-Host "Descargando componentes..."
Invoke-WebRequest -Uri $phpUrl -OutFile $phpZip -UserAgent $ua
Expand-Archive $phpZip -DestinationPath "$InstallPath\php" -Force
Invoke-WebRequest -Uri $apacheUrl -OutFile $apacheZip -UserAgent $ua
Expand-Archive $apacheZip -DestinationPath $InstallPath -Force

# Renombrar Apache
$extApache = Get-ChildItem $InstallPath | Where-Object { $_.PSIsContainer -and ($_.Name -like "httpd*" -or $_.Name -eq "Apache24") }
if ($extApache) {
    if (Test-Path "$InstallPath\apache") { Remove-Item "$InstallPath\apache" -Recurse -Force }
    Rename-Item $extApache.FullName "$InstallPath\apache"
}

# CONFIGURAR HTTPD.CONF
$conf = "$InstallPath\apache\conf\httpd.conf"
$htdocs = "$InstallPath\htdocs"
if (-not (Test-Path $htdocs)) { New-Item -ItemType Directory -Path $htdocs | Out-Null }

(Get-Content $conf) | ForEach-Object {
    $_ -replace 'Define SRVROOT ".*"', "Define SRVROOT `"$InstallPath\apache`"" `
       -replace 'DocumentRoot ".*"', "DocumentRoot `"$htdocs`"" `
       -replace '<Directory ".*htdocs.*">', "<Directory `"$htdocs`">" `
       -replace 'DirectoryIndex index.html', "DirectoryIndex index.php index.html"
} | Set-Content $conf

# COPIAR PROYECTO
Write-Host "Copiando archivos..." -ForegroundColor Yellow
Get-ChildItem -Path (Get-Location) -Exclude "installS.ps1", "*.zip", ".git*" | ForEach-Object {
    Copy-Item -Path $_.FullName -Destination $htdocs -Recurse -Force
}
# Agregar PHP al Path del sistema para que Apache encuentre las DLLs de las extensiones
$currentPath = [Environment]::GetEnvironmentVariable("Path", "Machine")
if ($currentPath -notlike "*$InstallPath\php*") {
    [Environment]::SetEnvironmentVariable("Path", $currentPath + ";$InstallPath\php", "Machine")
    $env:Path += ";$InstallPath\php" # Actualizar sesión actual
}
# VINCULAR PHP (Sintaxis ultra-segura)
$phpDll = "$InstallPath\php\php8apache2_4.dll"
$phpDir = "$InstallPath\php"
$content = Get-Content $conf -Raw

if ($content -notmatch "ServerName localhost") {
    Add-Content $conf "`nServerName localhost"
}
Add-Content -Path $conf -Value "`nLoadModule php_module `"$phpDll`""
Add-Content -Path $conf -Value "AddHandler application/x-httpd-php .php"
Add-Content -Path $conf -Value "PHPIniDir `"$phpDir`""


if (Test-Path "$phpDir\php.ini-development") {
    Copy-Item "$phpDir\php.ini-development" "$phpDir\php.ini" -Force
}

# INSTALAR SERVICIO
Write-Host "Registrando Servicio..." -ForegroundColor Cyan
Set-Location "$InstallPath\apache\bin"
$serviceExists = sc.exe query "Apache2.4" 2>$null

if ($serviceExists -match "SERVICE_NAME" -OR $serviceExists -match "NOMBRE_SERVICIO" -OR $serviceExists -match "NOMBRE_DE_SERVICIO") {

    Write-Host "Desinstalando servicio existente..." -ForegroundColor Yellow

    sc.exe stop "Apache2.4" 2>$null
    & .\httpd.exe -k uninstall -n "Apache2.4"
}
& .\httpd.exe -k install -n "Apache2.4"


# ===============================
# ACTIVAR EXTENSIONES PHP
# ===============================
Write-Host "Configurando extensiones de PHP (cURL)..." -ForegroundColor Yellow
$phpIni = "$InstallPath\php\php.ini"

# Habilitar el directorio de extensiones
(Get-Content $phpIni) -replace ';extension_dir = "ext"', "extension_dir = `"$InstallPath\php\ext`"" | Set-Content $phpIni

# Habilitar cURL y otras comunes (mbstring, openssl para HTTPS)
(Get-Content $phpIni) -replace ';extension=curl', "extension=curl" `
                      -replace ';extension=mbstring', "extension=mbstring" `
                      -replace ';extension=openssl', "extension=openssl" | Set-Content $phpIni

# Reiniciar para aplicar
Start-Service "Apache2.4"

Write-Host "INSTALACION OK" -ForegroundColor Green
Write-Host "URL: http://localhost" -ForegroundColor White