#get the location of this file, save current location
$ScriptDir = Split-Path $script:MyInvocation.MyCommand.Path
$OriginalLocation = Get-Location

#move up one directory from script location
Set-Location "$ScriptDir/../"

#build docker container
docker build --tag delta-cli -f ./Dockerfile .

#move back to the original location
Set-Location "$OriginalLocation"