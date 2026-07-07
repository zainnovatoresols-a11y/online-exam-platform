param(
    [string]$DownloadUrl = 'https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-windows-amd64.exe'
)

$ErrorActionPreference = 'Stop'

$ProjectRoot = Resolve-Path (Join-Path $PSScriptRoot '..')
$ToolsPath = Join-Path $ProjectRoot 'tools'
$CloudflaredPath = Join-Path $ToolsPath 'cloudflared.exe'

if (-not (Test-Path $ToolsPath)) {
    New-Item -Path $ToolsPath -ItemType Directory | Out-Null
}

Write-Host 'Downloading cloudflared for Windows...' -ForegroundColor Cyan
Invoke-WebRequest -Uri $DownloadUrl -OutFile $CloudflaredPath

Write-Host ''
Write-Host 'cloudflared installed locally:' -ForegroundColor Green
Write-Host $CloudflaredPath
Write-Host ''
Write-Host 'Next run:' -ForegroundColor Cyan
Write-Host '.\scripts\start-cloudflare-tunnel.ps1'
