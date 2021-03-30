<?php

namespace App\CoreModule\AdminModule\Traits;

use Nette\Utils\Html;
use Nette\Utils\ArrayHash;
use Nette\Application\UI\Form;

use Monty\DataGrid;


trait UsersTrait {

	public function usersList() {
		$list = new DataGrid;

		$user = $this->getUser();

		// $list->setItemsPerPageList([20, 50, 100]);

		$list->addColumnText("firstname", "Jméno")->setSortable()->setFilterText();
		$list->addColumnText("lastname", "Příjmení")->setSortable()->setFilterText();
		$list->addColumnText("email", "E-mail")->setSortable()->setFilterText();

		$list->addAction("active", null, "toggleUserActive!")
			->setRenderer(function($item) {
				$el = Html::el("a");
				$el->href($this->link("toggleUserActive!", $item->id));
				$el->class[] = "ajax";
				$el->addAttributes(["title" => "Aktivní", "data-toggle" => "tooltip", "data-confirm" => "Opravdu?"]);
				$icon = Html::el("i");

				if ($item->active) {
					$icon->class[] = "fas fa-check text-success";
				} else {
					$icon->class[] = "fas fa-times text-danger";
				}

				$el->addHtml($icon);
				return $el;
			});
		$list->addAction("delete", null, "userDelete!")
			->setIcon("fas fa-trash")
			->setClass("text-danger ajax")
			->setConfirm(function($item) {return "Opravdu smazat $item->firstname $item->lastname?";});

		return $list;
	}

	public function toggleUserActive($id) {
		\Tracy\Debugger::barDump("user toggle");
		$user = $this->UsersManager->getUser($id);

		if ($user->active) {
			$user->update(["active" => 0]);
			$this->flashMessage("Uživatel deaktivován", "alert-warning");
		} else {
			$user->update(["active" => 1]);
			$this->flashMessage("Uživatel aktivován");
		}

		$this->redrawControl("flashes");
		$this["usersList"]->reload();

		return $user;
	}

	public function handleUserDelete($id) {
		try {
			$this->UsersManager->userDelete($id);
			$this->flashMessage("Uživatel odstraněn", "alert-danger");
			$this->redrawControl("flashes");
			$this["usersList"]->reload();
		} catch (\Nette\Database\ForeignKeyConstraintViolationException $e) {
			$this->flashMessage("Uživatel nelze odstranit, je nejspíš vázán s nějakou událostí. Můžeš ho ale deaktivovat.", "alert-warning");
			$this->redrawControl("flashes");
		}
	}

	public function iamInOrg($org) {
		$col = is_numeric($org) ? "id" : "short";
		$orgs = $this->getMyOrgs()->fetchPairs(null, $col);
		$user = $this->getUser();

		return in_array($org, $orgs) || $user->isInRole("superadmin") ? true : false;
	}

	public function getMyOrgs() {
		$user = $this->getUser();

		if (!$user->isInRole("superadmin")) {
			return $this->UsersManager->getUserOrgs($this->getUser()->id);
		} else {
			return $this->OrgsManager->getOrgs();
		}
	}

	public function iamSuperadmin($inlcudeSession = false) {
		$user = $this->getUser();
    	$superadminSession = $this->getSession("superadmin");

    	if ($user->isInRole("superadmin") || ($inlcudeSession && $superadminSession->iamSuperadmin)) {
    		$superadminSession->setExpiration("1 day");
    		$superadminSession->iamSuperadmin = true;

    		return true;
    	} else {
    		return false;
    	}
	}

	public function getUserData($id = null) {
		$id = $id ? $id : $this->getUser()->id;
		
		return $this->UsersManager->getUser($id);
	}

	public function getUserEmailLink($id, $fullnameLabel = false) {
		$user = $this->getUserData($id);

		$a = Html::el("a");
		$a->href("mailto:" . $user->email);
		$a->setText($fullnameLabel ? $user->fullname : $user->email);

		return $a;
	}	

}