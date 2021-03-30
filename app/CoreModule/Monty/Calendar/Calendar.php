<?php

namespace Monty;

use Nette\Application\UI\Control;
use Nette\Utils\DateTime;
use Nette\Utils\ArrayHash;
use Nette\Database\Context;
use Tracy\Debugger;
use Nette\Application\UI\Form;

use Monty\Calendar\Event;


class Calendar extends BaseControl {

	protected $id;
	private $DateTime;
	private $today;
	private $calendarType;
	private $Date;
	private $labels;
	private $dayLayout;
	private $year;
	private $month;
	private $halfDayLimit = "13:00";
	private $layoutSize = "auto";
	private $eventsTable;
	public $confirmButton = false;
	protected $db;
	protected $eventsHaystack;
	protected $events = [];
	protected $actions;
	protected $months;
	protected $years;
	protected $maxYear = 2030;
	public $onChange;
	public $onDayClick;
	public $dayAction;
	public $eventAction;
	public $holidays;
	#protected $session;
	public $showEvents = true;
	public $calendarClasses;
	protected $remember;
	protected $session;
	public $dayHeight;
	public $dayActionTargetAttr;
	public $eventsTooltips;
	public $dayCallback;
	public $dayCallbackNewWrap;

	#add session -> events - kvuli listovani a zachovani eventu


	public function __construct(Context $db = null) {
		// parent::__construct();

		$this->db = $db;

		$this->setDates();
		$this->setLocale();

		$this->months = $this->getMonths();
		$this->years = $this->getYears();
	}

	public function render($year = null, $month = null) {
		if (!$this->year) $this->year = $year;
		if (!$this->month) $this->month = $month;

		$this->setExtDates();

		if (!$this->calendarType) {
			$this->calendarType = "month";
		}

		if (!$this->dayLayout) {
			$this->dayLayout = "day";
		}

		if ($this->eventsHaystack) {
			$this->sortEvents();
		}

		#\Tracy\Debugger::barDump($this->Date, "Date");

		$this["yearSelectForm"]["year"]->setDefaultValue($this->Date->year);
		$this["monthSelectForm"]["month"]->setDefaultValue($this->Date->month);

		$template = $this->template;
		if ($this->eventsTable) {
			$template->events = $this->db->table($this->eventsTable);
		}
		$template->Date = $this->Date;
		$template->labels = $this->labels;
		$template->dayLayout = $this->dayLayout;
		$template->halfDayLimit = $this->halfDayLimit;
		$template->layoutSize = $this->layoutSize;
		$template->db = $this->db;
		$template->eventsTable = $this->eventsTable;
		$template->confirmButton = $this->confirmButton;
		$template->events = $this->getEvents();
		$template->actions = $this->actions;
		$template->onDayClick = $this->onDayClick;
		$template->months = $this->months;
		$template->years = $this->years;
		$template->dayHeight = $this->dayHeight;
		$template->dayCallback = $this->dayCallback;
		$template->dayCallbackNewWrap = $this->dayCallbackNewWrap;

		#\Tracy\Debugger::barDump($this->getEvents(), "events");

		#\Tracy\Debugger::barDump($this->eventsHaystack, "eventsHaystack");
		#\Tracy\Debugger::barDump($this->getEvents(), "events");
		#\Tracy\Debugger::barDump($this->holidays, "holidays");

		$template->setFile(__DIR__ . "/templates/" . $this->calendarType . ".latte");

		#\Tracy\Debugger::barDump($this->confirmButton, "confirmButton");

		// if ($this->renderToString) {
		// 	// \Tracy\Debugger::barDump($template->getParameters(), "pars");
		// 	return $template->getLatte()->renderToString($templateFile, $template->getParameters());
		// 	// $this->renderToString();
		// } else {
		// 	$template->setFile($templateFile);
		// 	$template->render();
		// }

		return $this->renderIt($template);
	}
	
	/** SETTERS **/
	public function setYear($year) {
		$this->year = $year;
	}

	public function setMonth($month) {
		$this->month = $month;
	}

	public function setHolidays($holidays, $convert = false) {
		if (!$convert) {
			foreach ($holidays as $holiday) {
				if (!isset($this->holidays[$holiday->date->format("Y-m-d")])) {
					$this->holidays[$holiday->date->format("Y-m-d")] = [];
				}
				$this->holidays[$holiday->date->format("Y-m-d")][] = $holiday->title;
			}
		} else {
			$this->holidays = $this->eventsToDateArray($holidays);
		}

		// \Tracy\Debugger::barDump($this->holidays, "holidays");
	}

