# ---------------------------------------------------------------
# CareSync Deployment Package Builder
# Run this in PowerShell from the ci4/ directory:
#   powershell -ExecutionPolicy Bypass -File deploy.ps1
# ---------------------------------------------------------------

$ErrorActionPreference = "Stop"
$dest = "C:\xampp\htdocs\caresync\caresync-deploy.zip"
$src  = Get-Location

Write-Host "`n=== CareSync Deployment Package Builder ===" -ForegroundColor Cyan

# Remove old zip if exists
if (Test-Path $dest) { Remove-Item $dest -Force }

# Files/folders to INCLUDE in the package
$includePaths = @(
    "app",
    "public",
    "system",
    "vendor",
    "writable",
    ".htaccess",
    ".env.production",
    "spark",
    "composer.json",
    "composer.lock"
)

# Create a temp staging directory
$staging = Join-Path $src "_staging_deploy"
if (Test-Path $staging) { Remove-Item $staging -Recurse -Force }
New-Item -ItemType Directory -Path $staging | Out-Null

Write-Host "Staging files..." -ForegroundColor Yellow

foreach ($item in $includePaths) {
    $sourcePath = Join-Path $src $item
    if (Test-Path $sourcePath) {
        $destPath = Join-Path $staging $item
        if (Test-Path $sourcePath -PathType Container) {
            # Copy directory, excluding runtime/writable subdirectories
            # NOTE: uploads/ and client_imports/ hold local user data (gov't ID
            # verification docs, import files) — never ship those to a public host.
            $excludeDirs = @("cache", "logs", "session", "debugbar", "uploads", "client_imports", ".git")
            Copy-Item -Path $sourcePath -Destination $destPath -Recurse -Exclude $excludeDirs
            # Clean writable runtime subdirectories (keep the dirs, empty contents).
            # uploads/ and client_imports/ hold local user data (gov't ID docs,
            # import files) — must never ship to a public host.
            foreach ($sub in @("cache", "logs", "session", "debugbar", "uploads", "client_imports")) {
                $subPath = Join-Path $destPath $sub
                if (Test-Path $subPath) {
                    # Keep the directory but empty its contents
                    Get-ChildItem -Path $subPath -Recurse -File | Remove-Item -Force
                }
            }
        } else {
            Copy-Item -Path $sourcePath -Destination $destPath
        }
        Write-Host "  + $item" -ForegroundColor Green
    } else {
        Write-Host "  ! $item not found (skipped)" -ForegroundColor Red
    }
}

# Create empty runtime directories
foreach ($sub in @("cache", "logs", "session", "debugbar", "client_imports", "uploads")) {
    $emptyDir = Join-Path $staging "writable\$sub"
    if (-not (Test-Path $emptyDir)) {
        New-Item -ItemType Directory -Path $emptyDir -Force | Out-Null
    }
}

# Create the ZIP.
# NOTE: use bsdtar (Windows tar.exe) instead of Compress-Archive so entries use
# forward slashes — Compress-Archive writes backslashes which PHP ZipArchive on
# Linux treats as literal filename characters and extraction would break.
Write-Host "`nCreating ZIP archive..." -ForegroundColor Yellow
& "$env:SystemRoot\System32\tar.exe" -a -cf $dest -C $staging .

# Cleanup staging
Remove-Item -Path $staging -Recurse -Force

$zipSize = [math]::Round((Get-Item $dest).Length / 1MB, 1)
Write-Host "`n=== Done! ===" -ForegroundColor Green
Write-Host "Package: $dest" -ForegroundColor White
Write-Host "Size:    ${zipSize} MB" -ForegroundColor White
Write-Host "`nNext steps:" -ForegroundColor Cyan
Write-Host "  1. Export your database (see DEPLOYMENT_GUIDE.txt)" -ForegroundColor White
Write-Host "  2. Register at InfinityFree.com (or your hosting provider)" -ForegroundColor White
Write-Host "  3. Upload this ZIP via File Manager" -ForegroundColor White
Write-Host "  4. Extract, edit .env, import database" -ForegroundColor White
