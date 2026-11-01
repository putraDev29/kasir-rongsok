# Script Pembuat Pintasan Kasir Rongsok
$workingDir = $PSScriptRoot
if (-not $workingDir) {
    $workingDir = (Get-Location).Path
}

$wsh = New-Object -ComObject WScript.Shell
$vbsPath = Join-Path $workingDir "START_KASIR_RONGSOK.vbs"

# Pilih icon aktif: app_logo_active.ico (logo usaha custom) jika ada, atau app_icon.ico
$icoPath = Join-Path $workingDir "app_logo_active.ico"
if (-not (Test-Path $icoPath)) {
    $icoPath = Join-Path $workingDir "app_icon.ico"
}

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

# 2. Desktop Shortcut Detection & Update
$candidates = @()

try {
    $regDesk = (Get-ItemProperty "HKCU:\Software\Microsoft\Windows\CurrentVersion\Explorer\User Shell Folders" -ErrorAction SilentlyContinue).Desktop
    if ($regDesk) {
        $exp = [Environment]::ExpandEnvironmentStrings($regDesk)
        if (Test-Path $exp) { $candidates += $exp }
    }
} catch {}

try {
    $sfDesk = $wsh.SpecialFolders.Item("Desktop")
    if ($sfDesk -and (Test-Path $sfDesk)) { $candidates += $sfDesk }
} catch {}

if ($env:USERPROFILE) {
    $uDesk = Join-Path $env:USERPROFILE "Desktop"
    if (Test-Path $uDesk) { $candidates += $uDesk }
    $oneDesk = Join-Path $env:USERPROFILE "OneDrive\Desktop"
    if (Test-Path $oneDesk) { $candidates += $oneDesk }
}

if ($env:PUBLIC) {
    $pubDesk = Join-Path $env:PUBLIC "Desktop"
    if (Test-Path $pubDesk) { $candidates += $pubDesk }
}

$uniqueDesks = $candidates | Select-Object -Unique

foreach ($desk in $uniqueDesks) {
    try {
        $deskLnkPath = Join-Path $desk "Kasir Rongsok.lnk"
        if (Test-Path $deskLnkPath) {
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
    } catch {}
}

# 3. Flush & refresh Explorer icon cache
try {
    $code = @"
[System.Runtime.InteropServices.DllImport("Shell32.dll")]
public static extern void SHChangeNotify(int eventId, int flags, System.IntPtr item1, System.IntPtr item2);
"@
    Add-Type -MemberDefinition $code -Namespace Win32 -Name ShellApi -ErrorAction SilentlyContinue
    [Win32.ShellApi]::SHChangeNotify(0x08000000, 0, [System.IntPtr]::Zero, [System.IntPtr]::Zero)
} catch {}

Start-Process -FilePath "ie4uinit.exe" -ArgumentList "-show" -WindowStyle Hidden -ErrorAction SilentlyContinue
