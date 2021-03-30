<?php

namespace Monty\Calendar;

use Nette;
use Nette\Utils\Html;


class Event {

	use Nette\SmartObject;

	public $start;
	public $end;

	public $label;
	public $title;
	public $type;
	public $id;
	public $color;
	public $class = ["event", "d-block"];
	public $eventEl;
	public $link;
	public $data = [];
	public $times_labels;
	public $date_start;
	public $date_end;
	protected $Calendar;
	public $showDayTooltip;


	public function __construct($label, $DateTimeStart, $DateTimeEnd, $type = null, $id = null) {
		$this->label = $label;
		$this->start = $DateTimeStart;
		$this->end = $DateTimeEnd;
		$this->type = $type;
		$this->id = $id;

		$this->setTimeLabel();
		$this->date_start = $DateTimeStart->format("Y-m-d");
		$this->date_end = $DateTimeEnd->format("Y-m-d");

		// $this->setRender($this);

		return $this;
	}

	public function setTitle($title) {
		// \Tracy\Debugger::barDump("set title", $title);
		if (is_array($title)) {
			$title = implode(", ", $title);
		}

		$this->title = $title;

		// $this->setRender($this);

		return $this;
	}

	public function setColor($color) {
		$this->color = $color;

		$this->setRender($this);

		return $this;
	}

	protected function setTimeLabel() {
		$this->times_labels["start"] = $this->start->format("H:i");
		$this->times_labels["end"] = $this->end->format("H:i");
		$this->times_labels["interval"] = $this->start->format("H:i") . " - " . $this->end->format("H:i");
	}

	public function addClass($class) {
		$this->class[] = $class;

		$this->setRender($this);

		return $this;
	}

	public function setLink($link) {
		$this->link = $link;

		$this->setRender($this);

		return $this;
	}

	public function getEventHtml() {
		$this->setRender();

		return $this->eventEl;
	}

	public function setRender($event = null) {
		$event = $event ? $event : $this;
		// \Tracy\Debugger::barDump($this, "event");
		
		if ($this->link) {
			$eventEl = Html::el("a");
		} else {
			$eventEl = Html::el("div");
		}

		$eventEl->setText($this->label);
		$eventEl->class($this->class);

		$eventEl->class[] = $this->link ? "link" : null;

		/*if ($this->color) {
			$eventEl->addAttributes(["style" => "background-color: " . $this->color]);
		}
		$eventEl->setText($this->label);
		if ($this->title) {
			$eventEl->addAttributes(["title" => $this->title]);
		}*/

		$eventEl->addAttributes([
			"style" => ($this->color) ? "background-color:" . $this->color : null,
			"href" => $this->link
		]);

		$this->title = $this->title ? $this->title : $this->label;
		if ($this->title) {
			$eventEl->addAttributes([
				"data-toggle" => "tooltip",
				"title" => $this->title,
				"data-placement" => "top"
			]);
		}

		$this->eventEl = $eventEl;
	}

	public function setCalendar($calendar) {
		$this->Calendar = $calendar;

		return $this;
	}

	public function getEventEl() {
		return $this->eventEl;
	}

	public function addData($item, $value) {
		return $this->data[$item] = $value;
	}

	public function addType($type) {
		return $this->type = $type;
	}

}