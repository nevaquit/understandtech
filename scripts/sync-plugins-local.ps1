# Sync moodle-plugins from monorepo into local Moodle dirroot (./moodle).
param(
    [string]$Src = (Join-Path $PSScriptRoot "..\moodle-plugins"),
    [string]$MoodleDir = (Join-Path $PSScriptRoot "..\moodle")
)

$ErrorActionPreference = "Stop"
$Root = Resolve-Path (Join-Path $PSScriptRoot "..")

$PluginMap = @{
    "theme_understandtech"              = "theme/understandtech"
    "local_certmaster"                  = "local/certmaster"
    "local_aitutor"                     = "local/aitutor"
    "local_aigrading"                   = "local/aigrading"
    "local_gamification"                = "local/gamification"
    "local_community"                   = "local/community"
    "local_integrations"                = "local/integrations"
    "block_examreadiness"               = "block/examreadiness"
    "block_studyplan"                   = "block/studyplan"
    "block_portfolio"                   = "block/portfolio"
    "mod_ctfflag"                       = "mod/ctfflag"
    "qbehaviour_certmasterconfidence"   = "question/behaviour/certmasterconfidence"
}

if (-not (Test-Path $MoodleDir)) {
    throw "Moodle dirroot not found: $MoodleDir (clone MOODLE_405_STABLE first)"
}

foreach ($entry in $PluginMap.GetEnumerator()) {
    $srcName = $entry.Key
    $relPath = $entry.Value
    $srcPath = Join-Path $Src $srcName
    $dstPath = Join-Path $MoodleDir ($relPath -replace "/", [IO.Path]::DirectorySeparatorChar)

    if (-not (Test-Path $srcPath)) {
        continue
    }

    $versionFile = Join-Path $srcPath "version.php"
    if (-not (Test-Path $versionFile)) {
        if (Test-Path $dstPath) {
            Remove-Item -Recurse -Force $dstPath
            Write-Host "removed placeholder $relPath"
        }
        continue
    }

    $parent = Split-Path $dstPath -Parent
    if (-not (Test-Path $parent)) {
        New-Item -ItemType Directory -Path $parent -Force | Out-Null
    }

    if (Test-Path $dstPath) {
        Remove-Item -Recurse -Force $dstPath
    }
    Copy-Item -Path $srcPath -Destination $dstPath -Recurse -Force
    Write-Host "deployed $relPath"
}

Write-Host "plugins synced to $MoodleDir"
