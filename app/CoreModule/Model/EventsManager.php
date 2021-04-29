<?php

namespace App\CoreModule\Model;

use Nette\Utils\ArrayHash;
use Nette\Utils\Image;
use Nette\Utils\FileSystem;
use Nette\Utils\Strings;
use Nette\Utils\DateTime;
use Nette\Utils\Json;

use Monty\Helper;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

class EventsManager extends ContentsManager
{

  const TABLE_CONTENT_EVENTS_DATES = "contents_events_dates",
    TABLE_CONTENT_EVENTS_PERSONS = "contents_events_persons",
    EVENT_REGISTRATION = [
      "event" => "Na všechny termíny",
      "dates" => "Rozdělená po termínech"
    ];



  public function getEventsByDate()
  {
    return $this->getEvents(true);
  }

  public function getEvents($byDate = false) {
    if ($byDate) {
      return $this->db->table(self::TABLE_CONTENT_EVENTS_DATES)->where("content.type.short", "event");
    } else {
      return $this->getContents()->where("type.short", "event");
    }
  }

  public function getEventsDates()
  {
    return $this->db->table(self::TABLE_CONTENT_EVENTS_DATES);
  }

  public function getEventDates($id) {
    return $this->getEventsDates()->where("content", $id);
  }

  public function getEventDate($id) {
    return $this->getEventsDates()->get($id);
  }

  public function saveEventDate($v)
  {
    $v = is_array($v) ? ArrayHash::from($v) : $v;
    $content = $this->getContent($v->content);

    $data = [
      "content" => $content->id,
      "start" => new DateTime($v->start),
      "end" => new DateTime($v->end)
    ];

    if (!empty($v->id)) {
      $id = $v->id;

      $this->getEventDate($id)->update($data);
    } else {
      $id = $this->getEventsDates()->insert($data);
    }

    return $id;
  }

  public function getEventMinDate($id) {
    return $this->getEventDates($id)->min("start");
  }

  public function getEventMaxDate($id) {
    return $this->getEventDates($id)->max("end");
  }

  public function getEventDatesInterval($id) {
    return [$this->getEventMinDate($id), $this->getEventMaxDate($id)];
  }

  public function getEventDateCol($type) // start|end
  {
    $col = ":" . self::TABLE_CONTENT_EVENTS_DATES . "." . $type;

    return $col;
  }

  public function getEventStartCol()
  {
    return $this->getEventDateCol("start");
  }

  public function getEventEndCol()
  {
    return $this->getEventDateCol("end");
  }

  public function getEventsTypeInPeriod($type, $start, $end, $onlyActive = false) {
    return $this->getEventsInPeriod($start, $end, $type, $onlyActive);
  }

  public function getEventsInPeriod($start, $end = null, $type = null, $onlyActive = false) {
    $end = $end ? $end : $start;

    if (!is_object($start)) $start = new DateTime($start);
    if (!is_object($end)) $end = new DateTime($end);

    $sel = $this->getEvents();

    if ($type) {
      // $type = $this->getEventType($type);
      $sel->whereOr([
        "type.short" => $type
      ]);
    }

    if ($onlyActive) $sel->where("active", true);

    // \Tracy\Debugger::barDump($start, "getEventsInPeriod start");
    // \Tracy\Debugger::barDump($end, "getEventsInPeriod end");

    $timeIntervalCond = self::getIntervalCond($start, $end, "TIME");
    // \Tracy\Debugger::barDump($timeIntervalCond, "timeIntervalCond");

    $startCol = $this->getEventStartCol();
    $endCol = $this->getEventEndCol();

    $orConds = [
      //	 $start       $end		//
      //		|...........|		//
      // 		|.....<-----|-----> //
      //<-----|----->.....|		//
      //<-----|...........|----->	//
      //		|.<------->.|		//

      "$startCol >= ? AND $startCol <= ?" => [$start, $end],	//** zacatek akce v intervalu konec za nim
      "$endCol >= ? AND $endCol <= ?" => [$start, $end],		//** konec akce v intervalu zacatek pred nim
      "$startCol <= ? AND $endCol >= ?" => [$start, $end],		//** zacatek akce pred intervalem konec za intervalem
      "$startCol >= ? AND $endCol <= ?" => [$start, $end],		//** zacatek akce v intervalu konec v intervalu
    ];

    // \Tracy\Debugger::barDump($orConds, "orConds");
    $sel->whereOr($orConds);


    $sel->select("events.*");

    // \Tracy\Debugger::barDump($sel, "sel");
    return $sel;
  }

  public function getDayEvents($date = null) {
    $date = $date ? new DateTime($date) : new DateTime;

    $start = $date->modify("00:00:00");
    $end = (clone $date)->modify("23:59:00");

    \Tracy\Debugger::barDump($start, "start");
    \Tracy\Debugger::barDump($end, "end");

    $events = $this->getEventsInPeriod($start, $end)->order("TIME(start)");

    return $events;
  }

