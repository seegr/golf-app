<?php

namespace Monty\OAuth;

use Nette;
use Nette\Utils\ArrayHash;
use Google_Client;
use Google_Service_Oauth2;


final class OAuthGoogleControl extends \Monty\OAuth\OAuthControl
{
	protected $appName;
	protected $scope;
	protected $client;


	public function setAppName($appName): self
	{
		$this->appName = $appName;

		return $this;
	}

	public function setScope($scope): self
	{
		$this->scope = $scope;

		return $this;
	}

	public function getClient()
	{
		if ($this->id && $this->secret) {
			$client = new Google_Client();
			$client->setClientId($this->id);
			$client->setClientSecret($this->secret);
			$client->setApplicationName($this->appName);
			$client->setRedirectUri($this->LinkGenerator->link($this->redirectUri));
			$client->addScope("https://www.googleapis.com/auth/plus.login https://www.googleapis.com/auth/userinfo.email");

			// bdump($client, "client");

			return $client;
		} else {
			return null;
		}
	}

	public function getUserInfo($client)
	{
		// $oAuth = new Google_Service_Oauth2($this->getClient());
		$oAuth = new Google_Service_Oauth2($client);
		bdump($oAuth->userinfo_v2_me->get(), "google userinfo");
		// $gUser = ArrayHash::from($oAuth->userinfo_v2_me->get());
		$gUser = $oAuth->userinfo_v2_me->get();
		bdump($gUser, "gUser");

		return $gUser;
	}
	
}