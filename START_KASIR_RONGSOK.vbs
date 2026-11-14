' ================================================================
'  KASIR RONGSOK - SILENT LAUNCHER & AUTO STARTUP
'  Safe Startup: Menjalankan Laravel & WA Gateway Sekali Klik
'  Zero Permission Denied: Native WMI & WinHttp Windows
' ================================================================

Option Explicit
On Error Resume Next

Dim WshShell, fso, currentDir, chromeExe

Set WshShell = CreateObject("WScript.Shell")
Set fso = CreateObject("Scripting.FileSystemObject")
currentDir = fso.GetParentFolderName(WScript.ScriptFullName)

' Fungsi memeriksa apakah proses Windows sedang berjalan (Native WMI)
Function IsProcessRunning(procName)
    On Error Resume Next
    Dim wmiObj, colProcs
    IsProcessRunning = False
    Set wmiObj = GetObject("winmgmts:\\.\root\cimv2")
    If Not wmiObj Is Nothing Then
        Set colProcs = wmiObj.ExecQuery("Select * from Win32_Process Where Name = '" & procName & "'")
        If Not colProcs Is Nothing Then
            If colProcs.Count > 0 Then
                IsProcessRunning = True
            End If
        End If
    End If
    Set colProcs = Nothing
    Set wmiObj = Nothing
End Function

' Fungsi memeriksa apakah URL HTTP melayani respon (Native WinHttp)
Function CheckHttp(url)
    On Error Resume Next
    Dim http
    CheckHttp = False
    Set http = CreateObject("WinHttp.WinHttpRequest.5.1")
    If Not http Is Nothing Then
        http.SetTimeouts 800, 800, 800, 800
        http.Open "GET", url, False
        http.Send
        If Err.Number = 0 Then
            CheckHttp = True
        End If
    End If
    Set http = Nothing
    Err.Clear
End Function

' Fungsi memeriksa apakah URL HTTP mengembalikan teks tertentu (misal: "connected":true)
Function CheckHttpContent(url, textToFind)
    On Error Resume Next
    Dim http
    CheckHttpContent = False
    Set http = CreateObject("WinHttp.WinHttpRequest.5.1")
    If Not http Is Nothing Then
        http.SetTimeouts 1000, 1000, 1000, 1000
        http.Open "GET", url, False
        http.Send
        If Err.Number = 0 Then
            If InStr(http.ResponseText, textToFind) > 0 Then
                CheckHttpContent = True
            End If
        End If
    End If
    Set http = Nothing
    Err.Clear
End Function

' Menunggu MySQL aktif
Function WaitForMySql(maxSeconds)
    Dim i
    WaitForMySql = False
    For i = 1 To maxSeconds
        If IsProcessRunning("mysqld.exe") Then
            WaitForMySql = True
            Exit Function
        End If
        WScript.Sleep 1000
    Next
End Function

' Menunggu HTTP URL aktif
Function WaitForHttpUrl(url, maxSeconds)
    Dim i
    WaitForHttpUrl = False
    For i = 1 To maxSeconds
        If CheckHttp(url) Then
            WaitForHttpUrl = True
            Exit Function
        End If
        WScript.Sleep 1000
    Next
End Function

' Fungsi mencari Google Chrome
Function GetChromePath()
    Dim paths, p, appData
    appData = WshShell.ExpandEnvironmentStrings("%LOCALAPPDATA%")
    paths = Array( _
        "C:\Program Files\Google\Chrome\Application\chrome.exe", _
        "C:\Program Files (x86)\Google\Chrome\Application\chrome.exe", _
        appData & "\Google\Chrome\Application\chrome.exe" _
    )
    GetChromePath = ""
    For Each p In paths
        If fso.FileExists(p) Then
            GetChromePath = p
            Exit Function
        End If
    Next
End Function

' 0. Pastikan Windows Firewall selalu mengizinkan akses Tablet/HP (Port 8000 & 21465)
WshShell.Run "cmd /c netsh advfirewall firewall add rule name=""Kasir Rongsok Port 8000"" dir=in action=allow protocol=TCP localport=8000 profile=any >nul 2>&1", 0, False
WshShell.Run "cmd /c netsh advfirewall firewall add rule name=""Kasir Rongsok WA Gateway"" dir=in action=allow protocol=TCP localport=21465 profile=any >nul 2>&1", 0, False

