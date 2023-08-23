<?php

namespace App\CoreModule\Extensions;

use Nette;



class CoreExtension extends Nette\DI\CompilerExtension
{

	protected $pars;

	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		// bdump($builder, "builder");
		// bdump($builder->parameters, "builder pars");
		$this->pars = $builder->parameters;
	}

	public function beforeCompile()
	{
		// bdump("CoreExtension beforeCompile");
		$builder = $this->getContainerBuilder();
		// bdump($builder, "builder");
		$BaseManager = $builder->getDefinition("BaseManager");
		// bdump($BaseManager, "BaseManager");

		// bdump($this->pars, "pars");
		$pars = $this->pars;
		$config = $this->config;
		// $appName, $wwwDir, $appDir, $maintenance;
		$BaseManager->addSetup('$wwwDir', [$pars["wwwDir"]])
			->addSetup('$appDir', [$pars["appDir"]]);

		foreach ($this->config as $setting => $val) {
			bdump($setting, "setting");
			bdump($val, "val");
			$BaseManager->addSetup("setSetting", [$setting, $val]);
		}

		// $router = $builder->getDefinition("routing.router");
		// bdump($router, "router");
		// $routerFactory = $builder->getDefinition("core.routerFactory");
		// bdump($routerFactory, "routerFactory");

		// $routerFactory->addSetup("defineRouteList");

		// $routeList = 
	}

	public function afterCompile(Nette\PhpGenerator\ClassType $class)
	{
		// bdump($class, "class");
		// $method = $class->getMethod('__construct');
	}

}