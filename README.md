# Delta Systems CLI Tools

Command-line tools used at Delta Systems for common development workflow needs.

Delta CLI is an open source project started to improve development workflows at Delta Systems. Before Delta CLI, each project had its own ad-hoc shell scripts, Phing configuration files, git hooks, etc. When switching between projects, developers would have to learn the particular idiosyncracies of that project's deployment scripts. Beyond that, these scripts were typically written without robust error handling, logging or notifications. Delta CLI scripts have a handful of important properties that address these issues.

## Documentation

http://dev.deltasys.com/

## Video Tutorials

0. Original Intro to the Delta-CLI Project https://youtu.be/lm4iAuW4sIk
0. Working with remote dev environments in Delta-CLI https://youtu.be/QlSR8enp_Cg
0. Installing Delta CLI Tools using composer https://youtu.be/JTM429G2fps
0. In Delta CLI 2.0 we added logging and Slack notifications https://youtu.be/Va_1Tsx5FFk
0. Delta CLI v3.27.0 New Feature Update 2017-01-05 https://youtu.be/5I_fq9yEl1A
0. Delta-CLI: Custom Scripts Walkthrough https://youtu.be/Nmkc_AIHe_g

## Development

After cloning the repository, install dependencies with [Composer](https://getcomposer.org/), as in the following
example:

    $ git clone git@github.com:DeltaSystems/delta-cli-tools.git
    $ cd delta-cli-tools
    $ composer install

### Test Suite

Testing is facilitated by [PHPUnit](https://phpunit.de/), and the test suite can be executed as in the following example
that is run within the root directory of the project and enables inspection of the results with
[less](http://www.greenwoodsoftware.com/less/):

    $ vendor/bin/phpunit tests/ &>phpunit.out ; less phpunit.out


## Windows

Delta CLI executes a number of shell commands that are dependent on common Linux programs which are not available out
of box on Windows. Shell commands can be wrapped by defining a [sprintf](http://php.net/sprintf) compatible string for the SHELL_WRAPPER constant.
By default, commands ran on Windows are wrapped with `bash -c "%s"`. This will execute shell commands against the
[Windows Subsystem for Linux](https://blogs.msdn.microsoft.com/wsl/) available on Windows 10 installations with the
[Creators Update](https://www.microsoft.com/en-us/software-download/windows10) installed. If you are using Windows 10
and have the Creators Update (major version 1703) installed([how to verify](https://www.microsoft.com/en-us/software-download/windows10)).
You can run the below commands in Powershell to install Delta CLI and its dependencies:

```
Set-ExecutionPolicy Bypass
iex((New-Object System.Net.WebClient).DownloadString('https://raw.githubusercontent.com/bdelamatre/delta-cli-tools/master/bin/delta-install-windows.ps1'))
```





