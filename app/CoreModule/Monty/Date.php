<?php

namespace Monty;

use DatePeriod;
use DateInterval;
use Nette\Utils\ArrayHash;


class Date extends Today {

	public function getMonthInterval() {
		return $this->monthPeriod;
	}

}