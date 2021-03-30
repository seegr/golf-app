<?php

namespace Monty\OAuth;

use Nette;
use Nette\Utils\ArrayHash;
use Facebook\Facebook;


final class OAuthFacebookControl extends \Monty\OAuth\OAuthControl
{

	public function getClient()
	{
		if ($this->id && $this->secret) {
			$client = new Facebook([
			  'app_id'      => $this->id,
			  'app_secret'     => $this->secret,
			  'default_graph_version'  => 'v7.0'
			]);

			return $client;
		} else {
			return null;
		}
	}

	public function getRedirectUri() {
		$client = $this->getClient();

		$fb_helper = $client->getRedirectLoginHelper();

		$url = $fb_helper->getLoginUrl($this->LinkGenerator->link($this->redirectUri), ["email"]);		

		return $url;
	}
	
}