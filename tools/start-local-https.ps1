$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$apache = 'C:\xampp\apache\bin\httpd.exe'
$mainConfig = 'C:\xampp\apache\conf\httpd.conf'
$week14Config = Join-Path $PSScriptRoot 'week14-httpd-ssl.conf'
$certificate = Join-Path $projectRoot 'backend\storage\https\server.crt'

foreach ($requiredPath in @($apache, $mainConfig, $week14Config, $certificate)) {
    if (-not (Test-Path -LiteralPath $requiredPath)) {
        throw "Required HTTPS file is missing: $requiredPath"
    }
}

$includeDirective = "Include `"$($week14Config.Replace('\', '/'))`""
& $apache -t -f $mainConfig -c $includeDirective
if ($LASTEXITCODE -ne 0) {
    throw 'Apache rejected the controlled Week 14 HTTPS configuration.'
}

$existingListener = Get-NetTCPConnection -State Listen -LocalPort 8443 -ErrorAction SilentlyContinue
if ($existingListener) {
    Write-Output 'HTTPS listener is already available on port 8443.'
    exit 0
}

Start-Process -FilePath $apache `
    -ArgumentList @('-f', $mainConfig, '-c', $includeDirective) `
    -WindowStyle Hidden

Write-Output 'Started the controlled HTTPS endpoint at https://localhost:8443.'
