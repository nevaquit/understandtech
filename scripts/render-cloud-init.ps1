# Render cloud-init.yaml with Azure-fetched values.
# Usage: .\scripts\render-cloud-init.ps1 [-OutputPath infrastructure/runner/cloud-init.rendered.yaml]
param(
    [string]$OutputPath = (Join-Path $PSScriptRoot '..\infrastructure\runner\cloud-init.rendered.yaml'),
    [string]$PostgresFqdn = 'understandtech-pg-prod.postgres.database.azure.com',
    [string]$StorageAccountName = 'utstnhhwegpz3rem6',
    [string]$RegistrationToken = 'PLACEHOLDER_RUNNER_TOKEN',
    [string]$RepoSshUrl = 'git@github.com:nevaquit/understandtech.git'
)

$ErrorActionPreference = 'Stop'
$env:Path = [System.Environment]::GetEnvironmentVariable('Path', 'Machine') + ';' + [System.Environment]::GetEnvironmentVariable('Path', 'User')

$template = Join-Path $PSScriptRoot '..\infrastructure\runner\cloud-init.yaml'
$content = Get-Content -Raw -Path $template

if (-not $env:SMB_PASSWORD) {
    $env:SMB_PASSWORD = az storage account keys list `
        --account-name $StorageAccountName `
        --resource-group understandtech-prod-rg `
        --query '[0].value' -o tsv
}

$content = $content.Replace('{{POSTGRES_FQDN}}', $PostgresFqdn)
$content = $content.Replace('{{REGISTRATION_TOKEN}}', $RegistrationToken)
$content = $content.Replace('{{STORAGE_ACCOUNT_NAME}}', $StorageAccountName)
$content = $content.Replace('{{SMB_PASSWORD}}', $env:SMB_PASSWORD)
$content = $content.Replace('{{REPO_SSH_URL}}', $RepoSshUrl)

[System.IO.File]::WriteAllText($OutputPath, $content)
Write-Host "Rendered cloud-init to $OutputPath"
