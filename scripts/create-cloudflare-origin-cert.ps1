# Create a Cloudflare Origin CA certificate for understandtech.app via API.
# Saves PEM files locally (gitignored *.pem/*.key) for SCP to the VM.
# Does NOT print certificate or private key contents.
#
# Prerequisites:
#   CLOUDFLARE_API_TOKEN or CF_API_TOKEN with Zone:SSL and Certificates:Edit
#   (or Account:Cloudflare Origin CA:Edit for origin certs)
#
# Usage:
#   $env:CLOUDFLARE_API_TOKEN = '...'
#   .\scripts\create-cloudflare-origin-cert.ps1
#   .\scripts\deploy-cloudflare-origin-certs.ps1
#
# Dashboard alternative (no API token):
#   Cloudflare -> understandtech.app -> SSL/TLS -> Origin Server
#   -> Create Certificate -> RSA, hostnames: understandtech.app, *.understandtech.app
#   -> validity 15 years -> Create -> save Origin Certificate and Private Key to files
#   -> run deploy-cloudflare-origin-certs.ps1 with -OriginPem / -OriginKey paths

param(
    [string[]]$Hostnames = @('understandtech.app', '*.understandtech.app'),
    [int]$ValidityDays = 5475,
    [string]$RequestType = 'origin-rsa',
    [string]$OutDir = '',
    [string]$ApiToken = ''
)

$ErrorActionPreference = 'Stop'

if ([string]::IsNullOrWhiteSpace($ApiToken)) {
    $ApiToken = [Environment]::GetEnvironmentVariable('CLOUDFLARE_API_TOKEN')
    if ([string]::IsNullOrWhiteSpace($ApiToken)) {
        $ApiToken = [Environment]::GetEnvironmentVariable('CF_API_TOKEN')
    }
}

if ([string]::IsNullOrWhiteSpace($ApiToken)) {
    Write-Host @'
No Cloudflare API token found.

Set one of:
  $env:CLOUDFLARE_API_TOKEN = '<token>'
  $env:CF_API_TOKEN = '<token>'

Or create the origin cert in the Cloudflare dashboard:
  1. Log in to Cloudflare -> zone understandtech.app
  2. SSL/TLS -> Origin Server -> Create Certificate
  3. Let Cloudflare generate a private key and CSR (RSA, 2048)
  4. Hostnames: understandtech.app, *.understandtech.app
  5. Certificate Validity: 15 years
  6. Create -> copy Origin Certificate to origin.pem and Private Key to origin.key
  7. Run: .\scripts\deploy-cloudflare-origin-certs.ps1 -OriginPem <path> -OriginKey <path>

Also enable Authenticated Origin Pulls:
  SSL/TLS -> Origin Server -> Authenticated Origin Pulls -> ON

Stream signing key (for cf-stream-signing-key in Key Vault):
  Stream -> Settings -> Signing Keys -> Create signing key
  Copy the key id and PEM/JWK material per Cloudflare docs; store PEM body in KV as cf-stream-signing-key.
'@
    exit 1
}

if ([string]::IsNullOrWhiteSpace($OutDir)) {
    $OutDir = [System.IO.Path]::Combine($PSScriptRoot, '..', 'infrastructure', 'ssl', 'cloudflare')
}
$OutDir = [System.IO.Path]::GetFullPath($OutDir)
New-Item -ItemType Directory -Force -Path $OutDir | Out-Null

$body = @{
    hostnames          = $Hostnames
    requested_validity = $ValidityDays
    request_type       = $RequestType
} | ConvertTo-Json

$headers = @{
    Authorization = "Bearer $ApiToken"
    'Content-Type' = 'application/json'
}

Write-Host "Requesting origin certificate for: $($Hostnames -join ', ')"
$response = Invoke-RestMethod -Method Post `
    -Uri 'https://api.cloudflare.com/client/v4/certificates' `
    -Headers $headers `
    -Body $body

if (-not $response.success) {
    $err = ($response.errors | ConvertTo-Json -Compress)
    throw "Cloudflare API error: $err"
}

$cert = $response.result.certificate
$key = $response.result.private_key
if ([string]::IsNullOrWhiteSpace($cert) -or [string]::IsNullOrWhiteSpace($key)) {
    throw 'API response missing certificate or private_key'
}

$pemPath = Join-Path $OutDir 'origin.pem'
$keyPath = Join-Path $OutDir 'origin.key'
[System.IO.File]::WriteAllText($pemPath, $cert.Trim() + "`n")
[System.IO.File]::WriteAllText($keyPath, $key.Trim() + "`n")

Write-Host "Origin cert written to: $pemPath"
Write-Host "Origin key written to:  $keyPath"
Write-Host 'Next: .\scripts\deploy-cloudflare-origin-certs.ps1'
