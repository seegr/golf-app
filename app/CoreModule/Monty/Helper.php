<?php

namespace Monty;

use Nette;
use Nette\Utils\DateTime;
use Nette\Utils\Json;
use Nette\Utils\ArrayHash;
use Nette\Http\Url;
use Monty\Html;


class Helper extends \Monty\Utils {

	use Nette\SmartObject;

	
	const MIME = [
		"pdf" => "application/pdf",
		"jpg" => "image/jpeg",
		"png" => "image/png",
		"gif" => "image/gif",
		"doc" => "application/msword",
		"docx" => "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
		"ppt" => "application/vnd.ms-powerpoint",
		"pptx" => "application/vnd.openxmlformats-officedocument.presentationml.presentation",
		"xls" => "application/vnd.ms-excel",
		"xlsx" => "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
	];

	const MONTHS = [
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
		];

	const DAYS = [
			1 => [
				"short" => "Po",
				"label" => "Pondělí"
			],
			2 => [
				"short" => "Út",
				"label" => "Úterý"
			],
			3 => [
				"short" => "St",
				"label" => "Středa"
			],
			4 => [
				"short" => "Čt",
				"label" => "Čtvrtek"
			],
			5 => [
				"short" => "Pá",
				"label" => "Pátek"
			],
			6 => [
				"short" => "So",
				"label" => "Sobota"
			],
			7 => [
				"short" => "Ne",
				"label" => "Neděle"
			],
		];

	public static function getMonths() {
		return self::MONTHS;
	}

	public function getMonth($month) {
		return self::MONTHS[$month];
	}

	public static function getDaysArr($short = false) {
		$arr = [];

		foreach (self::DAYS as $dayN => $day) {
			if ($short) {
				$key = $day["short"];
			} else {
				$key = $dayN;
			}

			$arr[$key] = $day["label"];
		}

		return $arr;
	}

	public static function getDay($num) {
		return self::DAYS[$num];
	}

	public static function getWeek() {
		$time = new \DateTime;

		$days = [];
		for ($i = 1; $i <= 7; $i++) {
			$dayNo = $time->format("N");
			$timestamp = $time->getTimestamp();

			$days[$dayNo]["label"] = strftime("%A", $timestamp);
			$days[$dayNo]["short"] = strftime("%a", $timestamp);

			$time->add(new \DateInterval("P1D"));
		}

		ksort($days);

		return ArrayHash::from($days);
	}

	public static function getFileExt($fileName) {
		$parts = explode(".", $fileName);

		return strtolower(end($parts));
	}

	public static function getFileName($fileFullName, $webalize = true) {
		$parts = explode(".", $fileFullName);
		$justName = array_pop($parts);
		$justName = implode($parts);

		if ($webalize) {
			return Strings::webalize($justName);
		} else {
			return $justName;
		}
	}

	public static function getRandomFileName($filename, $lenght = 20, $chars = "A-Za-z") {
		$ext = self::getFileExt($filename);
		$randomName = Random::generate($lenght, $chars);

		return $randomName . "." . $ext;
	}

	public static function getMimeString($mimes) {
		$str = "";
		foreach ($mimes as $mime) {
			$str .= self::MIME[$mime];
			if ($mime != end($mimes)) {
				$str .= ",";
			}
		}

		return $str;
	}

	public static function camelToDash($str) {
		return strtolower(preg_replace('/([a-zA-Z])(?=[A-Z])/', '$1-', $str));	
	}

	public static function generateUniqueFileName($file, $prefix = null) {
		#\Tracy\Debugger::barDump($file, "file - generateUniqueFileName");
		$origName = $file->getName();
		$origNameArr = explode(".", $origName);
		$ext = end($origNameArr);
		unset($origNameArr[count($origNameArr)-1]);
		$fileName = implode(".", $origNameArr);

		$name = "";
		if ($prefix) $name .= $prefix;

		#\Tracy\Debugger::barDump($file, "file");
		$fileInfo = new \SplFileInfo($file);
		#\Tracy\Debugger::barDump($file, "file splinfo");
		$pathInfo = pathinfo($file);
		#\Tracy\Debugger::barDump($pathInfo, "pathInfo");

		$name .= $fileName . "_" . uniqid() . "." . $ext;

		return $name;
	}

	public static function shorten($string, $length, $rawEncode = false) {
		\Tracy\Debugger::barDump($string, "string");
		$length = $length - 3; // because of three dots

		if (strlen($string) >= $length) {
			$str = $rawEncode ? substr($string, 0, $length) : mb_substr($string, 0, $length);
		    $str .= "...";
		    \Tracy\Debugger::barDump($str, "str");
		    return $str;
		}
		else {
		    return $string;
		}
	}

