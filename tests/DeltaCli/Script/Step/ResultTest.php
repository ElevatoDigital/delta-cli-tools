<?php

namespace DeltaCli\Script\Step;

use PHPUnit_Framework_TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class ResultTest extends PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \DeltaCli\Exception\InvalidStepResult
     */
    public function testCreatingResultWithInvalidStatusThrowsException()
    {
        /* @var $step \PHPUnit_Framework_MockObject_MockObject|\DeltaCli\Script\Step\StepInterface */
        $step   = $this->getMock('\DeltaCli\Script\Step\StepInterface');
        new Result($step, 'fafafafa');
    }

    public function testIfVerboseOutputIsNotDisplayedACommentAboutVerboseFlagIsAdded()
    {
        /* @var $step \PHPUnit_Framework_MockObject_MockObject|\DeltaCli\Script\Step\StepInterface */
        $step   = $this->getMock('\DeltaCli\Script\Step\StepInterface');
        $result = new Result($step, Result::SUCCESS, ['test', 'test']);
        $output = new BufferedOutput();

        $result->setVerboseOutput(['test', 'test']);

        $result->render($output);

        $this->assertContains(strip_tags(Result::VERBOSE_FLAG_MESSAGE), $output->fetch());
    }

    public function testVerboseOutputIsDisplayedWhenOutputObjectIsConfiguredThatWay()
    {
        /* @var $step \PHPUnit_Framework_MockObject_MockObject|\DeltaCli\Script\Step\StepInterface */
        $step   = $this->getMock('\DeltaCli\Script\Step\StepInterface');
        $result = new Result($step, Result::SUCCESS, ['normal-output']);

        $result->setVerboseOutput(['verbose-output']);

        $output = new BufferedOutput();
        $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
        $result->render($output);

        $this->assertContains('verbose-output', $output->fetch());
        $this->assertNotContains('normal-output', $output->fetch());
        $this->assertNotContains(strip_tags(Result::VERBOSE_FLAG_MESSAGE), $output->fetch());

        $output = new BufferedOutput();
        $output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
        $result->render($output);

        $this->assertContains('verbose-output', $output->fetch());
        $this->assertNotContains('normal-output', $output->fetch());
        $this->assertNotContains(strip_tags(Result::VERBOSE_FLAG_MESSAGE), $output->fetch());
    }

    public function testExplanationIsIncludedInMessageText()
    {
        /* @var $step \PHPUnit_Framework_MockObject_MockObject|\DeltaCli\Script\Step\StepInterface */
        $step   = $this->getMock('\DeltaCli\Script\Step\StepInterface');
        $result = new Result($step, Result::SUCCESS, ['normal-output']);

        $this->assertNotContains('explanation', $result->getMessageText());

        $result->setExplanation('because explanation');

        $this->assertContains('explanation', $result->getMessageText());
    }
}
