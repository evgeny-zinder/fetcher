<?php

namespace Datanyze\fetcher\helpers;


use Datanyze\fetcher\Constants;
use eznio\ar\Ar;

class CsvFormatter
{
	public static function format( array $data ): string {
		return implode("\n", Ar::map($data, function($item, $itemId) {
			if (false === is_array($item)) {
				return $item;
			}
			return [
				$itemId => Constants::CSV_FIELD_QUOTES . implode(
						Constants::CSV_FIELD_QUOTES . Constants::CSV_FIELD_SEPARATOR. Constants::CSV_FIELD_QUOTES,
						$item
					) . Constants::CSV_FIELD_QUOTES
			];
		}));
	}
}
