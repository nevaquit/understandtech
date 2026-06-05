# Publish understandtech to GitHub (Option A automation)
# Prerequisites: gh auth login completed

$ErrorActionPreference = 'Stop'
Set-Location (Split-Path $PSScriptRoot -Parent)

Write-Host "Checking GitHub CLI auth..."
gh auth status | Out-Null

Write-Host "Creating private repo nevaquit/understandtech (if missing)..."
$repoExists = $false
try {
  gh repo view nevaquit/understandtech | Out-Null
  $repoExists = $true
  Write-Host "Repo already exists."
} catch {
  gh repo create understandtech --private --description "understandtech.app platform monorepo" --source=. --remote=origin
  $repoExists = $true
}

if (-not (git remote get-url origin 2>$null)) {
  git remote add origin git@github.com:nevaquit/understandtech.git
}

Write-Host "Pushing main to origin..."
git push -u origin main

Write-Host "Done: https://github.com/nevaquit/understandtech"
