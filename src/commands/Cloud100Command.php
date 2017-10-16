<?php

namespace Datanyze\fetcher\commands;


use Datanyze\fetcher\Constants;
use Datanyze\fetcher\helpers\CsvFormatter;
use eznio\ar\Ar;
use eznio\styler\references\ForegroundColors;
use eznio\styler\Styler;
use eznio\xget\Xget;
use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Cloud100Command extends Command
{
	public function configure() {
		$this->setName('cloud100')
			->addOption(
				'out',
				'o',
				InputOption::VALUE_OPTIONAL,
				'Resulting CSV file name',
				'cloud100.csv'
			);
	}

	public function execute( InputInterface $input, OutputInterface $output ) {
		$output->write('Loading companies list... ');

		$list = file_get_contents('https://www.forbes.com/ajax/list/data?year=2017&uri=cloud100&type=organization');
		$list = json_decode($list);
		$list = Ar::map($list, function($item, $id) {
			return [$id => (array) $item];
		});
		usort($list, function($item1, $item2) {
			return Ar::get($item1, 'position') <=> Ar::get($item2, 'position');
		});

		$output->writeln(
			sprintf(
				'done, %s found',
				Styler::style(count($list), ForegroundColors::LIGHT_GREEN)
			)
		);
		$output->writeln('Parsing company pages to gather URLs...');

		$progressBar = new ProgressBar($output, 100);
		$progressBar->start();

		$noSite = 0;
		foreach ($list as $id => $item) {
			$companyUrl = sprintf('https://www.forbes.com/companies/%s/', Ar::get($item, 'uri'));
			$companySite = Ar::get((new Xget(new Client()))
				->setUrl($companyUrl)
				->parse([ 'url' => '//dl/dt[contains(., "Website")]/following-sibling::dd[1]/a/.' ]), 'url.0');
			if (null === $companySite) {
				$noSite++;
			} else {
				$list[$id]['site'] = $companySite;
			}
			$progressBar->advance();
		}
		$output->writeln(
			sprintf(
				'Done, %s sites missing',
				Styler::style($noSite, ForegroundColors::RED)
			)
		);

		$outputData = Ar::map($list, function($item, $id) {
			return [$id => [
				$item['name'],
				Ar::get($item, 'site')
			]];
		});

		file_put_contents(
			$input->getOption('out'),
			CsvFormatter::format($outputData)
		);

		$output->writeln(
			sprintf(
				'Saved to %s',
				Styler::style($input->getOption('out'), ForegroundColors::GREEN)
			)
		);
	}
}
