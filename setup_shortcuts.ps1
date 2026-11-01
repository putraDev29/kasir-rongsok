# Script Pembuat Pintasan Kasir Rongsok
$workingDir = $PSScriptRoot
if (-not $workingDir) {
    $workingDir = (Get-Location).Path
}

$wsh = New-Object -ComObject WScript.Shell
$vbsPath = Join-Path $workingDir "START_KASIR_RONGSOK.vbs"
$icoPath = Join-Path $workingDir "app_icon.ico"

# 1. Project Directory Shortcut
$projLnkPath = Join-Path $workingDir "START_KASIR_RONGSOK.lnk"
if (Test-Path $projLnkPath) {
    Remove-Item $projLnkPath -Force -ErrorAction SilentlyContinue
}
$projLnk = $wsh.CreateShortcut($projLnkPath)
$projLnk.TargetPath = "wscript.exe"
$projLnk.Arguments = "`"$vbsPath`""
$projLnk.WorkingDirectory = $workingDir
$projLnk.Description = "Mulai Aplikasi Kasir Rongsok & WA Gateway"
if (Test-Path $icoPath) {
    $projLnk.IconLocation = "$icoPath,0"
}
$projLnk.Save()
Write-Host "Pintasan Folder Proyek berhasil dibuat: $projLnkPath" -ForegroundColor Green

# 2. Desktop Shortcut
$desktopPath = $wsh.SpecialFolders.Item("Desktop")
if (-not $desktopPath -or -not (Test-Path $desktopPath -ErrorAction SilentlyContinue)) {
    $desktopPath = Join-Path $env:USERPROFILE "Desktop"
}

if ($desktopPath -and (Test-Path $desktopPath -ErrorAction SilentlyContinue)) {
    $deskLnkPath = Join-Path $desktopPath "Kasir Rongsok.lnk"
    if (Test-Path $deskLnkPath -ErrorAction SilentlyContinue) {
        Remove-Item $deskLnkPath -Force -ErrorAction SilentlyContinue
    }
    $deskLnk = $wsh.CreateShortcut($deskLnkPath)
    $deskLnk.TargetPath = "wscript.exe"
    $deskLnk.Arguments = "`"$vbsPath`""
    $deskLnk.WorkingDirectory = $workingDir
    $deskLnk.Description = "Mulai Aplikasi Kasir Rongsok & WA Gateway"
    if (Test-Path $icoPath) {
        $deskLnk.IconLocation = "$icoPath,0"
    }
    $deskLnk.Save()
    Write-Host "Pintasan Desktop 'Kasir Rongsok' berhasil dibuat di: $deskLnkPath" -ForegroundColor Green
}
