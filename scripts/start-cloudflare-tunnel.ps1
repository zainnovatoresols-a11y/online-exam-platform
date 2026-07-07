param(
    [string]$LocalHostName = '127.0.0.1',
    [int]$Port = 8000,
    [string]$Protocol = 'http2',
    [string]$EdgeIpVersion = '4',
    [switch]$SkipBuild,
    [switch]$NoQueue
)

$ErrorActionPreference = 'Stop'

$ProjectRoot = Resolve-Path (Join-Path $PSScriptRoot '..')
$StorageLogPath = Join-Path $ProjectRoot 'storage\logs'
$CloudflaredLogPath = Join-Path $StorageLogPath 'cloudflared-tunnel.log'
$CloudflaredErrorPath = Join-Path $StorageLogPath 'cloudflared-tunnel.err.log'
$EnvPath = Join-Path $ProjectRoot '.env'
$ViteHotPath = Join-Path $ProjectRoot 'public\hot'
$LaravelProcess = $null
$QueueProcess = $null
$TunnelProcess = $null

function Write-Step {
    param([string]$Message)

    Write-Host ''
    Write-Host $Message -ForegroundColor Cyan
}

function Get-CloudflaredPath {
    $installed = Get-Command cloudflared -ErrorAction SilentlyContinue

    if ($installed) {
        return $installed.Source
    }

    $local = Join-Path $ProjectRoot 'tools\cloudflared.exe'

    if (Test-Path $local) {
        return $local
    }

    throw 'cloudflared was not found. Run .\scripts\install-cloudflared-local.ps1 first, or install Cloudflare Tunnel globally.'
}

function Test-PortOpen {
    param(
        [string]$HostName,
        [int]$PortNumber
    )

    $client = New-Object System.Net.Sockets.TcpClient

    try {
        $connect = $client.BeginConnect($HostName, $PortNumber, $null, $null)
        $isOpen = $connect.AsyncWaitHandle.WaitOne(700, $false)

        if ($isOpen) {
            $client.EndConnect($connect)
        }

        return $isOpen
    } catch {
        return $false
    } finally {
        $client.Close()
    }
}

function Disable-ViteHotMode {
    if (Test-Path $ViteHotPath) {
        Write-Step 'Disabling Vite dev-server asset mode for public tunnel access...'
        Remove-Item -Path $ViteHotPath -Force
    }
}

function Set-DotEnvValue {
    param(
        [string]$Path,
        [string]$Key,
        [string]$Value
    )

    $content = Get-Content -Path $Path -Raw
    $escapedValue = $Value -replace '\\', '\\'
    $line = "$Key=$escapedValue"

    if ($content -match "(?m)^$([regex]::Escape($Key))=") {
        $content = [regex]::Replace($content, "(?m)^$([regex]::Escape($Key))=.*$", $line)
    } else {
        $content = $content.TrimEnd() + [Environment]::NewLine + $line + [Environment]::NewLine
    }

    Set-Content -Path $Path -Value $content -NoNewline
}

function Get-TunnelUrl {
    param(
        [string[]]$Paths
    )

    foreach ($path in $Paths) {
        if (-not (Test-Path $path)) {
            continue
        }

        $match = Select-String -Path $path -Pattern 'https://(?!api\.)[a-zA-Z0-9-]+\.trycloudflare\.com' -AllMatches |
            Select-Object -First 1

        if ($match) {
            return $match.Matches[0].Value
        }
    }

    return $null
}

try {
    Set-Location $ProjectRoot

    if (-not (Test-Path $EnvPath)) {
        throw '.env file was not found. Copy .env.example to .env and configure your database first.'
    }

    if (-not (Test-Path $StorageLogPath)) {
        New-Item -Path $StorageLogPath -ItemType Directory | Out-Null
    }

    $cloudflared = Get-CloudflaredPath

    Disable-ViteHotMode

    if (-not $SkipBuild) {
        Write-Step 'Building production frontend assets...'
        npm.cmd run build
        Disable-ViteHotMode
    }

    if (-not (Test-PortOpen -HostName $LocalHostName -PortNumber $Port)) {
        Write-Step "Starting Laravel on http://${LocalHostName}:$Port ..."
        $LaravelProcess = Start-Process `
            -FilePath 'php' `
            -ArgumentList @('artisan', 'serve', "--host=$LocalHostName", "--port=$Port") `
            -WorkingDirectory $ProjectRoot `
            -WindowStyle Hidden `
            -PassThru

        Start-Sleep -Seconds 3
    } else {
        Write-Host "Laravel already appears to be running on http://${LocalHostName}:$Port" -ForegroundColor Yellow
    }

    Write-Step 'Starting Cloudflare Quick Tunnel...'
    Remove-Item -Path $CloudflaredLogPath, $CloudflaredErrorPath -ErrorAction SilentlyContinue

    $TunnelProcess = Start-Process `
        -FilePath $cloudflared `
        -ArgumentList @('tunnel', '--edge-ip-version', $EdgeIpVersion, '--protocol', $Protocol, '--url', "http://${LocalHostName}:$Port") `
        -WorkingDirectory $ProjectRoot `
        -RedirectStandardOutput $CloudflaredLogPath `
        -RedirectStandardError $CloudflaredErrorPath `
        -WindowStyle Hidden `
        -PassThru

    $publicUrl = $null
    $deadline = (Get-Date).AddSeconds(45)

    while ((Get-Date) -lt $deadline -and -not $publicUrl) {
        Start-Sleep -Seconds 1
        if ($TunnelProcess.HasExited) {
            throw "Cloudflare tunnel stopped before a public URL was created. Check $CloudflaredLogPath and $CloudflaredErrorPath."
        }

        $publicUrl = Get-TunnelUrl -Paths @($CloudflaredLogPath, $CloudflaredErrorPath)
    }

    if (-not $publicUrl) {
        throw "Cloudflare tunnel started, but no trycloudflare.com URL was detected. Check $CloudflaredLogPath and $CloudflaredErrorPath."
    }

    Write-Step 'Updating Laravel environment for the public HTTPS URL...'
    Set-DotEnvValue -Path $EnvPath -Key 'APP_URL' -Value $publicUrl
    Set-DotEnvValue -Path $EnvPath -Key 'SESSION_SECURE_COOKIE' -Value 'true'

    php artisan config:clear
    php artisan route:clear
    php artisan view:clear

    Disable-ViteHotMode

    if (-not $NoQueue) {
        Write-Step 'Starting Laravel queue worker...'
        $QueueProcess = Start-Process `
            -FilePath 'php' `
            -ArgumentList @('artisan', 'queue:work', '--tries=3', '--timeout=900') `
            -WorkingDirectory $ProjectRoot `
            -WindowStyle Hidden `
            -PassThru
    }

    Write-Host ''
    Write-Host 'Cloudflare tunnel is ready.' -ForegroundColor Green
    Write-Host "Public URL: $publicUrl" -ForegroundColor Green
    Write-Host ''
    Write-Host 'Keep this PowerShell window open while testing. Press Ctrl+C to stop started processes.'
    Write-Host ''

    while ($TunnelProcess -and -not $TunnelProcess.HasExited) {
        Start-Sleep -Seconds 2
    }
} finally {
    Write-Host ''
    Write-Host 'Stopping local tunnel helper processes...' -ForegroundColor Yellow

    foreach ($process in @($TunnelProcess, $QueueProcess, $LaravelProcess)) {
        if ($process -and -not $process.HasExited) {
            Stop-Process -Id $process.Id -Force -ErrorAction SilentlyContinue
        }
    }
}
