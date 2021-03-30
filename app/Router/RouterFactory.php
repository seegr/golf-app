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

		$router->addRoute("admin", "Admin:Dashboard:dashboard");
		
		$router->addRoute("", "Front:Home:home");

		return $router;
	}

}