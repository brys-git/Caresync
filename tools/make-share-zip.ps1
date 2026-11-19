<#
.SYNOPSIS
  Builds a clean zip of the CareSync app (the ci4 folder) that is safe to share.

.DESCRIPTION
  Leaves out everything private or machine-specific:
    - .env and env, and any other .env.* file except .env.production.example
    - writable/session, logs, debugbar, cache, uploads and imports (their index.html files are kept)
    - .git, node_modules, composer.phar and any *.zip
    - vendor/ (only when you pass -IncludeVendor)
  After the zip is written the script re-reads it and stops with an error if a forbidden
  file slipped in, so a bad zip is never left behind.

.PARAMETER OutFile
  Where to write the zip. Default: caresync-share-<yyyyMMdd-HHmm>.zip in the folder that
  contains ci4 (the repository root).

.PARAMETER IncludeVendor
  Also include ci4/vendor (leave it out unless the receiver cannot run `composer install`).

.EXAMPLE
  powershell -ExecutionPolicy Bypass -File tools\make-share-zip.ps1

.EXAMPLE
  powershell -ExecutionPolicy Bypass -File tools\make-share-zip.ps1 -OutFile C:\Temp\caresync.zip -IncludeVendor
#>
[CmdletBinding()]
param(
    [string]$OutFile,
    [switch]$IncludeVendor
)

$ErrorActionPreference = 'Stop'

$repoRoot = Split-Path -Parent $PSScriptRoot
$appDir   = Join-Path $repoRoot 'ci4'
if (-not (Test-Path -LiteralPath $appDir)) {
    throw "Cannot find the ci4 folder at $appDir"
}

if (-not $OutFile) {
    $OutFile = Join-Path $repoRoot ('caresync-share-' + (Get-Date -Format 'yyyyMMdd-HHmm') + '.zip')
}
$OutFile = [System.IO.Path]::GetFullPath($OutFile)
if (Test-Path -LiteralPath $OutFile) {
    Remove-Item -LiteralPath $OutFile -Force
}

# Paths are matched relative to ci4/, with forward slashes, case-insensitively.
$excludeFolders = @(
    'writable/session', 'writable/logs', 'writable/debugbar', 'writable/cache',
    'writable/uploads', 'writable/imports',
    '.git', 'node_modules'
)
if (-not $IncludeVendor) { $excludeFolders += 'vendor' }

# index.html placeholders keep the writable folders alive for the receiver.
$keepFiles = @(
    'writable/session/index.html', 'writable/logs/index.html', 'writable/debugbar/index.html',
    'writable/cache/index.html', 'writable/uploads/index.html', 'writable/imports/index.html'
)

function Test-Excluded([string]$rel) {
    $r = $rel.ToLowerInvariant()
    if ($keepFiles -contains $r) { return $false }

    $leaf = [System.IO.Path]::GetFileName($r)
    if ($leaf -eq '.env' -or $leaf -eq 'env') { return $true }
    if ($leaf -like '.env.*' -and $leaf -ne '.env.production.example') { return $true }
    if ($leaf -eq 'composer.phar' -or $leaf -like '*.zip') { return $true }

    foreach ($f in $excludeFolders) {
        if ($r -eq $f -or $r.StartsWith($f + '/')) { return $true }
    }

    return $false
}

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$added   = 0
$skipped = 0
$prefix  = $appDir.TrimEnd('\') + '\'

$zip = [System.IO.Compression.ZipFile]::Open($OutFile, [System.IO.Compression.ZipArchiveMode]::Create)
try {
    Get-ChildItem -LiteralPath $appDir -Recurse -File -Force | ForEach-Object {
        $rel = $_.FullName.Substring($prefix.Length).Replace('\', '/')
        if (Test-Excluded $rel) { $skipped++; return }
        [void][System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $_.FullName, 'ci4/' + $rel, [System.IO.Compression.CompressionLevel]::Optimal)
        $added++
    }
    # The deploy/ folder (SQL for the live database, checklists) sits next to ci4/. A partD_backfill*.sql
    # file is generated from live data, so it is never shared.
    $deployDir = Join-Path $repoRoot 'deploy'
    if (Test-Path -LiteralPath $deployDir) {
        $dprefix = $deployDir.TrimEnd('\') + '\'
        Get-ChildItem -LiteralPath $deployDir -Recurse -File -Force | ForEach-Object {
            if ($_.Name -like 'partD_backfill*') { $skipped++; return }
            [void][System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $_.FullName, 'deploy/' + $_.FullName.Substring($dprefix.Length).Replace('\', '/'), [System.IO.Compression.CompressionLevel]::Optimal)
            $added++
        }
    }
}
finally {
    $zip.Dispose()
}

# Safety check: read the finished zip back and refuse it if anything private got in.
$bad = @()
$check = [System.IO.Compression.ZipFile]::OpenRead($OutFile)
try {
    foreach ($e in $check.Entries) {
        if (-not $e.FullName.StartsWith('ci4/')) { if ($e.FullName -like 'deploy/partD_backfill*') { $bad += $e.FullName }; continue }
        $rel = $e.FullName.Substring('ci4/'.Length).ToLowerInvariant()
        $leaf = [System.IO.Path]::GetFileName($rel)
        $isPlaceholder = $leaf -eq 'index.html'
        if ($leaf -eq '.env' -or $leaf -eq 'env') { $bad += $e.FullName }
        elseif ($rel -match '^writable/(session|logs|debugbar|cache|uploads|imports)/' -and -not $isPlaceholder) { $bad += $e.FullName }
        elseif (-not $IncludeVendor -and $rel.StartsWith('vendor/')) { $bad += $e.FullName }
    }
}
finally {
    $check.Dispose()
}

if ($bad.Count -gt 0) {
    Remove-Item -LiteralPath $OutFile -Force
    throw ("The zip was removed because it contained files that must not be shared:`n  " + ($bad -join "`n  "))
}

$sizeMb = [math]::Round((Get-Item -LiteralPath $OutFile).Length / 1MB, 2)
Write-Host ""
Write-Host "Created: $OutFile"
Write-Host "  $added files added, $skipped left out, $sizeMb MB"
Write-Host "  Left out: .env/env, writable session/logs/debugbar/cache/uploads/imports (index.html kept)" -NoNewline
if ($IncludeVendor) { Write-Host "." } else { Write-Host ", vendor/." }
