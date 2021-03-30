<?php

namespace App\CoreModule\Traits;

use Nette\Utils\Json;


trait NavigationsTrait {

	public function getCurrentNavItemId() {
		$navItemId = $this->getParameter("nav_item_id");

		return $navItemId ? $navItemId : null;
	}

	public function getCurrentNavItemPars() {
		if ($id = $this->getCurrentNavItemId()) {
			$navItem = $this->NavigationsManager->getNavigationItem($id);

			return $navItem->params ? Json::decode($navItem->params) : null;
		} else {
			return null;
		}
	}

}