<?php

declare(strict_types=1);

namespace App\CoreModule\Router;

use App;
use Nette;
use Nette\Application\Routers\RouteList;
use Nette\Application\Routers\Route;
use App\CoreModule\Components\MyRouter;



interface IRouterFactory
{

	public function createRouter(App\CoreModule\Router\RouterManager $RouterManager): RouteList;

}
