# Interactive Stripe Key Vault setup for understandtech.app v1.0.0 billing gate.
# Prompts securely for Stripe test/live keys; never echoes secret values.
#
# Prerequisites:
#   az login
#   Key Vault Secrets Officer on utkvnhhwegpz3rem6
#
# Usage:
#   .\scripts\stripe-kv-setup-interactive.ps1
#   .\scripts\stripe-kv-setup-interactive.ps1 -SkipConfigureVm   # KV only
#
# After success:
#   ./scripts/configure-stripe-remote.sh   (Git Bash/WSL)
#   .\scripts\configure-stripe-vm.ps1      (SSH alternative)

param(
    [string]$KeyVault = 'utkvnhhwegpz3rem6',
    [switch]$SkipConfigureVm
)

$ErrorActionPreference = 'Stop'
$env:Path = [System.Environment]::GetEnvironmentVariable('Path', 'Machine') + ';' + [System.Environment]::GetEnvironmentVariable('Path', 'User')

$stripeSecrets = @(
    @{
        KvName   = 'stripe-secret-key'
        Prompt   = 'Stripe secret key (sk_test_… or sk_live_…)'
        EnvNames = @('STRIPE_SECRET_KEY')
        Prefix   = 'sk_'
    },
    @{
        KvName   = 'stripe-publishable-key'
        Prompt   = 'Stripe publishable key (pk_test_… or pk_live_…)'
        EnvNames = @('STRIPE_PUBLISHABLE_KEY')
        Prefix   = 'pk_'
    },
    @{
        KvName   = 'stripe-webhook-secret'
        Prompt   = 'Stripe webhook signing secret (whsec_…)'
        EnvNames = @('STRIPE_WEBHOOK_SECRET')
        Prefix   = 'whsec_'
    }
)

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

function Get-KvSecretValue {
    param([string]$Name)
    $val = az keyvault secret show --vault-name $KeyVault --name $Name --query value -o tsv 2>$null
    if ($LASTEXITCODE -ne 0) {
        return $null
    }
    return $val
}

function Set-KvSecret {
    param(
        [string]$Name,
        [string]$Value
    )
    az keyvault secret set --vault-name $KeyVault --name $Name --value $Value -o none | Out-Null
    if ($LASTEXITCODE -ne 0) {
        throw "Failed to set Key Vault secret '$Name' (check RBAC)."
    }
}

Write-Host "Key Vault: $KeyVault"
$null = az account show -o none 2>$null
if ($LASTEXITCODE -ne 0) {
    throw 'Azure CLI not logged in. Run: az login'
}

$updated = 0
$skipped = 0

foreach ($entry in $stripeSecrets) {
    $current = Get-KvSecretValue -Name $entry.KvName
    if ($null -eq $current) {
        Write-Host "[init] $($entry.KvName) absent — will create"
    }
    elseif ($current -ne 'REPLACE-ME' -and -not [string]::IsNullOrWhiteSpace($current)) {
        Write-Host "[skip] $($entry.KvName) already configured (len=$($current.Length))"
        $skipped++
        continue
    }
    else {
        Write-Host "[pend] $($entry.KvName) is REPLACE-ME or empty"
    }

    $value = Get-SecretFromEnv -EnvNames $entry.EnvNames
    if ([string]::IsNullOrWhiteSpace($value)) {
        $value = Read-SecretSecure -Prompt $entry.Prompt
    }

    if ([string]::IsNullOrWhiteSpace($value) -or $value -eq 'REPLACE-ME') {
        throw "No valid value provided for $($entry.KvName)"
    }

    if (-not $value.StartsWith($entry.Prefix)) {
        throw "$($entry.KvName) must start with '$($entry.Prefix)' — check the Stripe Dashboard value"
    }

    Set-KvSecret -Name $entry.KvName -Value $value
    Write-Host "[set]  $($entry.KvName) updated (len=$($value.Length))"
    $updated++
}

Write-Host ''
Write-Host 'Validation (lengths only):'
foreach ($entry in $stripeSecrets) {
    $val = Get-KvSecretValue -Name $entry.KvName
    if ($null -eq $val -or $val -eq 'REPLACE-ME' -or [string]::IsNullOrWhiteSpace($val)) {
        Write-Host "[FAIL] $($entry.KvName) still missing"
    }
    else {
        Write-Host "[ OK ] $($entry.KvName) (len=$($val.Length))"
    }
}

Write-Host ''
Write-Host "Done. Updated: $updated, already set: $skipped"

if (-not $SkipConfigureVm -and $updated -gt 0) {
    Write-Host ''
    Write-Host 'Next: merge Stripe vars onto VM'
    Write-Host '  Git Bash: ./scripts/configure-stripe-remote.sh'
    Write-Host '  Or SSH:   .\scripts\configure-stripe-vm.ps1'
    Write-Host ''
    Write-Host 'Then Moodle admin (required — no CLI for payment accounts):'
    Write-Host '  1. Site administration → Plugins → Payment gateways → enable Stripe'
    Write-Host '  2. Site administration → Payments → Payment accounts → create + add Stripe gateway'
    Write-Host '  3. Per course: Enrolment methods → Enrolment on payment → link account + fee'
    Write-Host '  See docs/stripe-integration.md'
}
