<?php

namespace DeltaCli\Console\Output;

use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class Banner
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var string
     */
    private $background;

    /**
     * @var string
     */
    private $foreground = 'white';

    /**
     * @var bool
     */
    private $bold = true;

    /**
     * @var int
     */
    private $padding = 2;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;

        if ($this->output instanceof BufferedOutput) {
            $this->padding = 0;
        }
    }

    public function setBackground($background)
    {
        $this->background = $background;

        return $this;
    }

    public function setForeground($foreground)
    {
        $this->foreground = $foreground;

        return $this;
    }

    public function setBold($bold)
    {
        $this->bold = (boolean) $bold;
    }

    public function setPadding($padding)
    {
        $this->padding = $padding;

        return $this;
    }

    public function render($text)
    {
        $this->output->writeln(
            [
                $this->renderPaddingLine($text),
                $this->renderTextLine($text),
                $this->renderPaddingLine($text),
                ''
            ]
        );
    }

    private function renderPaddingLine($text)
    {
        $out = $this->renderFormattingTag();
        $out .= str_repeat(' ', strlen($text) + ($this->padding * 2));
        $out .= '</>';
        return $out;
    }

    private function renderTextLine($text)
    {
        $out = $this->renderFormattingTag();
        $out .= str_repeat(' ', $this->padding);
        $out .= $text;
        $out .= str_repeat(' ', $this->padding);
        $out .= '</>';
        return $out;
    }

    private function renderFormattingTag()
    {
        $segments = [];

        if ($this->foreground) {
            $segments[] = sprintf('fg=%s', $this->foreground);
        }

        if ($this->background) {
            $segments[] = sprintf('bg=%s', $this->background);
        }

        if ($this->bold) {
            $segments[] = 'options=bold';
        }

        return sprintf('<%s>', implode(';', $segments));
    }
}
