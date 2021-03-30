<?php

namespace Monty;

use Nette;
use Nette\Application\UI\Form;
use Nette\Forms\IControl;
use Nette\Utils\DateTime;
use Nette\Utils\Strings;


class FormValidators {

	use Nette\SmartObject;

    const KEYWORDS_VALIDATION = 'Monty\FormValidators::keywordsValidation';
    const CHECK_START_END_DATETIME = "Monty\FormValidators::checkStartEndDatetime";
    const CHECK_START_END_DATE = "Monty\FormValidators::checkStartEndDate";
    const EVENT_REPEAT_END_OR_REPEATS = "Monty\FormValidators::eventRepeatEndOrRepeats";
    const IS_GREATER_ZERO = "Monty\FormValidators::isNotGreaterZero";
    const IS_URL_VALID = "Monty\FormValidators::isUrlValid";
    const PHONE_FORMAT = "Monty\FormValidators::phoneFormatCheck";
    

	public static function keywordsValidation($control, $args) {
		$keywords = explode(";", $control->getValue());
		$keywordsCounter = count($keywords);
		
		if ($keywordsCounter >= $args["limit_min"] && $keywordsCounter <= $args["limit_max"]) {
			return true;
		}
	}

	public static function checkStartEndDatetime($end, $start) {
		// \Tracy\Debugger::barDump($args, "args");
		$end = DateTime::from($end->getValue());
		$start = DateTime::from($start);

		if ($end > $start) {
			return true;
		}
	}

	public static function checkStartEndDate($end, $start) {
		$end = DateTime::from($end->getValue());
		$start = DateTime::from($start);

		if ($end >= $start) {
			return true;
		}
	}

	public static function eventRepeatEndOrRepeats($end, $repeats) {
		$end = $end->getValue();
		#\Tracy\Debugger::barDump($end, "end");
		#\Tracy\Debugger::barDump($repeats, "repeats");

		if ($repeats || $end) {
			return true;
		}
	}

	public static function isNotGreaterZero($number) {
		\Tracy\Debugger::barDump("is greater zero");
		if ($number > 0) {
			return true;
		}
	}

	public static function isUrlValid($url) {
		$url = $url->getValue();
		\Tracy\Debugger::barDump($url, "url");

		$regex = "/^(http:\/\/www\.|https:\/\/www\.|http:\/\/|https:\/\/)?[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,5}(:[0-9]{1,5})?(\/.*)?$/";
		$res = Strings::match($url, $regex);

		return $res[0];
	}

	public static function phoneFormatCheck($input) {
		$str = $input->getValue();
		\Tracy\Debugger::barDump($str, "str");
		$str = str_replace(" ", "", $str);
		$len = strlen($str);
		\Tracy\Debugger::barDump($len, "len");


		if ($len < 13) {
			return false;
		} else {
			preg_match('/^(\+\d{3})\s*(\d{3})\s*(\d{3})\s*(\d{3})$/', $str, $matches);
			\Tracy\Debugger::barDump($matches, "matches");
			return $matches ? true : false;
		}
	}

}