<?php

namespace Datanyze\fetcher\commands;


use Datanyze\fetcher\helpers\CsvFormatter;
use eznio\ar\Ar;
use eznio\styler\references\ForegroundColors;
use eznio\styler\Styler;
use eznio\xget\Xget;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UniversitiesCommand extends Command
{
	protected $url = null;
	protected $commandName = null;
	protected $commandAliases = [];
	protected $defaultFilename = null;

	public function configure() {
		$this->setName($this->commandName)
			 ->setAliases($this->commandAliases)
			 ->addOption(
				 'out',
				 'o',
				 InputOption::VALUE_OPTIONAL,
				 'Resulting CSV file name',
				 $this->defaultFilename
			 );
	}

	public function execute( InputInterface $input, OutputInterface $output ) {
		$output->write('Getting pages count... ');

		$firstPage = file_get_contents($this->url . '1');
		preg_match('/Found (\d+) matches , displaying 1 - 50/', $firstPage, $matches);
		$numRecords = (int) Ar::get($matches, '1');

		if (0 === $numRecords) {
			$output->writeln('got 0, exiting!' );
		}

		$numPages = floor($numRecords / 50) + 1;
		$output->writeln(sprintf(
			'%s found',
			Styler::style($numPages, [ForegroundColors::GREEN])
		));

		$output->writeln('Loading universities... ');
		$progressBar = new ProgressBar($output, $numPages);
		$progressBar->start();

		$total = [];
		for ($page = 1; $page <= $numPages; $page++) {
			$url = $this->url . $page;
			$data = Xget::getInstance()
						->setUrl($url)
						->parse([
							'universities' => [
								'@' => '//ol/li',
								'name' => '//a/.',
								'url' => '//a/@href'
							]
						]);
			$total = array_merge($total, $data['universities']);
			$progressBar->advance();
		}

		$progressBar->finish();
		$output->writeln('');
		$output->writeln('Done');

		file_put_contents($input->getOption('out'), CsvFormatter::format($total));
		$output->writeln(
			sprintf(
				'Saved to %s',
				Styler::style($input->getOption('out'), ForegroundColors::GREEN)
			)
		);
	}
}