	public function eventsToDateArray($events, $startCol = "start", $endCol = "end", $titleCol = "title") {
		$arr = [];

		if (!empty($events)) foreach ($events as $event) {
			$interval = new \DateInterval("P1D");
			$period = new \DatePeriod($event->$startCol, $interval, $event->$endCol);

			foreach ($period as $dt) {
				$date = $dt->format("Y-m-d");
				if (!isset($arr[$date])) {
					$arr[$date] = [];
				}
				$arr[$date][] = $event->$titleCol;
			}
		}

		return $arr;
	}

	public function setType($type) {
		$this->calendarType = $type;
	}

	public function setDayLayout($layout) {
		$this->dayLayout = $layout;
	}

	public function setDates() {
		$date = [
			"today" => date("Y-m-d", time()),
			"tYear" => date("Y", time()),
			"tMonth" => date("n", time()),
			"tDay" => date("j", time()),
			"tTimestamp" => strtotime(date("Y-m-d", time()))
		];

		$this->Date = ArrayHash::from($date);
	}

	private function setExtDates() {
		#$template = $this->template;
		#\Tracy\Debugger::barDump($this->month, "month");
		// \Tracy\Debugger::barDump($this, "cal");

		if ($this->remember) {
			$session = $this->getSession();
			// \Tracy\Debugger::barDump($session->year, "sess year");
			// \Tracy\Debugger::barDump($session->month, "sess month");

			$this->year = $session->year ? $session->year : $this->year;
			$this->month = $session->month ? $session->month : $this->month;
			// \Tracy\Debugger::barDump($this->year, "year");
			// \Tracy\Debugger::barDump($this->month, "month");
		}

		if ($this->year) {
			// \Tracy\Debugger::barDump(1);
			$year = $this->year;
		} else {
			// \Tracy\Debugger::barDump(2);
			$year = $this->Date->tYear;
			$this->year = $year;
		}

		if ($this->month) {
			// \Tracy\Debugger::barDump(3);
			$month = $this->month;
		} else {
			// \Tracy\Debugger::barDump(4);
			$month = $this->Date->tMonth;
			$this->month = $month;
		}

		$date = $this->Date;
		$date->year = $year;
		$date->month = $month;
		$date->daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
		$date->prevMonth = date("n", strtotime($date->year . "-" . $date->month . "-1 -1day"));
		$date->nextMonth = date("n", strtotime($date->year . "-" . $date->month . "-" . $date->daysInMonth . " +1day"));
		$date->daysInNextMonth = cal_days_in_month(CAL_GREGORIAN, $date->nextMonth, $year);
		$date->daysInPrevMonth = cal_days_in_month(CAL_GREGORIAN, $date->prevMonth, $year);
		$date->nextMonthYear = ($date->month == 12) ? $date->year + 1 : $date->year;
		$date->prevMonthYear = ($date->month == 1) ? $date->year - 1 : $date->year;
		$date->startDate = "1." . $date->month . "." . $date->year;
		$date->endDate = $date->daysInMonth . "." . $date->month . "." . $date->year;
	}

	private function setLocale() {
		$months = ArrayHash::from([
			1 => "Leden",
			2 => "Únor",
			3 => "Březen",
			4 => "Duben", 
			5 => "Květen",
			6 => "Červen",
			7 => "Červenec",
			8 => "Srpen",
			9 => "Září",
			10 => "Říjen",
			11 => "Listopad",
			12 => "Prosinec"
		]);

		$days = ArrayHash::from([
			1 => "Pondělí",
			2 => "Úterý",
			3 => "Středa",
			4 => "Čtvrtek",
			5 => "Pátek",
			6 => "Sobota",
			7 => "Neděle"
		]);
		$daysShort = ArrayHash::from([
			1 => "Po",
			2 => "Út",
			3 => "St",
			4 => "Čt",
			5 => "Pá",
			6 => "So",
			7 => "Ne"
		]);

		$this->labels = ArrayHash::from([
			"months" => $months,
			"days" => $days,
			"daysShort" => $daysShort
		]);
	}

