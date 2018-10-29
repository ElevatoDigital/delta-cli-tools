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

## Docker / Windows Support

For Windows users (or any OS), there is a Docker environment and commands available to run Delta CLI from your environment.

To get started, you need to have Docker installed, with both the `docker` and `docker-compose` commands available. 
You also need to make sure that your source files are shared for mounting and that you are signed into the Docker Store.
It is also necessary to add the ./bin-docker folder to your local OS PATH variable to
access the docker commands once installed (even in a global installation).
Once those are prerequisites are met, simply execute delta in your Windows environment.

```
delta-docker
```

On Windows, `delta-docker` executes `delta-docker.bat` which passes through to `delta-docker.ps1`, a specialised script that uses `docker run` to execute your delta-cli commands.

Note: when you first execute `delta` on Windows it will init a build of the delta-cli docker container. Subsequent runs won't have this overhead.

## PHP Storm

To enable code inspections in PHPStorm, in the PHP section of Languages & Frameworks settings add `~/.composer/vendor` to your include paths
