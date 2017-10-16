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

class DreamforceSponsorsCommand extends Command
{
	protected $url = '';

	public function configure() {
		$this->setName('dreamforce_sponsors')
			 ->setAliases(['ds'])
			 ->addOption(
				 'out',
				 'o',
				 InputOption::VALUE_OPTIONAL,
				 'Resulting CSV file name',
				 'dreamforce_sponsors.csv'
			 );
	}

	public function execute( InputInterface $input, OutputInterface $output ) {
		$output->write('Getting sponsors links... ');

		$links = Xget::getInstance()
			->setUrl('https://www.salesforce.com/dreamforce/expo/' )
			->parse([
				'links' => '//li[@class="sponsorRow"]/div/a/@href'
			])['links'];

		if (0 === count($links)) {
			$output->writeln('got 0, exiting!' );
		}

		$output->writeln(sprintf(
			'%s found',
			Styler::style(count($links), [ForegroundColors::GREEN])
		));

		$output->writeln('Loading universities... ');
		$progressBar = new ProgressBar($output, count($links));
		$progressBar->start();

		$total = [];
		foreach ($links as $link)
		{
			$url = 'https://www.salesforce.com' . $link;
			$data = Xget::getInstance()
				->setUrl($url)
				->parse([
					'sponsor' => [
						'@' => '//div[@class="sponsor-basic-detail row"]/div',
						'name' => '//h4/.',
						'url' => '//ul[@class="Api_Data"]/li[@class="Number_Two"]/a/.'
					]
				]);
			$total[] = $data['sponsor'][0];
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
