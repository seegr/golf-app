<?php

declare(strict_types=1);

namespace App\Router;

use App;
use Nette\Application\Routers\RouteList;


class RouterFactory implements App\CoreModule\Router\IRouterFactory
{

	public function createRouter($RouterManager): RouteList
	{
		$router = new RouteList;

		$router->addRoute("", "Admin:Dashboard:dashboard");
		
		// $router->addRoute("", "Front:Home:home");

		$router->addRoute("api/front-init", "Front:Api:frontInit");

		return $router;
	}

}