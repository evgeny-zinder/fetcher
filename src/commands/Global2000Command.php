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

class Global2000Command extends Command
{
	public function configure() {
		$this->setName('global2000')
			 ->addOption(
				 'out',
				 'o',
				 InputOption::VALUE_OPTIONAL,
				 'Resulting CSV file name',
				 'global2000.csv'
			 );
	}

	public function execute( InputInterface $input, OutputInterface $output ) {
		$output->write('Loading list... ');
		$data = \GuzzleHttp\json_decode(file_get_contents('https://www.forbes.com/ajax/list/data?year=2017&uri=global2000&type=organization'));

		usort($data, function($item1, $item2) {
			return $item1->position <=> $item2->position;
		});

		$data = Ar::map($data, function($item, $id) {
			return [$id => [
				'name' => $item->name,
				'link' => sprintf('https://www.forbes.com/companies/%s/', $item->uri)
			]];
		});

		if (0 === count($data)) {
			$output->writeln('got 0, exiting!' );
		}

		$output->writeln(sprintf(
			'%s found',
			Styler::style(count($data), [ForegroundColors::GREEN])
		));

		$output->writeln('Loading companies... ');
		$progressBar = new ProgressBar($output, count($data));
		$progressBar->start();

		foreach ($data as $itemId => $item) {
			$companySite = Ar::get(Xget::getInstance()
				->setUrl($item['link'])
				->parse([ 'url' => '//dl/dt[contains(., "Website")]/following-sibling::dd[1]/a/.' ]), 'url.0');

			unset($data[$itemId]['link']);
			$data[$itemId]['url'] = $companySite;

			$progressBar->advance();
		}

		$progressBar->finish();
		$output->writeln('');
		$output->writeln('Done');

		file_put_contents($input->getOption('out'), CsvFormatter::format($data));
		$output->writeln(
			sprintf(
				'Saved to %s',
				Styler::style($input->getOption('out'), ForegroundColors::GREEN)
			)
		);
	}
}
