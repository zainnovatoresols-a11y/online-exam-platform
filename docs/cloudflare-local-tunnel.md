# Cloudflare Local Tunnel

Use this when you want to keep every service running on your local machine but expose the Laravel app through a free HTTPS Cloudflare URL.

## What Runs Locally

- Laravel
- MySQL or Laragon database
- Queue worker
- Docker code execution
- FFmpeg video merge
- Local private storage

Cloudflare only exposes the web URL. It does not run the backend services.

## One-Time Install

If `cloudflared` is not installed globally, run:

```powershell
.\scripts\install-cloudflared-local.ps1
```

This downloads `tools\cloudflared.exe` inside the project.

## Start Public URL

From the project root:

```powershell
.\scripts\start-cloudflare-tunnel.ps1
```

The script will:

1. run `npm run build`
2. start `php artisan serve`
3. start a Cloudflare Quick Tunnel
4. copy the generated `https://*.trycloudflare.com` URL into `.env` as `APP_URL`
5. set `SESSION_SECURE_COOKIE=true`
6. clear Laravel config/route/view cache
7. start `php artisan queue:work`

Keep the PowerShell window open while testing.

## Useful Options

Skip frontend build:

```powershell
.\scripts\start-cloudflare-tunnel.ps1 -SkipBuild
```

Skip queue worker:

```powershell
.\scripts\start-cloudflare-tunnel.ps1 -NoQueue
```

Use a different local port:

```powershell
.\scripts\start-cloudflare-tunnel.ps1 -Port 8080
```

Use a different Cloudflare tunnel transport if your network blocks the default:

```powershell
.\scripts\start-cloudflare-tunnel.ps1 -Protocol http2 -EdgeIpVersion 4
```

## Important Notes

- Quick Tunnel URLs change when restarted.
- Your PC must stay powered on and connected to the internet.
- Camera and screen recording need HTTPS, so use the Cloudflare URL for candidate testing.
- After the tunnel URL changes, send fresh invitations so email links use the current `APP_URL`.
- If the URL does not load on mobile while the tunnel log says it is connected, test from mobile data instead of the same WiFi. Some networks block Cloudflare edge HTTPS even when `cloudflared` itself can connect out.
- If you want a stable URL, create a named Cloudflare Tunnel with your own domain later.
