<?php

namespace App\CoreModule\Extensions;

use Nette;
use App\CoreModule\Router\RouterManager;
use App\CoreModule\Router\IRouterFactory;


class RouterExtension extends Nette\DI\CompilerExtension
{
	const ROUTER_TAG = "router";

	public function loadConfiguration()
	{
		$config = $this->config;
		// \Tracy\Debugger::barDump($config, "config");

		$builder = $this->getContainerBuilder();
		// \Tracy\Debugger::barDump($builder, "builder");

		$routers = [];
		foreach ($config["routers"] as $name => $class) {
			$routers[] = $builder->addDefinition($this->prefix(self::ROUTER_TAG . $name))
				->setType(IRouterFactory::class)
				->setFactory($class)
				->setAutowired(false);
		}

		$builder->addDefinition($this->prefix('core.myRouter'))
			->setType(\App\CoreModule\Router\MyRouter::class)
			->setAutowired("self");

		// $RouterFactory = $builder->getDefinition("FinalRouterFactory");
		// $builder->addDefinition($this->prefix('routerManager'))
		// 	->setFactory(RouteManager::class, [$routers, $config['modules']]);
		$builder->addDefinition($this->prefix("RouterManager"))
			->setFactory(RouterManager::class, [$routers])
			->addSetup('$homeRoute', [$config["homeRoute"]]);
	}

	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();
		// \Tracy\Debugger::barDump($builder, "builder");

		$BaseManager = $builder->getDefinition("BaseManager");

		$config = $this->config;
		if ($config["homeRoute"]) {
			$BaseManager->addSetup('$homeRoute', [$config["homeRoute"]]);
		}

		$builder->getDefinition("router")
			->setFactory("@" . RouterManager::class . '::createRouter');
	}

}