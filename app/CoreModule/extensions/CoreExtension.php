<?php

namespace App\CoreModule\Extensions;

use Nette;



class CoreExtension extends Nette\DI\CompilerExtension
{

	protected $pars;

	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		// \Tracy\Debugger::barDump($builder, "builder");
		// \Tracy\Debugger::barDump($builder->parameters, "builder pars");
		$this->pars = $builder->parameters;
	}

	public function beforeCompile()
	{
		// \Tracy\Debugger::barDump("CoreExtension beforeCompile");
		$builder = $this->getContainerBuilder();
		// \Tracy\Debugger::barDump($builder, "builder");
		$BaseManager = $builder->getDefinition("BaseManager");
		// \Tracy\Debugger::barDump($BaseManager, "BaseManager");

		// \Tracy\Debugger::barDump($this->pars, "pars");
		$pars = $this->pars;
		$config = $this->config;
		// $appName, $wwwDir, $appDir, $maintenance;
		$BaseManager->addSetup('$wwwDir', [$pars["wwwDir"]])
			->addSetup('$appDir', [$pars["appDir"]]);

		foreach ($this->config as $setting => $val) {
			\Tracy\Debugger::barDump($setting, "setting");
			\Tracy\Debugger::barDump($val, "val");
			$BaseManager->addSetup("setSetting", [$setting, $val]);
		}

		// $router = $builder->getDefinition("routing.router");
		// \Tracy\Debugger::barDump($router, "router");
		// $routerFactory = $builder->getDefinition("core.routerFactory");
		// \Tracy\Debugger::barDump($routerFactory, "routerFactory");

		// $routerFactory->addSetup("defineRouteList");

		// $routeList = 
	}

	public function afterCompile(Nette\PhpGenerator\ClassType $class)
	{
		// \Tracy\Debugger::barDump($class, "class");
		// $method = $class->getMethod('__construct');
	}

}