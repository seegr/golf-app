<?php

declare(strict_types=1);

namespace App\CoreModule\Router;

use App;
use Nette;
use Nette\Application\Routers\RouteList;
use Nette\Application\Routers\Route;
use App\CoreModule\Components\MyRouter;



class RouterFactory implements App\CoreModule\Router\IRouterFactory
{
	public $MyRouter;
	protected $User, $BaseManager;

	public function __construct(
		// App\CoreModule\Router\RouterManager $RouterManager,
		App\CoreModule\Router\MyRouter $MyRouter,
		Nette\Security\User $User,
		App\CoreModule\Model\BaseManager $BaseManager
	)
	{
		// \Tracy\Debugger::barDump("router constructor");
		// $this->RouterManager = $RouterManager;
		$this->MyRouter = $MyRouter;
		$this->User = $User;
		$this->BaseManager = $BaseManager;
	}

	public function createRouter($RouterManager): RouteList
	{
		// \Tracy\Debugger::barDump("router defineRouteList");
		$router = new RouteList;

		// admin
		$router->addRoute("admin", "Core:Admin:Dashboard:default");
		$router->withModule("Core:Admin")->withPath('admin')
			->addRoute("test", "Custom:test")
			->addRoute("obsah-vypis/<type>", "Contents:contentsList")
			->addRoute("obsah/<id>", "Contents:contentForm")
			->addRoute("uzivatele", "Users:usersList")
			->addRoute("uzivatel[/<id>]", "Users:userForm")
			->addRoute("navigace-vypis", "Navigations:navigationsList")
			->addRoute("navigace[/<id>]", "Navigations:navigation")
			->addRoute("navigace-tlacitko[/<id>]", "Navigations:navigationItemForm")
		->end();
		
		$router->addRoute("soubory", "Core:Admin:FilesList:filesList");

		$this->setMaintenanceRoute($router);

		// front
		$langMask = $this->getLangsMask();
		// \Tracy\Debugger::barDump($langMask, "langMask");
		// $router->addRoute("[<lang=cs cs|en>/]", $RouterManager->homeRoute);
		$router->addRoute($langMask, $RouterManager->homeRoute);

		$router->withModule("Core:Front")
			// ->withPath("tralala/<lang=cs>/")
			->addRoute("prihlasit", "Users:loginForm")
			->addRoute("odhlasit", "Users:logout")
			->addRoute("registrace", "Users:registerForm")
			->addRoute("google-login", "Users:googleSignIn")
			->addRoute("google-sign-in-callback", "Users:googleSignInCallback")
			->addRoute("facebook-login", "Users:facebookSignIn")
			->addRoute("facebook-sign-in-callback", "Users:facebookSignInCallback")
			->addRoute("profil", "User:profile")
			->addRoute("uprava-profilu", "User:userForm")
			->addRoute("api/<act>", "Api:default")
		->end();
		
		$router->add($this->MyRouter);

		$router->addRoute("<module>/<presenter>/<action>");

		return $router;
	}

	public function setMaintenanceRoute($router)
	{
		// \Tracy\Debugger::barDump($this->User, "user");
		$auth = $this->User->getAuthorizator();
		// \Tracy\Debugger::barDump($auth, "auth");
		$resources = $auth->getResources();
		// \Tracy\Debugger::barDump($resources, "resources");
		
		$des = in_array("Admin:Maintenance", $resources) ? "Admin:Maintenance:default" : "Core:Admin:Maintenance:default";
		$router->addRoute("admin/maintenance[/<act>]", $des);
	}

	public function getLangsMask()
	{
		$langs = $this->BaseManager->getLangs(true);

		if (count($langs) < 2) {
			return "";
		} else {
			// $mask = "[<local=cs cs|en>/]";
			$langs = $langs->fetchAll();
			$default = $this->BaseManager->getDefaultLang();

			$mask = "[<lang=" . $default->code . " ";

			// \Tracy\Debugger::barDump($langs, "langs");
			// \Tracy\Debugger::barDump(end($langs), "end lang");
			foreach ($langs as $lang) {
				$mask .= $lang->code;
				if ($lang == end($langs)) {
					$mask .= ">]";
				} else {
					$mask .= "|";
				}				
			}

			return $mask;
		}

		return $mask;
	}

}
