# Run configure-stripe-vm.sh on the production VM via SSH (secrets stay out of repo).
param(
    [string]$VmHost = 'azureadmin@52.252.59.54',
    [string]$KeyVault = 'utkvnhhwegpz3rem6',
    [switch]$DryRun
)

$ErrorActionPreference = 'Stop'
$env:Path = [System.Environment]::GetEnvironmentVariable('Path', 'Machine') + ';' + [System.Environment]::GetEnvironmentVariable('Path', 'User')

$repoRoot = Split-Path -Parent $PSScriptRoot
$localScript = Join-Path $repoRoot 'scripts/configure-stripe-vm.sh'
if (-not (Test-Path $localScript)) {
    throw "Missing $localScript"
}

$null = az account show -o none 2>$null
if ($LASTEXITCODE -ne 0) {
    throw 'Azure CLI not logged in. Run: az login'
}

$remotePath = '/tmp/configure-stripe-vm.sh'
$sshKey = Join-Path $env:USERPROFILE '.ssh/id_ed25519'
$scpArgs = @('-i', $sshKey, '-o', 'BatchMode=yes', $localScript, "${VmHost}:${remotePath}")
$sshBase = @('-i', $sshKey, '-o', 'BatchMode=yes', $VmHost)

scp @scpArgs
if ($LASTEXITCODE -ne 0) {
    throw 'SCP failed — check SSH key and VM host'
}

$dryFlag = if ($DryRun) { '--dry-run' } else { '' }
$remoteCmd = "chmod +x $remotePath && KEY_VAULT=$KeyVault $remotePath $dryFlag"
ssh @sshBase $remoteCmd
if ($LASTEXITCODE -ne 0) {
    throw 'Remote configure-stripe-vm.sh failed'
}

Write-Host 'Done. Complete Moodle payment account setup in admin UI — docs/stripe-integration.md'
