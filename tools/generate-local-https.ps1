param(
    [switch] $TrustForCurrentUser
)

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$outputDirectory = Join-Path $projectRoot 'backend\storage\https'
$openssl = 'C:\xampp\apache\bin\openssl.exe'
$opensslConfig = 'C:\xampp\apache\conf\openssl.cnf'

foreach ($requiredPath in @($openssl, $opensslConfig)) {
    if (-not (Test-Path -LiteralPath $requiredPath)) {
        throw "Required XAMPP OpenSSL file was not found: $requiredPath"
    }
}

$env:OPENSSL_CONF = $opensslConfig

New-Item -ItemType Directory -Force -Path $outputDirectory | Out-Null
$serverKey = Join-Path $outputDirectory 'server.key'
$serverCertificate = Join-Path $outputDirectory 'server.crt'
$caCertificate = Join-Path $outputDirectory 'ca.crt'

& $openssl req -x509 -newkey rsa:2048 -nodes -sha256 -days 365 `
    -subj '/CN=localhost' `
    -addext 'subjectAltName=DNS:localhost,IP:127.0.0.1' `
    -addext 'keyUsage=digitalSignature,keyEncipherment' `
    -addext 'extendedKeyUsage=serverAuth' `
    -keyout $serverKey `
    -out $serverCertificate

if ($LASTEXITCODE -ne 0) {
    throw 'OpenSSL failed to generate the controlled local certificate.'
}

Copy-Item -LiteralPath $serverCertificate -Destination $caCertificate -Force

if ($TrustForCurrentUser) {
    certutil -user -addstore Root $caCertificate | Out-Null
    if ($LASTEXITCODE -ne 0) {
        throw 'The certificate could not be added to the current-user trust store.'
    }
}

& $openssl x509 -in $serverCertificate -noout -subject -issuer -dates -fingerprint -sha256
