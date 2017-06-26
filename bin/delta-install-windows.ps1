###########################################################################
#  Delta CLI Windows Installation
###########################################################################
#
#  This script will install the dependencies for Delta CLI on Windows 10
#
#  You must ensure Get-ExecutionPolicy is not Restricted. Run the
#  following command from PowerShell prior to running this
#  script.
# 
#      Set-ExecutionPolicy Bypass
#
#  You can then install Delta CLI and dependencies by executing this:
#
#      iex((New-Object System.Net.WebClient).DownloadString('https://raw.githubusercontent.com/bdelamatre/delta-cli-tools/master/bin/delta-install-windows.ps1'))
#
   
# Ask for elevated privelege if needed
Write-Host "Checking for elevated priveleges...[" -nonewline

If (!([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]"Administrator")) {
    Start-Process powershell.exe "-NoProfile -ExecutionPolicy Bypass -File `"$PSCommandPath`"" -Verb RunAs
    Exit
}

Write-Host "OK" -foreground "green" -NoNewLine
Write-Host "]"

#make sure windows 10
Write-Host "Checking if Windows 10...[" -NoNewline

if([System.Environment]::OSVersion.Version.Major -eq 10){
    Write-Host "OK" -foreground "green" -NoNewline
    Write-Host "]"
}else{
    Write-Host "FAIL" -foreground "red" -NoNewline
    Write-Host "]"
    Write-Host "This script was created for windows 10 only" -foreground "red"
    Exit
}

#make sure creators update is installed
Write-Host "Checking if the Windows 10 creator's update (version 1703) is installed...[" -NoNewline

$currentVersion = (Get-ItemProperty -Path "HKLM:\SOFTWARE\Microsoft\Windows NT\CurrentVersion" -Name ReleaseId).ReleaseId
if($currentVersion -ge 1703){
    Write-Host "OK" -foreground "green" -NoNewline
    Write-Host "]"
}else{
    Write-Host "FAIL" -foreground "red" -NoNewline
    Write-Host "]"
    Write-Host "You need to install the Windows 10 Creators update (version 1703). https://www.microsoft.com/en-us/software-download/windows10" -foreground "red"
    Exit
}

Write-Host "This script will install Chocolatey and other software on your computer. Continue? [Y/n]" -NoNewline
$proceed = Read-Host
if($proceed -eq "n"){
    Exit
}

#install chocolatey if it is not installed
Write-Host "Checking if chocolately is installed...[" -NoNewline

if (Get-Command "choco" -errorAction SilentlyContinue)
{
    Write-Host "OK" -foreground "green" -NoNewline
    Write-Host "]"

}else{

    Write-Host "FAIL" -foreground "red" -NoNewline
    Write-Host "]"
# From Choclately: https://chocolatey.org/install
# Don't forget to ensure ExecutionPolicy above
    iex ((New-Object System.Net.WebClient).DownloadString('https://chocolatey.org/install.ps1'))
}

# proceed with enabling linux subsystem
if (Get-Command "choco" -errorAction SilentlyContinue)
{

	# Install puppet using chocolaty
	choco install Microsoft-Windows-Subsystem-Linux  --yes --source windowsfeatures
    
    # Enable developer mode
    # https://gallery.technet.microsoft.com/scriptcenter/Enable-developer-mode-27008e86
    reg add "HKEY_LOCAL_MACHINE\SOFTWARE\Microsoft\Windows\CurrentVersion\AppModelUnlock" /t REG_DWORD /f /v "AllowDevelopmentWithoutDevLicense" /d "1"

    # Install Composer
    choco install composer --yes

    # Install DeltaCli
    composer global install bdelamatre/delta-cli

    Write-Host "Would you like to restart your computer [Y/n]" -NoNewline
    $reboot = Read-Host
    if($reboot -ne "n"){
        Write-Host "Restarting..."
        Restart-Computer
    }else{
        Write-Host "You will probably need to reboot Windows before you can use the Linux subsystem" -foreground "yellow"
    }

    #fix-me: resume script and run delta-install-windows.ps1
	
}
else
{

	Write-Host "Choclatey failed to install properly" -foreground "red"

}