' 0.1. Cari icon aktif terbaik
Dim activeIconPath
activeIconPath = ""
If fso.FileExists(currentDir & "\app_logo_active.ico") Then
    If fso.GetFile(currentDir & "\app_logo_active.ico").Size > 500 Then
        activeIconPath = currentDir & "\app_logo_active.ico"
    End If
End If
If activeIconPath = "" And fso.FileExists(currentDir & "\app_icon.ico") Then
    If fso.GetFile(currentDir & "\app_icon.ico").Size > 500 Then
        activeIconPath = currentDir & "\app_icon.ico"
    End If
End If
If activeIconPath = "" And fso.FileExists(currentDir & "\kasir rongsok\public\favicon.ico") Then
    If fso.GetFile(currentDir & "\kasir rongsok\public\favicon.ico").Size > 500 Then
        activeIconPath = currentDir & "\kasir rongsok\public\favicon.ico"
    End If
End If

' 0.2. Buat atau sinkronkan shortcut Desktop di semua lokasi profil (OneDrive/Local/Public)
Dim candidates, dPath, regDesk, userProf, pubDesk, lnk, sObj
Set candidates = CreateObject("Scripting.Dictionary")

' SpecialFolders
dPath = WshShell.SpecialFolders("Desktop")
If dPath <> "" And fso.FolderExists(dPath) Then candidates(dPath) = True

' Registry HKCU User Shell Folders Desktop
regDesk = ""
On Error Resume Next
regDesk = WshShell.RegRead("HKCU\Software\Microsoft\Windows\CurrentVersion\Explorer\User Shell Folders\Desktop")
If regDesk <> "" Then
    regDesk = WshShell.ExpandEnvironmentStrings(regDesk)
    If fso.FolderExists(regDesk) Then candidates(regDesk) = True
End If
Err.Clear

' USERPROFILE & OneDrive Desktop
userProf = WshShell.ExpandEnvironmentStrings("%USERPROFILE%")
If userProf <> "" Then
    If fso.FolderExists(userProf & "\Desktop") Then candidates(userProf & "\Desktop") = True
    If fso.FolderExists(userProf & "\OneDrive\Desktop") Then candidates(userProf & "\OneDrive\Desktop") = True
End If

' PUBLIC Desktop
pubDesk = WshShell.ExpandEnvironmentStrings("%PUBLIC%")
If pubDesk <> "" Then
    If fso.FolderExists(pubDesk & "\Desktop") Then candidates(pubDesk & "\Desktop") = True
End If

For Each dPath In candidates.Keys
    ' 1. Kasir Rongsok.lnk di Desktop
    lnk = dPath & "\Kasir Rongsok.lnk"
    Set sObj = WshShell.CreateShortcut(lnk)
    sObj.TargetPath = "wscript.exe"
    sObj.Arguments = """" & currentDir & "\START_KASIR_RONGSOK.vbs"""
    sObj.WorkingDirectory = currentDir
    sObj.Description = "Mulai Aplikasi Kasir Rongsok & WA Gateway"
    If activeIconPath <> "" Then
        sObj.IconLocation = activeIconPath & ",0"
    End If
    sObj.Save
    Set sObj = Nothing

    ' 2. START_KASIR_RONGSOK.lnk di Desktop
    lnk = dPath & "\START_KASIR_RONGSOK.lnk"
    Set sObj = WshShell.CreateShortcut(lnk)
    sObj.TargetPath = "wscript.exe"
    sObj.Arguments = """" & currentDir & "\START_KASIR_RONGSOK.vbs"""
    sObj.WorkingDirectory = currentDir
    sObj.Description = "Mulai Aplikasi Kasir Rongsok & WA Gateway"
    If activeIconPath <> "" Then
        sObj.IconLocation = activeIconPath & ",0"
    End If
    sObj.Save
    Set sObj = Nothing

    ' 3. WhatsApp Gateway.lnk di Desktop (Sebagai pintasan mandiri tambahan jika diperlukan)
    lnk = dPath & "\WhatsApp Gateway.lnk"
    Set sObj = WshShell.CreateShortcut(lnk)
    sObj.TargetPath = "cmd.exe"
    sObj.Arguments = "/c start ""Kasir Rongsok WhatsApp Gateway"" """ & currentDir & "\wa_gateway\run_wa_gateway.bat"""
    sObj.WorkingDirectory = currentDir & "\wa_gateway"
    sObj.Description = "WhatsApp Gateway Kasir Rongsok (Port 21465)"
    If activeIconPath <> "" Then
        sObj.IconLocation = activeIconPath & ",0"
    End If
    sObj.Save
    Set sObj = Nothing
