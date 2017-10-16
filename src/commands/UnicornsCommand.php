<?php

namespace Datanyze\fetcher\commands;


use eznio\xget\Xget;
use eznio\ar\Ar;
use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use eznio\styler\Styler;
use eznio\styler\references\ForegroundColors;

class UnicornsCommand extends Command {

	public function configure() {
		$this->setName( 'unicorns' );
	}

	public function execute( InputInterface $input, OutputInterface $output ) {
		$output->write('Loading companies list... ');

		$xget = (new Xget(new Client()))
			->setUrl('https://techcrunch.com/unicorn-leaderboard/');

		$config = [
			'unicorns' => [
				'name' => '//tbody[@class="unicorns"]/tr[@class="unicorn"]/td/table/tr/td[@class="company-name"]/a[@class="block bold"]/text()',
				'link' => '//tbody[@class="unicorns"]/tr[@class="unicorn"]/td/table/tr/td[@class="company-name"]/a[@class="block bold"]/@href',
			]
		];

		$unicorns = $xget->parse($config);
		$list = $unicorns['unicorns'];

		$permalinks = Ar::map($list, function ($item, $id) {
			return [$id => str_replace(['https://www.crunchbase.com/organization/'], '', Ar::get($item, 'link'))];
		});

		$sql = Ar::map($permalinks, function($item) {
			return sprintf('INSERT INTO cbo_unicorns (permalink) VALUES ("%s");', $item);
		});
		echo implode("\n", $sql); exit;

		$output->writeln(
			sprintf(
				'done, %s found',
				Styler::style(count($list), ForegroundColors::LIGHT_GREEN)
			)
		);

		$output->writeln('Starting to parse company pages to gather URLs...');

		$progressBar = new ProgressBar($output, count($list));
		$progressBar->start();

		$noSite = 0;
		foreach ($list as $id => $item) {
			$companyUrl = $item['link'];
			try {
				$client = new Client();
				$result = $client->get('https://www.crunchbase.com/organization/uber#/entity');
				var_dump($result); exit;


				$companySite = Ar::get((new Xget(new Client()))
					->setUrl($companyUrl)
					->parse([ 'url' => '//dl/dt[contains(., "Website")]/following-sibling::dd[1]/a/.' ]), 'url.0');
			} catch(\Exception $e) {
				echo $e->getMessage(); exit;
			}
			var_dump($companySite); exit;
			if (null === $companySite) {
				$noSite++;
			} else {
				$list[$id]['site'] = $companySite;
			}
			$progressBar->advance();
		}
		$output->writeln(
			sprintf(
				'Done, missed %s sites',
				Styler::style($noSite, ForegroundColors::RED)
			)
		);

	}
}
