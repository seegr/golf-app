<?php

namespace Monty;


class Strings extends \Nette\Utils\Strings {

	public static function camelToDash($string) {
		if (self::contains($string, " ")) {
			$parts = explode(" ", $string);
		} else {
			$parts = preg_split('/(?=[A-Z])/', $string, -1, PREG_SPLIT_NO_EMPTY);
		}

		foreach ($parts as &$part) {
			$part = self::webalize($part);
			$part = str_replace("--", "-", $part);
		}

		$outputStr = implode("-", $parts);
		$outputStr = str_replace("--", "-", $outputStr);
		
		return $outputStr;
	}

	public static function webalizeFileName($fileName) {
		$exp = explode(".", $fileName);
		$ext = strtolower(end($exp));
		unset($exp[count($exp)-1]);

		$name = implode(".", $exp);
		$name = self::webalize($name);
		$name .= "." . $ext;

		return $name;
	}

}