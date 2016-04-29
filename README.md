# Delta Systems CLI Tools

Command-line tools used at Delta Systems for common development workflow needs.

## Installation

0. Install Composer globally (https://getcomposer.org/doc/00-intro.md#globally).
0. Add ~/.composer/vendor/bin/ to your $PATH.
0. Install the Delta CLI tools by running `composer global require deltasystems/delta-cli`
0. Change to your project's directory and run `delta` to get started.

## Configuring Your Project

All CLI tools configuration is done via the delta-cli.php file.  You can generate one for your project
using the create-project-config command:

    delta create-project-config

At this point, the generated file will be quite sparse.  It will just have your project's name.

Typically, the first thing you'll want to do in delta-cli.php is configure the environments and
deployment script for your project.  Take a look at the example delta-cli.php file to get started
(https://github.com/DeltaSystems/delta-cli-tools/blob/master/delta-cli.php).

Some key concepts to note from the example:

0. You can pass a shell command directly to addStep().  You can either pass just the command or you can provide two arguments: a step name and your command.
0. You can do the same with any valid PHP callable (an inline Closure or any class method, for example).
0. You can specify some steps as being specific to only some of your environments.
0. While the deploy script is built-in, you can add custom scripts with the same API.

Whenever you're running a CLI tools script, you have some options available to you that are worth
noting:

0. The environment name is the primary required argument.  Example: `delta my-script production`.
0. --skip-step=[STEP-NAME]: Allows you to skip one or more steps in the script.
0. --dry-run: Will attempt to perform a dry run.  Steps that don't support it will be skipped.
0. --list-steps: Will just list the steps in a script rather than trying to run them.  (Helpful especially if many steps were added by a template rather than directly in your delta-cli.php.)

## What's next?

The CLI tools project is still quite young.  In the near future, we'll be working on these additions:

0. More step types.  Currently, you can add PHP callbacks and shell commands to your scripts.  We plan to add specialized steps for rsync, checking for common issues, etc.
0. Templates for common environments like WordPress and Zend Framework.
0. Build out the environment API.  We will add things like credentials and host names to the environment objects so that script steps don't have to be specified repeatedly.
0. The ability to notify people after a script has been run.
