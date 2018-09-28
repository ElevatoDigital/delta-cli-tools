#get the location of this file
$ScriptDir = Split-Path $script:MyInvocation.MyCommand.Path

#test if image exists
$DockerImageExists = (docker images -q delta-cli)

#build the image if doesn't exist
if(!$DockerImageExists){
    Write-Host "Docker delta-cli image doesn't exist. Attempting to build." -ForegroundColor Red
    & "$ScriptDir/delta-docker-build.ps1"
}

#directory of the user's api key
$ApiKeyDir = "$($env:USERPROFILE)"

#filename for the api key
$ApiKeyFile = ".delta-api.json"

#path to the api key
$ApiKeyPath = "$($ApiKeyDir)\$($ApiKeyFile)"

#create a blank API key file if it doesn't exist
if (!(Test-Path "$ApiKeyPath")){
   New-Item -path $ApiKeyDir -name $ApiKeyFile -type "file"
   Write-Host "Created blank file for API key at $ApiKeyPath. This will be corrected when you login." -ForegroundColor Red
}else{
    Write-Host "Loaded API Key from $ApiKeyPath" -ForegroundColor Green
}

#this is useful when embedded in a project directory
#$ProjectDir = "$PSScriptRoot/.."

#build the project mount paths
#fix-me: for now, we assume that we are running from the project root
$CurrentLocation = Get-Location
$ProjectBaseDir = "$CurrentLocation"

Write-Host "Project directory set to $ProjectBaseDir" -ForegroundColor Green

#build and test project layout
$ProjectSrcDir = "$ProjectBaseDir/src"
$ProjectDbDir = "$ProjectBaseDir/db"
$ProjectDeltaCliConfigFile = "$ProjectBaseDir/delta-cli.php"
$ProjectSshKeysDir = "$ProjectBaseDir/ssh-keys"

#start building the command
$BuildCmd = "docker run --rm --interactive --tty"
$BuildSubcmd = ""

#test src path
if (!(Test-Path -Path "$ProjectSrcDir")){
    Write-Host "src/ directory not detected ($ProjectSrcDir)" -ForegroundColor Red
    #assume the src dir to the base dir
    Write-Host "changing the src/ directory to the base directory ($ProjectBaseDir)" -ForegroundColor Yellow
    $ProjectSrcDir = "$ProjectBaseDir"
}else{
    Write-Host "src/ directory detected" -ForegroundColor Green
}

#assemble src path (always required)
$BuildCmd = "${BuildCmd} --volume `"${ProjectSrcDir}:/app/src`""

#test and assemble db path
if (!(Test-Path -Path "$ProjectDbDir")){
    Write-Host "db/ directory not detected ($ProjectDbDir)" -ForegroundColor Red
}else{
    $BuildCmd = "${BuildCmd} --volume `"${ProjectDbDir}:/app/db`""
}

#test and assemble delta-cli.php
if (!(Test-Path -Path "$ProjectDeltaCliConfigFile")){
    Write-Host "delta-cli.php file not detected ($ProjectDeltaCliConfigFile)" -ForegroundColor Red
}else{
    Write-Host "delta-cli.php file detected ($ProjectDeltaCliConfigFile)" -ForegroundColor Green
    $BuildCmd = "${BuildCmd} --volume `"${ProjectDeltaCliConfigFile}:/app/delta-cli.php`""
}

#test and assemble ssh-keys
if (!(Test-Path -Path "$ProjectSshKeysDir")){
    Write-Host "ssh-keys/ directory not detected ($ProjectSshKeysDir)" -ForegroundColor Red
}else{
    Write-Host "ssh-keys/ directory detected" -ForegroundColor Green
    $BuildCmd = "${BuildCmd} --volume `"${ProjectSshKeysDir}:/delta-cli-ssh-keys`" "
    $BuildSubCmd = "cp -r /delta-cli-ssh-keys /app/ssh-keys && chmod -R 600 /app/ssh-keys && "
}

#finish assembling command
$BuildCmd = "${BuildCmd} delta-cli bash -c `"${BuildSubCmd}delta ${args} `""

#execute the command
Invoke-Expression $BuildCmd