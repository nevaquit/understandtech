# SCP Cloudflare origin certs and production nginx config to the VM, then run
# install-cloudflare-origin-certs.sh remotely. Does not print secret material.
#
# Usage:
#   .\scripts\deploy-cloudflare-origin-certs.ps1
#   .\scripts\deploy-cloudflare-origin-certs.ps1 -OriginPem C:\path\origin.pem -OriginKey C:\path\origin.key
#
# Default cert paths: infrastructure/ssl/cloudflare/origin.pem and origin.key
# (created by create-cloudflare-origin-cert.ps1 or saved from Cloudflare dashboard)

param(
    [string]$VmHost = 'azureadmin@52.252.59.54',
    [string]$OriginPem = '',
    [string]$OriginKey = '',
    [string]$RepoRoot = ''
)

$ErrorActionPreference = 'Stop'
$env:Path = [System.Environment]::GetEnvironmentVariable('Path', 'Machine') + ';' + [System.Environment]::GetEnvironmentVariable('Path', 'User')

if ([string]::IsNullOrWhiteSpace($RepoRoot)) {
    $RepoRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
}

$sslDir = [System.IO.Path]::Combine($RepoRoot, 'infrastructure', 'ssl', 'cloudflare')
if ([string]::IsNullOrWhiteSpace($OriginPem)) {
    $OriginPem = Join-Path $sslDir 'origin.pem'
}
if ([string]::IsNullOrWhiteSpace($OriginKey)) {
    $OriginKey = Join-Path $sslDir 'origin.key'
}

foreach ($f in @($OriginPem, $OriginKey)) {
    if (-not (Test-Path $f)) {
        Write-Host @"
Missing cert file: $f

Create certs first:
  .\scripts\create-cloudflare-origin-cert.ps1
  # or save dashboard Origin Certificate / Private Key to infrastructure/ssl/cloudflare/
"@
        exit 1
    }
}

$nginxConf = [System.IO.Path]::Combine($RepoRoot, 'infrastructure', 'nginx', 'understandtech.conf')
$rateLimit = [System.IO.Path]::Combine($RepoRoot, 'infrastructure', 'nginx', 'understandtech-rate-limit.conf')
$installScript = [System.IO.Path]::Combine($RepoRoot, 'scripts', 'install-cloudflare-origin-certs.sh')
$sshKey = [System.IO.Path]::Combine($env:USERPROFILE, '.ssh', 'id_ed25519')

& scp -i $sshKey -o BatchMode=yes `
    $OriginPem, $OriginKey, $nginxConf, $rateLimit, $installScript `
    "${VmHost}:/tmp/"
if ($LASTEXITCODE -ne 0) { throw 'SCP failed' }

$remote = @'
chmod +x /tmp/install-cloudflare-origin-certs.sh
sudo /tmp/install-cloudflare-origin-certs.sh \
  --origin-pem /tmp/origin.pem \
  --origin-key /tmp/origin.key \
  --nginx-conf /tmp/understandtech.conf \
  --rate-limit-conf /tmp/understandtech-rate-limit.conf
rm -f /tmp/origin.pem /tmp/origin.key
'@

ssh -i $sshKey -o BatchMode=yes $VmHost $remote
if ($LASTEXITCODE -ne 0) { throw 'Remote install failed' }

Write-Host 'Cloudflare origin certs deployed; nginx switched to production HTTPS config.'
