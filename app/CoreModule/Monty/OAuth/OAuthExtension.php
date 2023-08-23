<?php

namespace Monty\OAuth;

use Nette;
use Nette\Schema\Expect;
use Google_Client;
use Google_Service_Oauth2;
use Monty\OAuth\OAuthFacebookControl;
use Monty\OAuth\OAuthGoogleControl;


class OAuthExtension extends Nette\DI\CompilerExtension
{
	public function loadConfiguration()
	{
		$config = $this->config;
		// bdump($config, "config");

		$builder = $this->getContainerBuilder();
		// bdump($builder, "builder");

		// $container = $builder->addDefinition($this->prefix('container'))
		// 	->setType(MenuContainer::class);

		// $builder->addFactoryDefinition($this->prefix('component.menu'))
		// 	->setImplement(IMenuComponentFactory::class)
		// 	->getResultDefinition()
		// 		->setType(MenuComponent::class);

		// foreach ($config as $menuName => $menu) {
		// 	$container->addSetup('addMenu', [
		// 		$this->loadMenuConfiguration($builder, $menuName, $menu),
		// 	]);
		// }

		$fbContainer = $builder->addDefinition($this->prefix('facebook'))
			->setClass(OAuthFacebookControl::class);
		$gContainer = $builder->addDefinition($this->prefix('google'))
			->setClass(OAuthGoogleControl::class);

		if (!empty($config["facebook"])) {
			$fbConf = $config["facebook"];

			$fbContainer->addSetup("setId", [$fbConf["id"]])
				->addSetup("setSecret", [$fbConf["secret"]])
				->addSetup("setRedirectUri", [$fbConf["redirectUri"]]);
		}

		if (!empty($config["google"])) {
			$gConf = $config["google"];

			$gContainer->addSetup("setId", [$gConf["id"]])
				->addSetup("setSecret", [$gConf["secret"]])
				->addSetup("setAppName", [$gConf["appName"]])
				->addSetup("setRedirectUri", [$gConf["redirectUri"]]);
		}

		// bdump($builder, "builder");
	}

	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();
		// bdump($builder, "builder");

	}

}
