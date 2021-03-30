<?php

namespace App\CoreModule\Extensions;

use Nette;


class ContentsExtension extends BaseExtension
{

	public function loadConfiguration()
	{
		// \Tracy\Debugger::barDump($this->config, "config");
	}

	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();

		$ContentsManager = $builder->getDefinition("ContentsManager");

		foreach ($this->config as $type => $data) {
			$ContentsManager->addSetup("addContentCustomFields", [$type, $data["fields"]]);
			$ContentsManager->addSetup("addContentExcludeFields", [$type, $data["exclude"]]);
		}
	}

}