<?php

declare(strict_types=1);

namespace App\CoreModule\Router;

use App;
use Nette;
use Nette\Application\Routers\RouteList;
use Nette\Application\Routers\Route;
use App\CoreModule\Components\MyRouter;



final class RouterManager
{
	public $routers, $homeRoute;


	public function __construct(array $routers)
	{
		$this->routers = $routers;

		// \Tracy\Debugger::barDump($this->routers, "routers");

		return $this;
	}

	public function createRouter(): RouteList
	{
		$list = new RouteList;

		$routers = array_reverse($this->routers);

		foreach ($routers as $router) {
			$rList = $router->createRouter($this);
			// \Tracy\Debugger::barDump($rList, "rList");

			$list[] = $rList;
		}

		// \Tracy\Debugger::barDump($list, "RouterManager route list");

		return $list;
	}

	public function appendRouteList($routeList) {
		$this->RouteList = $this->RouteList + $routeList;

		return $this;
	}

	public function test()
	{
		// \Tracy\Debugger::barDump("hovno");
	}

}
