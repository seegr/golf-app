<?php

namespace App\CoreModule\FormsModule\Traits;

use Nette\Application\UI\Multiplier;
use Nette\Application\UI\Form;
use Monty\DataGrid;


trait FormsTrait {

	public function isFieldSelector($id) {
		$field = $this->FormsManager->getFormField($id);

		$type = $field->ref("type")->short;

		return in_array($type, ['radio', 'select', 'multiselect', "checkboxlist"]) ? true : false;
	}

}