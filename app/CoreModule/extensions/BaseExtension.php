<?php

namespace App\CoreModule\Extensions;

use Nette;



class BaseExtension extends Nette\DI\CompilerExtension
{

	protected $pars;

	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$this->pars = $builder->parameters;
	}

	public function beforeCompile()
	{
	}

	public function afterCompile(Nette\PhpGenerator\ClassType $class)
	{
	}

}