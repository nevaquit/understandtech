# Bootstrap local Moodle: clone core (if missing), start Docker stack, install, sync plugins.
param(
    [switch]$SkipClone,
    [switch]$SkipInstall
)

$ErrorActionPreference = "Stop"
$Root = Resolve-Path (Join-Path $PSScriptRoot "..")
Set-Location $Root

$EnvFile = Join-Path $Root "docker\.env.local"
$EnvExample = Join-Path $Root "docker\.env.example"
$MoodleDir = Join-Path $Root "moodle"
$MoodleData = Join-Path $Root "moodledata"

function Get-DockerExe {
    $cmd = Get-Command docker -ErrorAction SilentlyContinue
    if ($cmd) { return $cmd.Source }
    $default = "C:\Program Files\Docker\Docker\resources\bin\docker.exe"
    if (Test-Path $default) { return $default }
    throw "Docker not found. Start Docker Desktop and ensure docker is on PATH."
}

function Invoke-DockerCompose {
    param([string[]]$ComposeArgs)
    $docker = Get-DockerExe
    $allArgs = @("compose", "--env-file", $EnvFile) + $ComposeArgs
    & $docker @allArgs
    if ($LASTEXITCODE -ne 0) { throw "docker compose failed: $($ComposeArgs -join ' ')" }
}

# Ensure env file exists.
if (-not (Test-Path $EnvFile)) {
    Copy-Item $EnvExample $EnvFile
    Write-Host "Created $EnvFile from example."
}

# Load env for install credentials.
Get-Content $EnvFile | ForEach-Object {
    if ($_ -match '^\s*#' -or $_ -notmatch '=') { return }
    $parts = $_ -split '=', 2
    Set-Item -Path "env:$($parts[0].Trim())" -Value $parts[1].Trim()
}

# Clone Moodle 4.5 LTS if needed.
if (-not $SkipClone -and -not (Test-Path (Join-Path $MoodleDir "index.php"))) {
    Write-Host "Cloning Moodle MOODLE_405_STABLE into ./moodle ..."
    git clone --depth 1 -b MOODLE_405_STABLE https://github.com/moodle/moodle.git $MoodleDir
}

if (-not (Test-Path (Join-Path $MoodleDir "index.php"))) {
    throw "Moodle core missing at $MoodleDir"
}

if (-not (Test-Path $MoodleData)) {
    New-Item -ItemType Directory -Path $MoodleData | Out-Null
}

Write-Host "Starting Docker stack ..."
Invoke-DockerCompose @("up", "-d", "--build")

Write-Host "Waiting for Postgres ..."
$docker = Get-DockerExe
for ($i = 0; $i -lt 30; $i++) {
    & $docker compose --env-file $EnvFile exec -T postgres pg_isready -U $env:POSTGRES_USER -d $env:POSTGRES_DB 2>$null
    if ($LASTEXITCODE -eq 0) { break }
    Start-Sleep -Seconds 2
}

# Copy local config before install.
Copy-Item (Join-Path $Root "docker\moodle\config.php.local") (Join-Path $MoodleDir "config.php") -Force

if (-not $SkipInstall) {
    $installed = & $docker compose --env-file $EnvFile exec -T moodle php admin/cli/install_database.php --help 2>$null
    $needsInstall = $true
    try {
        $check = & $docker compose --env-file $EnvFile exec -T moodle php -r "define('CLI_SCRIPT', true); require '/var/www/moodle/config.php'; echo \$DB->get_manager()->table_exists('user') ? 'yes' : 'no';" 2>$null
        if ($check -match 'yes') { $needsInstall = $false }
    } catch {
        $needsInstall = $true
    }

    if ($needsInstall) {
        Write-Host "Running Moodle CLI install ..."
        & $docker compose --env-file $EnvFile exec -T moodle php admin/cli/install.php `
            --lang=en `
            --wwwroot="$($env:MOODLE_WWWROOT)" `
            --dataroot=/var/moodledata `
            --dbtype=pgsql `
            --dbhost=postgres `
            --dbname="$($env:POSTGRES_DB)" `
            --dbuser="$($env:POSTGRES_USER)" `
            --dbpass="$($env:POSTGRES_PASSWORD)" `
            --fullname="UnderstandTech Local" `
            --shortname="UT Local" `
            --adminuser="$($env:MOODLE_ADMIN_USER)" `
            --adminpass="$($env:MOODLE_ADMIN_PASSWORD)" `
            --adminemail="$($env:MOODLE_ADMIN_EMAIL)" `
            --agree-license `
            --non-interactive
        if ($LASTEXITCODE -ne 0) { throw "Moodle install failed" }
    } else {
        Write-Host "Moodle already installed; skipping install.php"
    }
}

Write-Host "Syncing custom plugins ..."
& (Join-Path $PSScriptRoot "sync-plugins-local.ps1")

Write-Host "Upgrading plugins ..."
Invoke-DockerCompose @("exec", "-T", "moodle", "php", "admin/cli/upgrade.php", "--non-interactive")

Write-Host "Activating theme_understandtech ..."
Invoke-DockerCompose @("exec", "-T", "moodle", "php", "admin/cli/cfg.php", "--name=theme", "--set=understandtech")

Write-Host "Purging caches ..."
Invoke-DockerCompose @("exec", "-T", "moodle", "php", "admin/cli/purge_caches.php")

Write-Host ""
Write-Host "Local Moodle ready at $($env:MOODLE_WWWROOT)"
Write-Host "Login: $($env:MOODLE_ADMIN_USER) / (password from docker/.env.local)"
