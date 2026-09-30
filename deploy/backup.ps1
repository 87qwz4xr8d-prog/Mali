# สำรองข้อมูลมาลีบน Windows PC เซิร์ฟเวอร์
#   powershell -ExecutionPolicy Bypass -File .\deploy\backup.ps1
#   powershell -ExecutionPolicy Bypass -File .\deploy\backup.ps1 -Mode manual
param(
  [ValidateSet("auto", "manual")]
  [string]$Mode = "auto",
  [string]$DataDir = $env:MALI_DATA_DIR,
  [string]$BackupDir = $env:MALI_BACKUP_DIR,
  [int]$Keep = $(if ($env:MALI_BACKUP_KEEP) { [int]$env:MALI_BACKUP_KEEP } else { 14 }),
  [string]$Mirror = $env:MALI_BACKUP_MIRROR
)

$ErrorActionPreference = "Stop"
$RepoRoot = Split-Path -Parent $PSScriptRoot
Set-Location $RepoRoot

if (-not $DataDir) {
  $defaultData = Join-Path $RepoRoot "data"
  if (Test-Path "C:\mali-data") { $DataDir = "C:\mali-data" }
  elseif (Test-Path $defaultData) { $DataDir = $defaultData }
  else { $DataDir = $defaultData }
}

if (-not $BackupDir) {
  $BackupDir = Join-Path $DataDir "backups"
}

$env:MALI_DATA_DIR = $DataDir
$env:MALI_BACKUP_DIR = $BackupDir
$env:MALI_BACKUP_KEEP = "$Keep"

$node = Get-Command node -ErrorAction SilentlyContinue
if (-not $node) {
  throw "ไม่พบ node ใน PATH — ติดตั้ง Node.js 22+ หรือรันจากเครื่องที่มี Node"
}

& node (Join-Path $RepoRoot "scripts\mali-backup.mjs") --mode $Mode
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

if ($Mirror) {
  New-Item -ItemType Directory -Force -Path $Mirror | Out-Null
  robocopy $BackupDir $Mirror /MIR /NFL /NDL /NJH /NJS /nc /ns /np | Out-Null
  # robocopy exit codes 0-7 are success-ish
  if ($LASTEXITCODE -ge 8) {
    throw "สำเนา mirror ล้มเหลว (robocopy=$LASTEXITCODE)"
  }
  Write-Host "สำเนาไปที่ mirror: $Mirror"
}

Write-Host "เสร็จแล้ว · ข้อมูล=$DataDir · สำรอง=$BackupDir"
