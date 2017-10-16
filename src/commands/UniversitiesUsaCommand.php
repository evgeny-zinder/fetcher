<?php

namespace Datanyze\fetcher\commands;


class UniversitiesUsaCommand extends UniversitiesCommand
{
	protected $url = 'http://univ.cc/search.php?dom=edu&key=&start=';
	protected $commandName = 'universities_usa';
	protected $commandAliases = ['univ_usa', 'uu'];
	protected $defaultFilename = 'universities_usa.csv';
}
