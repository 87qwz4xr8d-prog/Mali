# Mali CMMS installer for Windows (Docker Desktop).
# Run from the repo root in PowerShell:
#   powershell -ExecutionPolicy Bypass -File .\deploy\install.ps1

param(
  [int]$Port = 3000,
  [string]$DataDir = "",
  [string]$AdminPassword = "",
  [string]$SessionSecret = ""
)

$ErrorActionPreference = "Stop"
$RepoRoot = Split-Path -Parent $PSScriptRoot
Set-Location $RepoRoot

function Require-Docker {
  if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
    throw "ไม่พบ docker — ติดตั้ง Docker Desktop แล้วเปิดให้ทำงานก่อน"
  }
  docker compose version | Out-Null
}

Require-Docker

if (-not $DataDir) {
  $DataDir = Join-Path $RepoRoot "data"
}
if (-not $AdminPassword) {
  $secure = Read-Host "รหัสผ่านผู้ใช้ admin (ติดตั้งครั้งแรก)" -AsSecureString
  $AdminPassword = [Runtime.InteropServices.Marshal]::PtrToStringAuto(
    [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure)
  )
}
if ($AdminPassword.Length -lt 8) {
  throw "รหัสผ่าน admin สั้นเกินไป (อย่างน้อย 8 ตัวอักษร)"
}
if (-not $SessionSecret) {
  $bytes = New-Object byte[] 32
  [System.Security.Cryptography.RandomNumberGenerator]::Create().GetBytes($bytes)
  $SessionSecret = ($bytes | ForEach-Object { $_.ToString("x2") }) -join ""
}

New-Item -ItemType Directory -Force -Path (Join-Path $DataDir "uploads") | Out-Null

$envFile = Join-Path $RepoRoot ".env"
@"
NODE_ENV=production
PORT=$Port
MALI_BIND=0.0.0.0
MALI_DATA_DIR=$DataDir
MALI_SESSION_SECRET=$SessionSecret
MALI_ADMIN_PASSWORD=$AdminPassword
MALI_SHOW_SEED_LOGINS=0
MALI_HTTPS=0
"@ | Set-Content -Path $envFile -Encoding ascii

Write-Host "กำลัง docker compose up --build …"
docker compose --env-file $envFile up -d --build

$ok = $false
for ($i = 0; $i -lt 60; $i++) {
  try {
    $res = Invoke-WebRequest -Uri "http://127.0.0.1:$Port/login" -UseBasicParsing -TimeoutSec 3
    if ($res.StatusCode -ge 200 -and $res.StatusCode -lt 400) { $ok = $true; break }
  } catch {
    Start-Sleep -Seconds 2
  }
}

if (-not $ok) {
  docker compose logs --tail 80
  throw "คอนเทนเนอร์ยังไม่ตอบที่ /login"
}

Write-Host ""
Write-Host "มาลีทำงานแล้ว: http://localhost:$Port/login"
Write-Host "ข้อมูล SQLite: $DataDir\mali.db"
Write-Host "เข้าด้วย admin และรหัสที่ตั้งตอนติดตั้ง"
Write-Host "บัญชี seed อื่น (tech/engineer/manager) เป็นข้อมูลทดลอง — รหัส Mali@2569"