	public static function getIntervalLabel($start, $end, $times = true) {
		$parts = ["start", "end"];
		$_l = Html::el("span");
		$_l->class[] = "interval-label";
		$_sep = Html::el("span class='separator'")->setText("-");
		$_from = Html::el("span class='from'")->setText("od");
		$_to = Html::el("span class='to'")->setText("do");

		$spans = [];
		$datetimes = [];
		foreach ($parts as $part) {
			// \Tracy\Debugger::barDump($part, "part");
			// \Tracy\Debugger::barDump($$part, "part");
			$letter = $part[0];

			$span = "_" . $letter;
			$dateSpan = "_" . $letter . "Date";
			$timeSpan = "_" . $letter . "Time";
			$dateVar = $letter . "Date";
			$timeVar = $letter . "Time";

			$$dateVar = $$part->format("j.n.Y");
			$$timeVar = $$part->format("H:i");

			${$span} = Html::el("span");
			${$span}->class[] = $part;
			${$dateSpan} = Html::el("span class='date'")->setText(${$dateVar});
			${$timeSpan} = Html::el("span class='time'")->setText(${$timeVar});
		}

		$_l->addHtml($sDate);
		if ($times) $_l->addHtml($_sTime);
		$_l->addHtml($_sep);
		$_l->addHtml($eDate);
		if ($times) $_l->addHtml($_eTime);
		// if ($sDate == $eDate) { //one day
		// 	$_l->addHtml($_sTime)->addHtml($_sep)->addHtml($_eTime);
		// } else {
		// 	if ($date == $sDate) {
		// 		$_l->addHtml($_from)->addHtml($_sTime);
		// 	} elseif ($date == $eDate) {
		// 		$_l->addHtml($_to)->addHtml($_eTime);
		// 	} else {
		// 		$_l->setText("celý den");
		// 	}
		// }

		return $_l;
	}

	public static function isSameDay($start, $end) {
		$start = $start->format("j.n.Y");
		$end = $end->format("j.n.Y");

		return $start == $end ? true : false;
	}

	public static function isWholeDay($start, $end) {
		if (self::isSameDay($start, $end)) {
			// \Tracy\Debugger::barDump(1);
			$start = $start->format("H:i");
			$end = $end->format("H:i");

			return $start == "00:00" && $end == "23:59" ? true : false;
		} else {
			// \Tracy\Debugger::barDump(2);
			return false;
		}
	}

	public static function priceFormat($price) {
		$wrap = Html::el("span");
		$wrap->class[] = "price-wrap";

		$p = Html::el("span");
		$p->class[] = "price";
		$p->setText(number_format($price, null, null, " "));

		$cur = Html::el("span");
		$cur->class[] = "currency";
		$cur->setText("Kč");

		$wrap->addHtml($p)->addHtml(" ")->addHtml($cur);;

		return $wrap;
	}

	public static function formatPrice($price) {
		return self::priceFormat($price);
	}

	public static function getPostMaxSize($str = false) {
	  static $max_size = -1;

	  if ($max_size < 0) {
	    // Start with post_max_size.
	    $post_max_size = self::parse_size(ini_get('post_max_size'));
	    if ($post_max_size > 0) {
	      $max_size = $post_max_size;
	    }

	    // If upload_max_size is less, then reduce. Except if upload_max_size is
	    // zero, which indicates no limit.
	    $upload_max = self::parse_size(ini_get('upload_max_filesize'));
	    if ($upload_max > 0 && $upload_max < $max_size) {
	      $max_size = $upload_max;
	    }
	  }

		if (!$str) {
			return $max_size;
		} else {
			return (int)round($max_size / 1000000);
		}
	}

	public static function parse_size($size) {
	  $unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
	  $size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
	  if ($unit) {
	    // Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
	    return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
	  }
	  else {
	    return round($size);
	  }
	}

	public static function isImage($path) {
		// \Tracy\Debugger::barDump($path, "path");
		return file_exists($path) && filesize($path) && exif_imagetype($path) ? true : false;
	}

	public static function getTimeDiffString($arg1, $arg2 = null) {
		// \Tracy\Debugger::barDump($arg1, "arg1");
		// \Tracy\Debugger::barDump($arg2, "arg2");
		$wrap = Html::el("span class=time-diff");

		if ($arg1 && $arg2) {
			$int = $arg1->diff($arg2);
		} else {
			if ($arg1 instanceOf \DateInterval) {
				$int = $arg1;
			} else {
				$dt = new \DateTime;
				$int = $dt->diff($arg1);
			}
		}

		\Tracy\Debugger::barDump($int, "int");

		$text = "Před ";
		if (!$int->d && !$int->h && !$int->i) {
			$text .= "chvílí";
		} else if (!$int->d && !$int->h) {
			if ($int->i > 1) {
				$text .= $int->i . " minutami";
			} else {
				$text .= "minutou";
			}
		} else if (!$int->d) {
			if ($int->h > 1) {
				$text .= $int->h . " hodinami";
			} else {
				$text .= "hodinou";
			}
		} else {
			if ($int->d > 1) {
				if (!$int->m) {
					$text .= $int->d ." dny";
				} else {
					$text = $arg1;
				}
			} else {
				$text = "Včera";
			}
		}
		
		// $wrap->addHtml($text);
		// \Tracy\Debugger::barDump($text, "text");

		$wrap->addHtml("<span class='time-diff-number'>" . $text . "</span>");

		return $wrap;
	}

