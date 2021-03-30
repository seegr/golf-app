<?php

namespace Monty;

use DateTime;
use DatePeriod;
use DateInterval;
use Nette\Utils\ArrayHash;


class Today {

	public $dt, $weekDay, $day, $month, $year, $stamp, $time;

	public function __construct($date = null) {
		$this->dt = new DateTime($date);

		$this->setAll();

		return $this;
	}

	public function setYear($year) {
		// \Tracy\Debugger::barDump($year, "set year");
		$this->year = $year;

		$this->dt->modify("first day of this month");

		$this->setAll();

		return $this;
	}

	public function setMonth($month) {
		// \Tracy\Debugger::barDump($month, "set month");
		$this->month = $month;

		$this->dt->modify("first day of this month");

		$this->setAll();


		return $this;
	}

	public function setAll() {
		// \Tracy\Debugger::barDump($this->year, "year");
		// \Tracy\Debugger::barDump($this->month, "month");
		// \Tracy\Debugger::barDump($this->dt, "this-dt 1");
		$this->dt = $dt = $this->dt ? $this->dt : new DateTime;

		// if ($this->date) $dt->modify($this->date);
		// \Tracy\Debugger::barDump($dt, "this-dt 2");

		$this->today = new DateTime;

		$this->year = $this->year ? $this->year : $dt->format("Y");
		$this->month = $this->month ? $this->month : $dt->format("n");
		// \Tracy\Debugger::barDump($dt, "this-dt 3");

		// \Tracy\Debugger::barDump("set date");
		// \Tracy\Debugger::barDump($this->year, "year");
		// \Tracy\Debugger::barDump($this->month, "month");
		// \Tracy\Debugger::barDump($dt->format("j"), "day");
		$dt->setDate($this->year, $this->month, $dt->format("j"));
		// \Tracy\Debugger::barDump($dt, "this-dt 4");

		// $this->dt = $dt;

		$this->weekDay = (clone $dt)->format("N");
		$this->day = (clone $dt)->format("j");
		$this->month = (clone $dt)->format("n");
		$this->year = (clone $dt)->format("Y");
		$this->datetime = (clone $dt)->format("j.n.Y H:i");
		$this->time = (clone $dt)->format("H:i");
		$this->stamp = time();

		$this->todayInterval = ArrayHash::from([
			"start" => (clone $dt)->modify("00:00"),
			"end" => (clone $dt)->modify("23:59")
		]);

		$this->daysInMonth = cal_days_in_month(CAL_GREGORIAN, $this->month, $this->year);
		$this->prevMonth = date("n", strtotime($this->year . "-" . $this->month . "-1 -1day"));
		$this->nextMonth = date("n", strtotime($this->year . "-" . $this->month . "-" . $this->daysInMonth . " +1day"));
		$this->daysInNextMonth = cal_days_in_month(CAL_GREGORIAN, $this->nextMonth, $this->year);
		$this->daysInPrevMonth = cal_days_in_month(CAL_GREGORIAN, $this->prevMonth, $this->year);
		$this->nextMonthYear = ($this->month == 12) ? $this->year + 1 : $this->year;
		$this->prevMonthYear = ($this->month == 1) ? $this->year - 1 : $this->year;
		$this->startDate = "1." . $this->month . "." . $this->year;
		$this->endDate = $this->daysInMonth . "." . $this->month . "." . $this->year;

		$start = (new DateTime($this->startDate))->modify("00:00:00");
		$end = (new DateTime($this->endDate))->modify("23:59:59");

		$this->monthPeriod = new DatePeriod($start, DateInterval::createFromDateString('1 day'), $end);

		// \Tracy\Debugger::barDump($this, "date after set");
	}

	public function getTodayInterval() {
		return $this->todayInterval;
	}

	public function getMonthPeriod() {
		return $this->monthPeriod;
	}

	public function getMonthInterval() {
		return $this->getMonthPeriod();
	}

}