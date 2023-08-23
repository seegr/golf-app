<?php

namespace Monty;

use Nette;
use Nette\Schema\Expect;
use Google_Client;
use Google_Service_Oauth2;


class GoogleApi extends Nette\DI\CompilerExtension
{

	protected $LinkGenerator;
	protected $id;
	protected $secret;
	protected $appName;
	protected $redirectUri;
	protected $scope;
	protected $client;


	public function __construct(Nette\Application\LinkGenerator $LinkGenerator)
	{
		$this->LinkGenerator = $LinkGenerator;
	}


	public function setId($id): void
	{
		$this->id = $id;
	}

	public function setSecret($secret): void
	{
		$this->secret = $secret;
	}

	public function setAppName($appName): void
	{
		$this->appName = $appName;
	}

	public function setRedirectUri($redirectUri): void
	{
		$this->redirectUri = $redirectUri;
	}

	// public function setScope($scope): void
	// {
	// 	$this->scope = $scope;
	// }

	public function setClient()
	{
		// bdump($this, "setClient");

		if (!$this->id || !$this->secret) return;

		$gClient = new Google_Client();
		$gClient->setClientId($this->id);
		$gClient->setClientSecret($this->secret);
		$gClient->setApplicationName($this->appName);
		$gClient->setRedirectUri($this->LinkGenerator->link("Core:Front:Users:googleSignInCallback"));
		$gClient->addScope("https://www.googleapis.com/auth/plus.login https://www.googleapis.com/auth/userinfo.email");
		// bdump($gClient, "gClient");

		$this->client = $gClient;

		return $this->client;
	}

	public function getClient()
	{
		return $this->setClient();
	}

}