Next

' Pastikan shortcut di folder proyek selalu sinkron ke folder saat ini
Dim pLnk
pLnk = currentDir & "\START_KASIR_RONGSOK.lnk"
Set sObj = WshShell.CreateShortcut(pLnk)
sObj.TargetPath = "wscript.exe"
sObj.Arguments = """" & currentDir & "\START_KASIR_RONGSOK.vbs"""
sObj.WorkingDirectory = currentDir
sObj.Description = "Mulai Aplikasi Kasir Rongsok & WA Gateway"
If activeIconPath <> "" Then
    sObj.IconLocation = activeIconPath & ",0"
End If
sObj.Save
Set sObj = Nothing

' ================================================================
' LANGKAH 1: MULAI WHATSAPP GATEWAY SEGERA (Parallel Startup)
' Jendela Command Prompt terlihat agar pengguna langsung melihat
' status koneksi WhatsApp Gateway berjalan secara real-time.
' ================================================================
If Not CheckHttp("http://127.0.0.1:21465/api/status") Then
    WshShell.CurrentDirectory = currentDir & "\wa_gateway"
    WshShell.Run "cmd.exe /c start ""Kasir Rongsok WhatsApp Gateway"" run_wa_gateway.bat", 1, False
End If

' ================================================================
' LANGKAH 2: PASTIKAN DATABASE MYSQL BERJALAN
' ================================================================
If Not IsProcessRunning("mysqld.exe") Then
    WshShell.Run "cmd /c net start mysql >nul 2>&1", 0, True
    If Not IsProcessRunning("mysqld.exe") Then
        WshShell.Run "cmd /c net start mariadb >nul 2>&1", 0, True
    End If

    If Not IsProcessRunning("mysqld.exe") Then
        Dim drives, drv, batP, binP
        drives = Array("C", "D", "E")
        For Each drv In drives
            batP = drv & ":\xampp\mysql_start.bat"
            binP = drv & ":\xampp\mysql\bin\mysqld.exe"
            If fso.FileExists(batP) Then
                WshShell.Run """" & batP & """", 0, False
                Exit For
            ElseIf fso.FileExists(binP) Then
                WshShell.Run """" & binP & """ --defaults-file=""" & drv & ":\xampp\mysql\bin\my.ini"" --standalone", 0, False
                Exit For
            End If
        Next
    End If
    Call WaitForMySql(15)
    WScript.Sleep 1000
End If

' ================================================================
' LANGKAH 3: JALANKAN BACKEND LARAVEL (Port 8000 - Mode Senyap)
' ================================================================
If Not CheckHttp("http://127.0.0.1:8000") Then
    WshShell.CurrentDirectory = currentDir & "\kasir rongsok"
    WshShell.Run "cmd.exe /c run_backend.bat", 0, False
End If

WshShell.CurrentDirectory = currentDir

' ================================================================
' LANGKAH 4: TUNGGU SERVER SIAP & TERHUBUNG
' ================================================================
Call WaitForHttpUrl("http://127.0.0.1:8000", 25)

' Tunggu sampai WhatsApp Gateway siap (baik CONNECTED maupun QR_READY, maks 12 detik)
Dim w
For w = 1 To 12
    If CheckHttpContent("http://127.0.0.1:21465/api/status", """connected"":true") Or CheckHttpContent("http://127.0.0.1:21465/api/status", """status"":""QR_READY""") Then
        Exit For
    End If
    WScript.Sleep 1000
Next
WScript.Sleep 500

' ================================================================
' LANGKAH 5: BUKA BROWSER OTOMATIS
' ================================================================
chromeExe = GetChromePath()
If chromeExe <> "" Then
    WshShell.Run """" & chromeExe & """ http://localhost:8000", 1, False
Else
    WshShell.Run "http://localhost:8000", 1, False
End If

Set WshShell = Nothing
Set fso = Nothing
WScript.Quit 0
