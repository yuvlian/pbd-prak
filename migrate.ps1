param(
    [string]$Migration
)

$ErrorActionPreference = "Stop"

$envFile = Join-Path $PSScriptRoot ".env"
if (-not (Test-Path $envFile)) {
    Write-Error ".env not found"
    exit 1
}

$url = $null
Get-Content $envFile | ForEach-Object {
    if ($_ -match '^\s*DATABASE_URL\s*=\s*(.+?)\s*$') { $url = $Matches[1] }
}
if (-not $url) {
    Write-Error "DATABASE_URL not found in .env"
    exit 1
}

if ($url -notmatch '^mysql://([^:]+):([^@]+)@([^:/]+):(\d+)/([^/?]+)') {
    Write-Error "cannot parse DATABASE_URL: $url"
    exit 1
}

$dbUser = $Matches[1]
$dbPass = $Matches[2]
$dbHost = $Matches[3]
$dbPort = $Matches[4]
$dbName = $Matches[5]

$mysql = Get-Command mysql.exe -ErrorAction SilentlyContinue
if (-not $mysql) {
    Write-Error "mysql.exe not found in PATH"
    exit 1
}
$mysql = $mysql.Source

& $mysql --host=$dbHost --port=$dbPort --user=$dbUser --password=$dbPass -e "create database if not exists ``$dbName``"
if ($LASTEXITCODE -ne 0) { exit 1 }

$migrations = Get-ChildItem (Join-Path $PSScriptRoot "migrations") -Filter *.sql | Sort-Object Name

if ($Migration) {
    $migrations = $migrations | Where-Object { $_.Name.StartsWith($Migration) }

    if (-not $migrations) {
        Write-Error "no migration found starting with '$Migration'"
        exit 1
    }
}

$migrations | ForEach-Object {
    $sqlFile = $_.FullName -replace "\\", "/"
    Write-Host "applying $($_.Name) ..."
    & $mysql --host=$dbHost --port=$dbPort --user=$dbUser --password=$dbPass $dbName -e "source $sqlFile"
    if ($LASTEXITCODE -ne 0) {
        Write-Error "failed on $($_.Name)"
        exit 1
    }
}

Write-Host "done."
