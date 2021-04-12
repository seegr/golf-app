<?php

namespace Monty;

use Nette;
use Nette\Strings;
use DateTime;

use Monty\Html;


class Filters {

	use Nette\SmartObject;


	public static function timeBefore() {
		return "hovno :)";
	}

	public static function timeLeft($DateTime, $string = true) {
		$today = new Datetime;
		$dayToday = $today->format("j");
		$dayDate = $DateTime->format("j");
		#\Tracy\Debugger::barDump($dayToday, "dayToday");
		#\Tracy\Debugger::barDump($dayDate, "dayDate");


		$diff = $today->diff($DateTime);
		#\Tracy\Debugger::barDump($diff, "diff");

		$daysLeft = $diff->days;
		
		$str = $daysLeft;

		if ($string) {
			$str = "za " . $daysLeft;
			if ($daysLeft >= 5) {
				$str .= " dnů";
			} elseif ($daysLeft < 5 && $daysLeft > 1) {
				$str .= " dny";
			} elseif ($daysLeft == 1 || ($daysLeft == 0 && $dayToday != $dayDate)) {
				$str = "zítra";
			} else {
				$str = "dnes";
			}

			return $str;
		} else {
			return $daysLeft;
		}
	}

	public static function price($num, $curr = "Kč") {
		$num = str_replace(" ", "", $num);
		if (!is_numeric($num)) return $num;

    	$el = Html::el("span class=price");
    	$numEl = Html::el("span class=num");
    	$curEl = Html::el("span class=currency");

    	$numEl->setText(number_format($num, 0, ",", " "));
    	$curEl->setText(" " . $curr);

    	$el->addHtml($numEl)->addHtml($curEl);

    	return $el;
	}

	public static function tel($num, $prefix = null) {
		// \Tracy\Debugger::barDump($num, "num");
		$num = str_replace(" ", "", $num);
		// $num = str_replace("00", "+", $num);
		// \Tracy\Debugger::barDump($num, "num");
		$el = Html::el("span class=tel");
		$preEl = Html::el("span class=prefix");
		$numEl = Html::el("span class=num");

		$len = strlen($num);

		// \Tracy\Debugger::barDump($num, "num");

		// \Tracy\Debugger::barDump(strpos($num, "00"));
		// \Tracy\Debugger::barDump(strpos($num, "+"));
		if (strpos($num, "+") === 0 || strpos($num, "00") === 0) {
			\Tracy\Debugger::barDump("with prefix");
			if (strpos($num, "00") === 0) {
				$num = str_replace("00", "+", $num);
			}
			$pref = substr($num, 0, 4);
			$num = substr($num, 4);
			$preEl->setText($pref);
			\Tracy\Debugger::barDump($num, "num");
			$numEl->setText(number_format($num, 0, "", " "));
			$el->addHtml($preEl . " ")->addHtml($numEl);
		} else {
			\Tracy\Debugger::barDump("no prefix");
			if ($prefix) {
				$preEl->setText($prefix);
				$el->addHtml($preEl . " ");
			}
			$numEl->setText(number_format($num, 0, "", " "));
			$el->addHtml($numEl);
		}

		return $el;
	}

	public static function removeTables($string) {
		\Tracy\Debugger::barDump($string, "string");
		$pattern[] = '/<table(.*)<\/table>/iUs';
		$replace[] = '';

		$replaced = preg_replace($pattern, $replace, $string);
		\Tracy\Debugger::barDump($replaced, "replaced");

		return $replaced;
	}

	public static function dateTimeInterval($start, $end = null, $time = true, $showYear = true, $showDayStr = false) {
		if (!$start) return;

		\Tracy\Debugger::barDump($time, "time");
		\Tracy\Debugger::barDump($showYear, "showYear");

		if ($end) {
			$endEl = Html::el("span");
			#$endEl->setHtml($end->format("j.n.Y H:i"));
		}

		$interval = Html::el("span class='time-interval'");

		$startEl = Html::el("span class='datetime-start'");
		$startDateEl = self::dateEl($start, $showYear);
		$startTimeEl = self::timeEl($start);

		if (!$end) {
			$startEl->addHtml($startDateEl);
			$startEl->addHtml("&nbsp;");
			if ($time) {
				$startEl->addHtml($startTimeEl);
			}
			$interval->addHtml($startEl);
		} else {
			$startEl->addHtml($startDateEl);
			$startEl->addHtml("&nbsp;");
			if ($time) {
				$startEl->addHtml($startTimeEl);
			}
			$startEl->addHtml("&nbsp;");
			$interval->addHtml($startEl);
			$endEl = Html::el("span class='datetime-end'");
			if ($start->format("j.n.Y") != $end->format("j.n.Y")) {
				$endEl->setHtml("<span>&ndash;</span>");
				$endEl->addHtml("&nbsp;");
			}

			$endDateEl = self::dateEl($end, $showYear);
			$endTimeEl = self::timeEl($end);
			// \Tracy\Debugger::barDump($start->format("j.n.Y"));
			// \Tracy\Debugger::barDump($end->format("j.n.Y"));
			if ($start->format("j.n.Y") != $end->format("j.n.Y")) {
				$endEl->addHtml($endDateEl);
			} else {
				if ($time) {
					$interval->addHtml("<span class='mx-0'>&ndash;</span>");
				}
			}
			$endEl->addHtml("&nbsp;");
			if ($time) {
				$endEl->addHtml($endTimeEl);
			}
			$interval->addHtml($endEl);
		}

		if ($showDayStr) {
			if ($start->format("j.n.Y") != $end->format("j.n.Y")) {
				$startEl->addHtml(self::dayEl($start) . "&nbsp;");
				$endEl->addHtml("&nbsp;" . self::dayEl($end));
			} else {
				$interval->addHtml("&nbsp;" . self::dayEl($start));
			}
		}

		return $interval;
	}

	public static function dateEl($datetime, $showYear = true) {
		if (!$datetime) return;

    	$el = Html::el("span class='date'");
    	$format = $showYear ? "j.n.Y" : "j.n.";
    	$el->setText($datetime->format($format));

    	return $el;
    }

	public static function timeEl($datetime) {
		if (!$datetime) return;

    	$el = Html::el("span class='time'");
    	$el->setText($datetime->format("H:i"));

    	return $el;
    }

    public static function dayEl($datetime) {
    	$el = Html::el("span class='day'");

    	// $str = strftime("%a", $datetime->getTimestamp());
			$day = $datetime->format("N");
			$day = Helper::getDay($day)["short"];
    	$el->setText("(" . $day . ")");

    	return $el;
    }

    public static function datetime($dt)
    {
    	$date = self::dateEl($dt);
    	$time = self::timeEl($dt);

    	$span = Html::el("span class='datetime'");
    	$span->addHtml($date);
    	$span->addHtml("<span class='separator'>&nbsp;</span>");
    	$span->addHtml($time);

    	return $span;
    }
}