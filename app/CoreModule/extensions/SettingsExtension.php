<?php

namespace App\CoreModule\Extensions;

use Nette;


class SettingsExtension extends BaseExtension
{

	public function loadConfiguration()
	{
		// bdump($this->config, "config");
	}

	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();

		$BaseManager = $builder->getDefinition("BaseManager");
		// bdump($BaseManager, "BaseManager");
		
		foreach ($this->config as $setting => $val) {
			// bdump($setting, "setting");
			// bdump($val, "val");
			$BaseManager->addSetup("setSetting", [$setting, $val]);
		}

		// foreach ($this->config as $type => $data) {
		// 	$SettingsManager->addSetup("setSetting", [$type, $data["fields"]]);
		// }
	}

}