	public static function sortAssocArray($arr, $key, $sort = "asc") {
		$cols = array_column($arr, $key, "id");
		\Tracy\Debugger::barDump($cols, "cols");

		$sort = strtoupper($sort);
		$sort = $sort == "ASC" ? SORT_ASC : SORT_DESC;

		array_multisort($cols, $sort, $arr);

		return $arr;
	}

	public static function stripHtml($html) {
		return html_entity_decode(strip_tags($html));
	}

	public static function getDays($shorts = null) {
		$days = parent::getDays();

		$arr = [];

		foreach ($days as $num => $day) {
			$arr[$shorts ? $day["short"] : $num] = $day["label"];
		}

		return $arr;
	}

	public static function getObjectClassName($obj) {
		$path = explode('\\', get_class($obj));
		return array_pop($path);
	}

	public static function isPeriodConcur($scopeStart, $scopeEnd, $eventStart, $eventEnd) {
		// "repeat IS NULL AND start >= ? AND start <= ?" => [$start, $end],	//** zacatek akce v intervalu konec za nim
		// "repeat IS NULL AND end >= ? AND end <= ?" => [$start, $end],		//** konec akce v intervalu zacatek pred nim
		// "repeat IS NULL AND start <= ? AND end >= ?" => [$start, $end],		//** zacatek akce pred intervalem konec za intervalem
		// "repeat IS NULL AND start >= ? AND end <= ?" => [$start, $end],		
	}

	public static function getDateRangeInterval($s, $e, $interval = "1 day") {
		$s = is_object($s) ? $s : new DateTime($s);
		$e = is_object($e) ? $e : new DateTime($e);

		$interval = \DateInterval::createFromDateString($interval);
		$period = new \DatePeriod($s, $interval, $e);

		return $period;
	}

	public static function merge(&$first, $second, $force = null)
	{
		$first = $first ? $first : [];
		$second = $second ? $second : [];

		if (is_array($first) && is_array($second)) {
			// \Tracy\Debugger::barDump(1);
			$res = $first + $second;
		} elseif (self::isJson($first) && self::isJson($second)) {
			// \Tracy\Debugger::barDump(2);
			$arr1 = Json::decode($first);
			$arr2 = Json::decode($second);

			$res = $arr1 + $arr2;
		} elseif (is_array($first) && self::isJson($second)) {
			// \Tracy\Debugger::barDump(3);
			$res = $arr1 + Json::decode($second);
		} elseif (self::isJson($first) && is_array($second)) {
			// \Tracy\Debugger::barDump(4);
			$arr1 = Json::decode($first);

			$res = Json::encode($arr1 + $second);
		}

		if (!$force) {
			$first = $res;
		} else {
			if ($force == "json" && is_array($res)) {
				$res = Json::encode($res);
			} elseif ($force == "array" && self::isJson($res)) {
				$res = Json::decode($res);
			}

			$first = $res;
		}
	}

	public static function isJson($str)
	{
		try {
			Json::decode($str);

			// \Tracy\Debugger::barDump("is Json");
			return true;
		} catch (\Nette\Utils\JsonException $e) {
			return false;	
		}
	}

	public static function explodeRoute($route)
	{
		$path = trim($route, ":");
		$path = explode(":", $path);
		// \Tracy\Debugger::barDump($path, "exp path");

		$action = end($path);
		array_pop($path);
		$presenter = implode(":", $path);

		// \Tracy\Debugger::barDump($presenter, "presenter");
		// \Tracy\Debugger::barDump($action, "action");
		return [
			"presenter" => $presenter,
			"action" => $action
		];
	}

	public static function getUrl()
	{
		$protocol = stripos($_SERVER['SERVER_PROTOCOL'],'https') === 0 ? 'https://' : 'http://';
		$url = new Url($protocol . $_SERVER["HTTP_HOST"]);

		return $url;
	}

	public static function getFilesArray($dir) {
		$files = scandir($dir);

		unset($files[0], $files[1]);

		return $files;
	}

	public static function arrayHashToArray($arrHash) {
		if (is_array($arrHash)) return;

		return json_decode(json_encode($arrHash), true);
	}

	public static function filterArrayByKeys($arr, $keys)
	{
		$arr = !is_array($arr) ? self::arrayHashToArray($arr) : $arr;
		$keys = !is_array($keys) ? self::arrayHashToArray($keys) : $keys;

		return array_intersect_key($arr, array_flip($keys));
	}

	public static function stringSplit($str, $len = 1) {
	    $arr		= [];
	    $length 	= mb_strlen($str, 'UTF-8');

	    for ($i = 0; $i < $length; $i += $len) {

	        $arr[] = mb_substr($str, $i, $len, 'UTF-8');

	    }

	    return $arr;
	}

}