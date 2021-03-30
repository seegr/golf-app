<?php

declare(strict_types=1);

namespace App;

use Nette\Configurator;


class Bootstrap
{
	public static function boot(): Configurator
	{		
		$configurator = new Configurator;

		$sessionDir = __DIR__ . "/../session";
		$logDir = __DIR__ . "/../log";
		$tempDir = __DIR__ . "/../temp";
		$cacheDir = __DIR__ . "/../temp/cache";
		
		if (!file_exists($sessionDir)) mkdir($sessionDir);
		if (!file_exists($logDir)) mkdir($logDir);

		$configurator->setDebugMode(true); // enable for your remote IP
		$configurator->enableTracy(__DIR__ . '/../log');

		$configurator->setTimeZone('Europe/Prague');
		$configurator->setTempDirectory($tempDir);
		
		$configurator->addParameters([
			"sessionDir" => $sessionDir,
			"projectPublic" => __DIR__ . "/../www",
			"tempDir" => $tempDir,
			"cacheDir" => $cacheDir
		]);

		$configurator->createRobotLoader()
			->addDirectory(__DIR__)
			->register();

		$configurator
			->addConfig(__DIR__ . '/CoreModule/config/common.neon');

		//** add project neons
		$path = __DIR__ . "/config";
		$neons = scandir(__DIR__ . "/config");
		unset($neons[0], $neons[1]);

		foreach ($neons as $config) {
			$configurator->addConfig($path . "/" . $config);
		}

		return $configurator;
	}

}