	public function setLayoutSize($size) {
		$this->layoutSize = $size;

		return $this;
	}

	public function setEventsTable($table) {
		$this->eventsTable = $table;

		return $this;
	}

	public function setConfirmButton($state) {
		$this->confirmButton = $state;

		return $this;
	}

	public function showEvents($state = true) {
		$this->showEvents = $state;

		return $this;
	}

	// public function setMinYear($year) {
	// 	$this->minYear = $year;

	// 	return $this;
	// }

	// public function setMaxYear($year) {
	// 	$this->maxYear = $year;

	// 	return $this;
	// }


	public function addEvent($label, $DateTimeStart, $DateTimeEnd, $type = null, $id = null) {
		$event = new Event($label, $DateTimeStart, $DateTimeEnd, $type, $id);
		$event->setCalendar($this);

		// $event->showDayTooltip = $this->eventsTooltips;

		$this->eventsHaystack[] = $event;

		return $event;
	}

	public function sortEvents() {
		#\Tracy\Debugger::barDump($this->Date, "Date");
		#\Tracy\Debugger::barDump($this->Date->month, "cal month");
		#\Tracy\Debugger::barDump($this->Date->year, "cal year");

		$events = $this->eventsHaystack;
		#\Tracy\Debugger::barDump($events, "events");

		if (!$this->year) {
			throw new \Exception("you have to set calendar year");
		}
		if (!$this->month) {
			throw new \Exception("you have to set calendar month");
		}
		$this->setExtDates();
		$year = $this->Date->year;
		$month = $this->Date->month;

		$endMonthDay = $this->Date->daysInMonth;
		$startMonthTimestamp = strtotime("$year-$month-01 00:00");
		$endMonthTimestamp = strtotime("$year-$month-$endMonthDay 23:59");

		if ($events && count($events) > 0) {
			foreach ($events as $event) {
				#\Tracy\Debugger::barDump($event, "event");
				$startTimestamp = $event->start->getTimestamp();
				$endTimestamp = $event->end->getTimestamp();

				#\Tracy\Debugger::barDump($event->DateTimeStart, "start");
				#\Tracy\Debugger::barDump($event->DateTimeEnd, "end");

				#\Tracy\Debugger::barDump($startTimestamp, "startTimestamp");
				#\Tracy\Debugger::barDump($endTimestamp, "endTimestamp");

				$eventStartDate = $event->start->format("Y-m-d");
				$eventEndDate = $event->end->format("Y-m-d");

				if ($eventStartDate != $eventEndDate) { //** multipledays event
					#\Tracy\Debugger::barDump("multiple days event");
					$daysDiff = $event->start->diff($event->end)->days;
					#\Tracy\Debugger::barDump($daysDiff, "daysDiff");

					$this->events[$eventStartDate][] = $event;
					$i = 0;
					for ($i; $i < $daysDiff; $i++) {
						$date = $event->start->add(new \DateInterval("P1D"))->format("Y-m-d");
						#\Tracy\Debugger::barDump($date, "date in diff");
						$this->events[$date][] = $event;
					}
				} else { //** one day event
					#\Tracy\Debugger::barDump("one day event");
					$date = $event->start->format("Y-m-d");
					$this->events[$date][] = $event;
				}
			}
		}

		/*foreach ($this->events as &$day) {   
			usort($day, function($a, $b) {
			  if ($a->DateTimeStart == $b->DateTimeStart) {
			  	return 0;
			  }

			  return $a->DateTimeStart < $b->DateTimeStart ? -1 : 1;
			});
		}*/
	}

	public function getEvents() {
		if (!$this->events) {
			$this->sortEvents();
		}

		return $this->events;
	}

	public function getDayEvents($date) {
		if (isset($this->events[$date])) {
			return $this->events[$date];
		}
	}

	public function getYear() {
		return $this->year;
	}

	public function getMonth() {
		return $this->month;
	}

	public function getMonths() {
		return \Monty\Helper::getMonths();
	}

	public function getYears() {
		$years = [];

		for ($year = 2018; $year <= $this->maxYear; $year++) {
			$years[$year] = $year;
		}

		return $years;
	}	

	public function getDate() {
		if (!isset($this->Date->daysInMonth)) {
			$this->setExtDates();
		}

		return $this->Date;
	}

