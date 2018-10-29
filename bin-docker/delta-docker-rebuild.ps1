#get the location of this file, save current location
$ScriptDir = Split-Path $script:MyInvocation.MyCommand.Path

#remove the existing image
docker image rm delta-cli

#build again
& "$ScriptDir/delta-docker-build.ps1"