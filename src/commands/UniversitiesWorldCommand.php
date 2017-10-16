<?php

namespace Datanyze\fetcher\commands;


class UniversitiesWorldCommand extends UniversitiesCommand
{
	protected $url = 'http://univ.cc/search.php?dom=world&key=&start=';
	protected $commandName = 'universities_world';
	protected $commandAliases = ['univ_world', 'uw'];
	protected $defaultFilename = 'universities_world.csv';
}
