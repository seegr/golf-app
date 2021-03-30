<?php

namespace App\CoreModule\Extensions;

use Nette;


class SettingsExtension extends BaseExtension
{

	public function loadConfiguration()
	{
		// \Tracy\Debugger::barDump($this->config, "config");
	}

	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();

		$BaseManager = $builder->getDefinition("BaseManager");
		// \Tracy\Debugger::barDump($BaseManager, "BaseManager");
		
		foreach ($this->config as $setting => $val) {
			// \Tracy\Debugger::barDump($setting, "setting");
			// \Tracy\Debugger::barDump($val, "val");
			$BaseManager->addSetup("setSetting", [$setting, $val]);
		}

		// foreach ($this->config as $type => $data) {
		// 	$SettingsManager->addSetup("setSetting", [$type, $data["fields"]]);
		// }
	}

}