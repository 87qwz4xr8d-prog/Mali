# ติดตั้ง Auto Backup บน Windows PC เซิร์ฟเวอร์ (Task Scheduler ทุกวัน 02:00)
#   powershell -ExecutionPolicy Bypass -File .\deploy\install-auto-backup.ps1
# รันในฐานะ Administrator
param(
  [string]$DataDir = $env:MALI_DATA_DIR,
  [string]$BackupDir = $env:MALI_BACKUP_DIR,
  [int]$Keep = $(if ($env:MALI_BACKUP_KEEP) { [int]$env:MALI_BACKUP_KEEP } else { 14 }),
  [string]$Mirror = $env:MALI_BACKUP_MIRROR,
  [int]$Hour = 2,
  [string]$TaskName = "MaliAutoBackup"
)

$ErrorActionPreference = "Stop"
$RepoRoot = Split-Path -Parent $PSScriptRoot
$BackupScript = Join-Path $PSScriptRoot "backup.ps1"

if (-not $DataDir) {
  $defaultData = Join-Path $RepoRoot "data"
  if (Test-Path "C:\mali-data") { $DataDir = "C:\mali-data" }
  else { $DataDir = $defaultData }
}
if (-not $BackupDir) {
  $BackupDir = Join-Path $DataDir "backups"
}

New-Item -ItemType Directory -Force -Path $BackupDir | Out-Null

$arg = "-NoProfile -ExecutionPolicy Bypass -File `"$BackupScript`" -Mode auto -DataDir `"$DataDir`" -BackupDir `"$BackupDir`" -Keep $Keep"
if ($Mirror) { $arg += " -Mirror `"$Mirror`"" }

$action = New-ScheduledTaskAction -Execute "powershell.exe" -Argument $arg -WorkingDirectory $RepoRoot
$trigger = New-ScheduledTaskTrigger -Daily -At ([datetime]::Today.AddHours($Hour))
$settings = New-ScheduledTaskSettingsSet -StartWhenAvailable -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries
$principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest

Register-ScheduledTask -TaskName $TaskName -Action $action -Trigger $trigger -Settings $settings -Principal $principal -Force | Out-Null

Write-Host "สร้าง Scheduled Task '$TaskName' แล้ว (ทุกวัน $Hour:00)"
Write-Host "ทดสอบสำรองครั้งแรก..."
$testArgs = @{
  Mode = "auto"
  DataDir = $DataDir
  BackupDir = $BackupDir
  Keep = $Keep
}
if ($Mirror) { $testArgs.Mirror = $Mirror }
& $BackupScript @testArgs

Write-Host @"

Auto Backup พร้อมแล้ว

  ข้อมูล:   $DataDir
  สำรอง:   $BackupDir (เก็บ $Keep ชุด)
  Mirror:   $(if ($Mirror) { $Mirror } else { "(ไม่ตั้ง)" })
  Task:     $TaskName
  รันมือ:   powershell -ExecutionPolicy Bypass -File .\deploy\backup.ps1 -Mode manual
  ในแอป:   /backup (Admin)

"@
