# Script Pembuat Pintasan Kasir Rongsok
$workingDir = $PSScriptRoot
if (-not $workingDir) {
    $workingDir = (Get-Location).Path
}

$wsh = New-Object -ComObject WScript.Shell
$vbsPath = Join-Path $workingDir "START_KASIR_RONGSOK.vbs"

# Deteksi dan pilih icon aktif secara dinamis (multi-fallback & auto-generate jika hilang)
$candidateIcons = @(
    (Join-Path $workingDir "app_logo_active.ico"),
    (Join-Path $workingDir "app_icon.ico"),
    (Join-Path $workingDir "kasir rongsok\public\favicon.ico")
)

$foundIcon = $null
foreach ($cand in $candidateIcons) {
    if (Test-Path $cand) {
        $fi = Get-Item $cand
        if ($fi.Length -gt 500) {
            $foundIcon = $cand
            break
        }
    }
}

if (-not $foundIcon) {
    # Generate icon secara dinamis menggunakan PHP jika belum tersedia
    $phpCmd = "php"
    if (Test-Path "C:\xampp\php\php.exe") { $phpCmd = "C:\xampp\php\php.exe" }
    elseif (Test-Path "D:\xampp\php\php.exe") { $phpCmd = "D:\xampp\php\php.exe" }
    elseif (Test-Path "E:\xampp\php\php.exe") { $phpCmd = "E:\xampp\php\php.exe" }

    $artisan = Join-Path $workingDir "kasir rongsok\artisan"
    if (Test-Path $artisan) {
        & $phpCmd $artisan app:generate-icon 2>$null
    }

    foreach ($cand in $candidateIcons) {
        if (Test-Path $cand) {
            $fi = Get-Item $cand
            if ($fi.Length -gt 500) {
                $foundIcon = $cand
                break
            }
        }
    }
}

$icoPath = if ($foundIcon) { $foundIcon } else { Join-Path $workingDir "app_icon.ico" }

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
        # 1. Pintasan 'Kasir Rongsok'
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

        # 2. Pintasan 'START_KASIR_RONGSOK'
        $startLnkPath = Join-Path $desk "START_KASIR_RONGSOK.lnk"
        if (Test-Path $startLnkPath) {
            Remove-Item $startLnkPath -Force -ErrorAction SilentlyContinue
        }
        $startLnk = $wsh.CreateShortcut($startLnkPath)
        $startLnk.TargetPath = "wscript.exe"
        $startLnk.Arguments = "`"$vbsPath`""
        $startLnk.WorkingDirectory = $workingDir
        $startLnk.Description = "Mulai Aplikasi Kasir Rongsok & WA Gateway"
        if (Test-Path $icoPath) {
            $startLnk.IconLocation = "$icoPath,0"
        }
        $startLnk.Save()

        # 3. Pintasan 'WhatsApp Gateway'
        $waBatPath = Join-Path $workingDir "wa_gateway\run_wa_gateway.bat"
        $waLnkPath = Join-Path $desk "WhatsApp Gateway.lnk"
        if (Test-Path $waLnkPath) {
            Remove-Item $waLnkPath -Force -ErrorAction SilentlyContinue
        }
        $waLnk = $wsh.CreateShortcut($waLnkPath)
        $waLnk.TargetPath = "cmd.exe"
        $waLnk.Arguments = "/c start `"Kasir Rongsok WhatsApp Gateway`" `"$waBatPath`""
        $waLnk.WorkingDirectory = Join-Path $workingDir "wa_gateway"
        $waLnk.Description = "WhatsApp Gateway Kasir Rongsok (Port 21465)"
        if (Test-Path $icoPath) {
            $waLnk.IconLocation = "$icoPath,0"
        }
        $waLnk.Save()

        Write-Host "Pintasan Desktop berhasil dibuat di: $desk" -ForegroundColor Green
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
