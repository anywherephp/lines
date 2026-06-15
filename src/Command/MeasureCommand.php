<?php

declare (strict_types=1);
namespace Lines202606\TomasVotruba\Lines\Command;

use Lines202606\Entropy\Console\Contract\CommandInterface;
use Lines202606\Entropy\Console\Enum\ExitCode;
use Lines202606\Entropy\Console\Output\OutputPrinter;
use Lines202606\Entropy\Console\Output\ProgressBar;
use Lines202606\TomasVotruba\Lines\Analyser;
use Lines202606\TomasVotruba\Lines\Console\OutputFormatter\JsonOutputFormatter;
use Lines202606\TomasVotruba\Lines\Console\OutputFormatter\TextOutputFormatter;
use Lines202606\TomasVotruba\Lines\Finder\PhpFilesFinder;
final class MeasureCommand implements CommandInterface
{
    /**
     * @readonly
     * @var \TomasVotruba\Lines\Finder\PhpFilesFinder
     */
    private $phpFilesFinder;
    /**
     * @readonly
     * @var \TomasVotruba\Lines\Analyser
     */
    private $analyser;
    /**
     * @readonly
     * @var \TomasVotruba\Lines\Console\OutputFormatter\JsonOutputFormatter
     */
    private $jsonOutputFormatter;
    /**
     * @readonly
     * @var \TomasVotruba\Lines\Console\OutputFormatter\TextOutputFormatter
     */
    private $textOutputFormatter;
    /**
     * @readonly
     * @var \Entropy\Console\Output\OutputPrinter
     */
    private $outputPrinter;
    /**
     * @readonly
     * @var \Entropy\Console\Output\ProgressBar
     */
    private $progressBar;
    public function __construct(PhpFilesFinder $phpFilesFinder, Analyser $analyser, JsonOutputFormatter $jsonOutputFormatter, TextOutputFormatter $textOutputFormatter, OutputPrinter $outputPrinter, ProgressBar $progressBar)
    {
        $this->phpFilesFinder = $phpFilesFinder;
        $this->analyser = $analyser;
        $this->jsonOutputFormatter = $jsonOutputFormatter;
        $this->textOutputFormatter = $textOutputFormatter;
        $this->outputPrinter = $outputPrinter;
        $this->progressBar = $progressBar;
    }
    public function getName() : string
    {
        return 'measure';
    }
    public function getDescription() : string
    {
        return 'Measure lines of code in given path(s)';
    }
    /**
     * @api invoked dynamically by entropy console
     *
     * @param bool $json Output in JSON format
     * @param string[] $paths Paths to analyze
     * @param string[] $excludes Paths to exclude
     * @param bool $short Print short metrics only
     * @param bool $allowVendor Allow /vendor directory to be scanned
     * @param bool $longest Show top 10 longest files
     * @return ExitCode::*
     */
    public function run(bool $json = \false, array $paths = [], array $excludes = [], bool $short = \false, bool $allowVendor = \false, bool $longest = \false) : int
    {
        if ($paths === []) {
            $paths = [(string) \getcwd()];
        }
        $filePaths = $this->phpFilesFinder->findInDirectories($paths, $excludes, $allowVendor);
        if ($filePaths === []) {
            $this->outputPrinter->error('No files found to scan');
            return ExitCode::ERROR;
        }
        $progressBarClosure = $this->createProgressBarClosure($json, $filePaths);
        $measurements = $this->analyser->measureFiles($filePaths, $progressBarClosure);
        // print results
        if ($json) {
            $this->jsonOutputFormatter->printMeasurement($measurements, $short, $longest);
        } else {
            $this->progressBar->finish();
            $this->textOutputFormatter->printMeasurement($measurements, $short, $longest);
        }
        return ExitCode::SUCCESS;
    }
    /**
     * @param string[] $filePaths
     */
    private function createProgressBarClosure(bool $isJson, array $filePaths) : ?callable
    {
        if ($isJson) {
            return null;
        }
        $this->progressBar->start(\count($filePaths));
        return function () : void {
            $this->progressBar->advance();
        };
    }
}
