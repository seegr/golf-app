<?php

namespace Monty\OAuth;

use Nette;
use Nette\Utils\ArrayHash;


class OAuthControl extends Nette\Application\UI\Control
{

	protected $LinkGenerator;
	protected $id, $secret;
	protected $redirectUri;


	public function __construct(Nette\Application\LinkGenerator $LinkGenerator)
	{
		$this->LinkGenerator = $LinkGenerator;

		return $this;
	}


	public function setId($id) {
		$this->id = $id;

		return $this;
	}

	public function setSecret($secret) {
		$this->secret = $secret;

		return $this;
	}

	public function setRedirectUri($redirectUri): self
	{
		$this->redirectUri = $redirectUri;

		return $this;
	}

	// public function __construct(MenuContainer $container, string $name)
	// {
	// 	$this->container = $container;
	// 	$this->menuName = $name;

	// 	$this->monitor(Presenter::class, function (Presenter $presenter): void {
	// 		$menu = $this->container->getMenu($this->menuName);
	// 		$menu->setActivePresenter($presenter);
	// 	});
	// }
	
}