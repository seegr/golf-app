<?php

namespace Monty\Extensions;

use Nette;
use Nette\Schema\Expect;
use Google_Client;
use Google_Service_Oauth2;


class GoogleApiExtension extends Nette\DI\CompilerExtension
{

	public function getConfigSchema(): Nette\Schema\Schema
	{
		\Tracy\Debugger::barDump($this, "getConfigSchema");
		return Expect::structure([
			"id" => Expect::string(),
			"secret" => Expect::string(),
			"applicationName" => Expect::string(),
			"redirectUri" => Expect::string(),
			"scope" => Expect::string()->default("https://www.googleapis.com/auth/plus.login https://www.googleapis.com/auth/userinfo.email"),
			// 'allowComments' => Expect::bool()->default(true),
		]);
	}

	public function loadConfiguration()
	{
		\Tracy\Debugger::barDump($this->config, "loadConfiguration");
		$builder = $this->getContainerBuilder();
		// \Tracy\Debugger::barDump($builder, "builder");
		// $builder->addDefinition($this->prefix('googleApi'));
			// ->setFactory(App\Model\HomepageArticles::class, ['@connection'])
			// ->addSetup('setLogger', ['@logger']);
	}

	// public function beforeCompile()
	// {
	// 	$builder = $this->getContainerBuilder();

	// 	// foreach ($builder->findByTag('logaware') as $name) {
	// 	// 	$builder->getDefinition($name)->addSetup('setLogger');
	// 	// }
	// }	


	public function setClient()
	{
		\Tracy\Debugger::barDump($this, "setClient");
		$config = $this->config;
		\Tracy\Debugger::barDump($config, "config");
		// $gClient = new Google_Client();
		// $gClient->setClientId($gConfigs->id);
		// $gClient->setClientSecret($gConfigs->secret);
		// $gClient->setApplicationName("Google login test");
		// $gClient->setRedirectUri($this->link("//:Front:Users:googleSignInCallback"));
		// $gClient->addScope("https://www.googleapis.com/auth/plus.login https://www.googleapis.com/auth/userinfo.email");

		// \Tracy\Debugger::barDump($gClient, "gClient");
	}

}