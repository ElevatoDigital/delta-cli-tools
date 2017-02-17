# Delta Systems CLI Tools

Command-line tools used at Delta Systems for common development workflow needs.

## Documentation

http://dev.deltasys.com/

## Video Tutorials

0. Original Intro to the Project https://www.youtube.com/watch?v=lm4iAuW4sIk&feature=youtu.be
0. Delta CLI v3.27.0 New Feature Update 2017-01-05 https://youtu.be/5I_fq9yEl1A

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

