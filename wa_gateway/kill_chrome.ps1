# Kill lingering Chrome/Edge processes holding the kasir-rongsok session
Get-CimInstance Win32_Process -Filter "Name = 'chrome.exe' or Name = 'msedge.exe'" -ErrorAction SilentlyContinue | Where-Object {
    $_.CommandLine -like '*kasir-rongsok*' -or $_.CommandLine -like '*dental-care-clinic*'
} | ForEach-Object {
    Stop-Process -Id $_.ProcessId -Force -ErrorAction SilentlyContinue
}

