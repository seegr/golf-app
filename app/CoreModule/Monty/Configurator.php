<?php

namespace Monty;

use Nette\Http\Url;
use Nette\Utils\Strings;


class Configurator extends \Nette\Configurator
{

	public function getNameByUrl($webalize = false): string
	{
		// \Tracy\Debugger::barDump($_SERVER, "server");
		$name = $_SERVER["SERVER_NAME"];
		$name = explode(".", $name);
		$count = count($name);
		if ($count < 3) {
			array_unshift($name, "www");
		}

		// \Tracy\Debugger::barDump($name, "name");

		if (!in_array($name[1], ["localhost", "127.0.0.1"])) {
			$name = $name[1] . "." . $name[2];
		} else {
			$name = "localhost";
		}

		return $webalize ? Strings::webalize($name) : $name;
	}

}