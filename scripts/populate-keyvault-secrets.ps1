# Populate understandtech Key Vault secrets that still hold REPLACE-ME placeholders.
# Reads from environment variables or secure interactive prompts. Never echoes secret values.
#
# Required secrets (env var -> Key Vault name):
#   ANTHROPIC_API_KEY          -> anthropic-api-key
#   OPENAI_API_KEY             -> openai-api-key
#   CF_STREAM_SIGNING_KEY      -> cf-stream-signing-key
#   AITUTOR_WORKER_SHARED_SECRET or CF_WORKER_SHARED_SECRET -> cf-worker-shared-secret
#   STRIPE_SECRET_KEY            -> stripe-secret-key
#   STRIPE_PUBLISHABLE_KEY       -> stripe-publishable-key
#   STRIPE_WEBHOOK_SECRET        -> stripe-webhook-secret
#
# Prerequisites:
#   az login
#   Key Vault Secrets Officer on utkvnhhwegpz3rem6
#
# Usage:
#   $env:ANTHROPIC_API_KEY = '...'   # set secrets in session, then:
#   .\scripts\populate-keyvault-secrets.ps1
#   # Or run without env vars to be prompted securely for each missing value.

param(
    [string]$KeyVault = 'utkvnhhwegpz3rem6',
    [switch]$GenerateWorkerSecret
)

$ErrorActionPreference = 'Stop'
$env:Path = [System.Environment]::GetEnvironmentVariable('Path', 'Machine') + ';' + [System.Environment]::GetEnvironmentVariable('Path', 'User')

$secretMap = [ordered]@{
    'anthropic-api-key'       = @('ANTHROPIC_API_KEY')
    'openai-api-key'          = @('OPENAI_API_KEY')
    'cf-stream-signing-key'   = @('CF_STREAM_SIGNING_KEY', 'CLOUDFLARE_STREAM_SIGNING_KEY')
    'cf-worker-shared-secret' = @('AITUTOR_WORKER_SHARED_SECRET', 'CF_WORKER_SHARED_SECRET')
    'stripe-secret-key'       = @('STRIPE_SECRET_KEY')
    'stripe-publishable-key'  = @('STRIPE_PUBLISHABLE_KEY')
    'stripe-webhook-secret'   = @('STRIPE_WEBHOOK_SECRET')
}

function Get-SecretFromEnv {
    param([string[]]$EnvNames)
    foreach ($name in $EnvNames) {
        $val = [Environment]::GetEnvironmentVariable($name)
        if (-not [string]::IsNullOrWhiteSpace($val)) {
            return $val
        }
    }
    return $null
}

function Read-SecretSecure {
    param([string]$Prompt)
    $secure = Read-Host -Prompt $Prompt -AsSecureString
    $bstr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure)
    try {
        return [Runtime.InteropServices.Marshal]::PtrToStringAuto($bstr)
    }
    finally {
        [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($bstr)
    }
}

function New-RandomWorkerSecret {
    $bytes = New-Object byte[] 32
    [System.Security.Cryptography.RandomNumberGenerator]::Create().GetBytes($bytes)
    return [Convert]::ToBase64String($bytes)
}

Write-Host "Key Vault: $KeyVault"
$null = az account show -o none 2>$null
if ($LASTEXITCODE -ne 0) {
    throw 'Azure CLI not logged in. Run: az login'
}

$updated = @()
$skipped = @()

foreach ($kvName in $secretMap.Keys) {
    $current = az keyvault secret show --vault-name $KeyVault --name $kvName --query value -o tsv 2>$null
    if ($LASTEXITCODE -ne 0) {
        Write-Host "[init] $kvName not in Key Vault — creating REPLACE-ME placeholder"
        az keyvault secret set --vault-name $KeyVault --name $kvName --value 'REPLACE-ME' -o none | Out-Null
        if ($LASTEXITCODE -ne 0) {
            throw "Cannot create secret '$kvName' in Key Vault (check RBAC)."
        }
        $current = 'REPLACE-ME'
    }

    if ($current -ne 'REPLACE-ME' -and -not [string]::IsNullOrWhiteSpace($current)) {
        Write-Host "[skip] $kvName already configured"
        $skipped += $kvName
        continue
    }

    $value = Get-SecretFromEnv -EnvNames $secretMap[$kvName]

    if ([string]::IsNullOrWhiteSpace($value) -and $kvName -eq 'cf-worker-shared-secret' -and $GenerateWorkerSecret) {
        $value = New-RandomWorkerSecret
        Write-Host "[gen]  $kvName generated random 32-byte base64 value (store same value in Cloudflare Worker secrets)"
    }

    if ([string]::IsNullOrWhiteSpace($value)) {
        if ($kvName -like 'stripe-*') {
            Write-Host "[pend] $kvName still REPLACE-ME — set $($secretMap[$kvName] -join ' or ') when Stripe is ready"
            continue
        }
        $envHint = ($secretMap[$kvName] -join ' or ')
        $value = Read-SecretSecure -Prompt "Enter value for $kvName (env: $envHint)"
    }

    if ([string]::IsNullOrWhiteSpace($value) -or $value -eq 'REPLACE-ME') {
        throw "No valid value provided for $kvName"
    }

    az keyvault secret set --vault-name $KeyVault --name $kvName --value $value -o none | Out-Null
    if ($LASTEXITCODE -ne 0) {
        throw "Failed to set $kvName"
    }
    Write-Host "[set]  $kvName updated"
    $updated += $kvName
}

Write-Host ''
Write-Host 'Validation (required):'
$allOk = $true
$required = @('anthropic-api-key', 'openai-api-key', 'cf-stream-signing-key', 'cf-worker-shared-secret')
foreach ($kvName in $required) {
    $val = az keyvault secret show --vault-name $KeyVault --name $kvName --query value -o tsv
    if ($val -eq 'REPLACE-ME' -or [string]::IsNullOrWhiteSpace($val)) {
        Write-Host "[FAIL] $kvName still REPLACE-ME or empty"
        $allOk = $false
    }
    else {
        Write-Host "[ OK ] $kvName configured"
    }
}

Write-Host ''
Write-Host 'Validation (Stripe — required before §7.1 billing gate):'
foreach ($kvName in @('stripe-secret-key', 'stripe-publishable-key', 'stripe-webhook-secret')) {
    $val = az keyvault secret show --vault-name $KeyVault --name $kvName --query value -o tsv 2>$null
    if ($val -eq 'REPLACE-ME' -or [string]::IsNullOrWhiteSpace($val)) {
        Write-Host "[PEND] $kvName not set"
    }
    else {
        Write-Host "[ OK ] $kvName configured"
    }
}

if (-not $allOk) {
    throw 'One or more secrets still need values. Set env vars and re-run.'
}

Write-Host ''
Write-Host "Done. Updated: $($updated.Count), already set: $($skipped.Count)"
Write-Host 'Next: .\scripts\setup-moodle-env-vm.ps1'
Write-Host 'Stripe: .\scripts\configure-stripe-vm.ps1  (see docs/stripe-integration.md)'
