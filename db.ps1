$env:MYSQL_PWD = 'root'

mysql -u root -h 127.0.0.1 -P 3307 --protocol=tcp -e 'drop database if exists pbd_prak; create database if not exists pbd_prak;'

Get-ChildItem "$PSScriptRoot\database\*.sql" | Sort-Object Name | ForEach-Object {
    Write-Host $_.Name
    Get-Content $_.FullName -Raw | & 'mysql' -u root -h 127.0.0.1 -P 3307 --protocol=tcp pbd_prak
}
