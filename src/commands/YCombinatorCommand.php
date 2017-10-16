<?php

namespace Datanyze\fetcher\commands;


use Datanyze\fetcher\helpers\CsvFormatter;
use eznio\xget\Xget;
use eznio\ar\Ar;
use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class YCombinatorCommand extends Command {

	public function configure() {
		$this
			->setName( 'ycombinator' )
			->addOption(
				'out',
				'o',
				InputOption::VALUE_OPTIONAL,
				'Resulting CSV file name',
				'ycombinator.csv'
			);
	}

	public function execute( InputInterface $input, OutputInterface $output ) {
		$output->write( 'Loading companies list... ' );
		$companies = (new Xget(new Client()))
			->setUrl('http://yclist.com/')
			->parse([
				'@' => '//tbody/tr[@class="operating"]',
				'name' => '//td[not(*)]/.',
				'domain' => '//td/a/.',
			]);
		$output->writeln( 'done.' );

		$data = Ar::map($companies, function ($item, $id) {
			return [$id => [
				$item['name'],
				Ar::get($item, 'domain')
			]];
		});

		file_put_contents($input->getOption('out'), CsvFormatter::format($data));

		$output->writeln(sprintf( 'Saved to %s', $input->getOption('out')));
	}
}