  public function getMonthEvents($month = null, $year = null, $type = null) {
    $today = new DateTime;
    $today->modify("first day of this month");
    if ($month) $today->modify($month . " month");
    if ($year) $today->modify($year . " year");

    $start = $today;
    $start->modify("00:00:00");

    $end = clone $start;
    $end->modify("23:59:00")->modify("last day of this month");

    \Tracy\Debugger::barDump($start, "getMonthEvents start");
    \Tracy\Debugger::barDump($end, "getMonthEvents end");

    $diff = $start->diff($end);
    \Tracy\Debugger::barDump($diff, "diff");

    $interval = \DateInterval::createFromDateString('1 day');
    $period = new \DatePeriod($start, $interval, $end);

    $arr = [];
    foreach ($period as $dt) {
      \Tracy\Debugger::barDump($dt, "period dt");
      // $dtStart = $dt;
      // $dtEnd = clone($dt)->modify("23:59:00");

      // $events = $this->getEventsInPeriod($dtStart, $dtEnd)->fetchAll();
      $events = $this->getDayEvents($dt);
      if ($type) {
        \Tracy\Debugger::barDump($type, "type");
        $type = $this->getEventType($type);
        \Tracy\Debugger::barDump($type, "type");
        $events->where("type.short", $type->short);
      }
      $events = $events->fetchAll();
      $events = array_values($events);
      \Tracy\Debugger::barDump($events, "events");
      // $arr[] = $dt;
      if (count($events)) $arr[$dt->format("Y-m-d")] = $events;
    }

    // $arr = ArrayHash::from($arr);

    \Tracy\Debugger::barDump($arr, "events arr");
    return $arr;
  }

  public function getFutureEvents($noDate = false, $byDates = false) {
    $today = new DateTime;

    if (!$byDates) {
      $events = $this->getEvents();

      $startCol = $this->getEventStartCol();
      $endCol = $this->getEventEndCol();
    } else {
      $events = $this->getEventsByDate();
      $startCol = self::TABLE_CONTENT_EVENTS_DATES . ".start";
      $endCol = self::TABLE_CONTENT_EVENTS_DATES . ".end";
      $datesTable = self::TABLE_CONTENT_EVENTS_DATES;
      $events->select("content.*, $datesTable.*, content.id AS event_id");
    }

    $conds = [
      "$startCol >= ? OR ($startCol <= ? AND $endCol >= ?)" => [(clone $today)->modify("00:00"), $today, $today]
    ];

    if ($noDate) {
      $conds[$startCol] = null;	
    }

    $events->whereOr($conds);

    return $events;
  }

  public function getEvent($id): ActiveRow
  {
    return $this->getContent($id);
  }
  
  public function getEventsPersons(): Selection
  {
    return $this->db->table(self::TABLE_CONTENT_EVENTS_PERSONS);
  }

  public function getEventDatePersons($id): Selection
  {
    return $this->getEventsPersons()->where("date", $id)->select("record.*");
  }

  public function getEventPersons($id, $date = null): Selection
  {
    $sel = $this->getEventsPersons()->where("event", $id)->select("record.*");

    if ($date) $sel->where("date", $date);

    return $sel;
  }

  public function getEventPerson($id): ActiveRow
  {
    return $this->getEventsPersons()->get($id);
  }

  public function insertEventPerson(int $event, int $record, string $role, int $date = null): int
  {
    $this->getEventsPersons()->insert([
      "event" => $event,
      "record" => $record,
      "role" => $role,
      "date" => $date ? $date : null,
      "state" => $this->getState("unconfirmed")->id
    ]);

    return $record;
  }

	public function getEventRegSummary($id, $date = null, $forPublic = false): ArrayHash
  {
    $event = $this->getEvent($id);
		\Tracy\Debugger::barDump($id, "id");

		$all = $this->getEventPersons($id, $date)->where("record.active", true);
		// \Tracy\Debugger::barDump($all, "submitters");

		$part = (clone $all)->where("role", "part");
		$sub = (clone $all)->where("role", "sub");
		$allCount = count($all);
		$partCount = count($part);
		$subCount = count($sub);
		$spots = $event->reg_part + $event->reg_sub;
		$isFull = $spots ? ($spots - $allCount <= 0 ? true : false) : false;
		$partLimit = $event->reg_part;
		$subLimit = $event->reg_sub;

		if ($forPublic && ($partCount < $event->reg_part && $subCount)) {
			$partDiff = $partLimit - $partCount;

			if ($partDiff >= $subCount) {
				$partCount += $subCount;
				$subCount = 0;
			} else {
				$partCount += $partDiff;
				$subCount -= $partDiff;
			}
		}

		$data = [
      "partLimit" => (int) $partLimit,
      "subLimit" => $subLimit,
      "spots" => $spots,
      "all" => $all,
      "part" => $part,
      "sub" => $sub,
      "allCount" => $allCount,
      "personsCount" => $allCount,
      "partCount" => $partCount,
      "subCount" => $subCount,
      "partPercent" => $spots ? ($partCount / $spots) * 100 : null,
      "subPercent" => $spots ? ($subCount / $spots) * 100 : null,
      "personsPercent" => $spots ? ($allCount / $spots) * 100 : null,
      "partLeft" => $event->reg_part - $partCount,
      "subLeft" => $event->reg_sub - $subCount,
      "isFull" => $isFull,
      "space" => !$isFull,
      "free" => !$isFull,
      "partSpace" => $event->reg_part ? ($event->reg_part - $partCount > 0 ? true : false) : true,
      "subSpace" => $event->reg_sub ? ($event->reg_sub - $subCount > 0 ? true : false) : true
    ];

    bdump($data["personsPercent"], "perspercent");
		if ($data["personsPercent"] <= 50) {
			$color = "success";
		} else if ($data["personsPercent"] < 100) {
			$color = "warning";
		} else {
			$color = "danger";
		}

    $data["spotsColor"] = $color;

		return ArrayHash::from($data);
	}

}