	public function getDayAction($date) {
		if ($this->dayAction) {
			// \Tracy\Debugger::barDump($this->dayAction, "dayAction");

			$link = $this->dayAction[0];
			$dayAttr = isset($this->dayAction[1]) ? $this->dayAction[1] : "date";
			$attrs = isset($this->dayAction[2]) ? $this->dayAction[2] : [];

			// \Tracy\Debugger::barDump($attrs, "attrs");
			return $this->presenter->link($link, [$dayAttr => $date] + $attrs);
		}
	}

	public function getEventAction($event) {
		$link = $this->eventAction[0];
		$attrs = $this->eventAction[1];

		if ($attrs) {
			$attrs = [];
			foreach ($this->eventAction[1] as $attrKey => $attr) {
				$attrs[$attrKey] = $event[$attr];
			}
		} else {
			$attrs = ["id" => $event->id];
		}

		return $this->presenter->link($link, $attrs);
	}

	public function addChangeAction($action) {
		$this->actions["change"] = $action;
	}

	public function addDayAction($link, $dayAttr = null, $attrs = null) {
		$this->dayAction = [$link, $dayAttr, $attrs];

		return $this;
	}

	public function addEventAction($link, $attrs = []) {
		$this->eventAction = [$link, $attrs];
	}

	public function setDayAction($link, $dayAttr = null, $attrs = null, $targetAttr = null) {
		$this->addDayAction($link, $dayAttr, $attrs);
		$this->dayActionTargetAttr = $targetAttr;

		return $this;
	}

	public function onChangeCall() {
		if ($this->onChange) foreach ($this->onChange as $call) {
			$call();
		}
	}

	public function countEvents() {
		return count($this->eventsHaystack);
	}

	public function countEventsType($type) {
		$counter = 0;
		if ($this->eventsHaystack) {
			foreach ($this->eventsHaystack as $event) {
				if ($event->type == $type) {
					$counter++;
				}
			}
		}

		return $counter;
	}

	public function isWeekend(DateTime $dt) {
		if ($dt->format("N") > 5) {
			return true;
		}
	}

	public function addHoliday($date, $title) {
		$this->holidays[$date] = $title;
	}

	public function isHoliday($date) {
		if (isset($this->holidays[$date])) {
			return $this->holidays[$date];
		}
	}

	public function setElementsClasses(array $classes) {
		$this->calendarClasses = $classes;

		return $this;
	}

	public function getElementClass($element) {
		return isset($this->calendarClasses[$element]) ? $this->calendarClasses[$element] : null;
	}

	public function getId() {
		return Strings::webalize($this->getParent()->getName()) . "-" . $this->lookupPath();
	}

	public function getSession() {
		return $this->getParent()->getSession($this->getId());
	}

	public function setRemember($s = true) {
		$this->remember = $s;

		return $s;
	}

	public function setDayHeight($height) {
		$this->dayHeight = $height;

		return $this;
	}

	public function setDayCallback($callback, $newWrap = true) {
		$this->dayCallback = $callback;
		$this->dayCallbackNewWrap = $newWrap;

		return $this;
	}

	public function showEventsTooltips($state = true) {
		$this->eventsTooltips = $state;

		return $this;
	}

	public function callDayCallback($day) {
		$callback = $this->dayCallback;

		$dt = new \DateTime($day);

		return $callback($dt);
		// return $this->dayCallback($day);
	}


	//** COMPONENTS **//

	public function createComponentYearSelectForm() {
		$form = new Form;

		$form->addSelect("year", null, $this->years);

		return $form;
	}

	public function createComponentMonthSelectForm() {
		$form = new Form;

		$form->addSelect("month", null, $this->months);

		return $form;
	}


	/** HANDLES **/
	public function handleChangeDate($year, $month) {
		$this->year = $year;
		$this->month = $month;

		if ($this->remember) {
			$id = $this->getId();
			\Tracy\Debugger::barDump($this->getId(), "id");
			$session = $this->getSession();
			$session->year = $year;
			$session->month = $month;
		}

		$this->onChangeCall();

		if ($this->presenter->isAjax()) {
			$this->redrawControl("calendar");
		}
	}

	public function handleOnDayClick($day) {
		\Tracy\Debugger::barDump("click");
		\Tracy\Debugger::barDump($this->onDayClick);
		foreach ($this->onDayClick as $callback) {
			$callback($day);
		}
	}

}