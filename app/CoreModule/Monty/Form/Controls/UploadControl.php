<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Monty\Forms\Controls;

use Nette;
use Nette\Forms;
use Monty\Form;
use Nette\Http\FileUpload;


/**
 * Text box and browse button that allow users to select a file to upload to the server.
 */
class UploadControl extends Nette\Forms\Controls\UploadControl
{
	/** validation rule */
	public const
		VALID = ':uploadControlValid',
		BYTES_IN_MB = 1048576;


	/**
	 * @param  string|object  $label
	 */
	public function __construct($label = null, bool $multiple = false)
	{
		parent::__construct($label);
		$this->control->type = 'file';
		$this->control->multiple = $multiple;
		$this->setOption('type', 'file');

		$maxFileSize = Forms\Helpers::iniGetSize('upload_max_filesize');
		// \Tracy\Debugger::barDump($maxFileSize, "maxFileSize");

		$this->addCondition(true) // not to block the export of rules to JS
			->addRule([$this, 'isOk'], );
		$this->addRule(Form::MAX_FILE_SIZE, null, $maxFileSize);

		$this->monitor(Form::class, function (Form $form): void {
			if (!$form->isMethod('post')) {
				throw new Nette\InvalidStateException('File upload requires method POST.');
			}
			$form->getElementPrototype()->enctype = 'multipart/form-data';
		});
	}


	/** @return static */
	// public function addRule($validator, $errorMessage = null, $arg = null)
	// {
	// 	if ($validator === Form::IMAGE) {
	// 		$this->control->accept = implode(', ', FileUpload::IMAGE_MIME_TYPES);
	// 	} elseif ($validator === Form::MIME_TYPE) {
	// 		$this->control->accept = implode(', ', (array) $arg);
	// 	} elseif ($validator === Form::MAX_FILE_SIZE) {
	// 		// \Tracy\Debugger::barDump("MAX_FILE_SIZE");
	// 		// \Tracy\Debugger::barDump($arg, "arg");
	// 		// \Tracy\Debugger::barDump(Forms\Helpers::iniGetSize('upload_max_filesize'), "upload_max_filesize");
	// 		if ($arg > Forms\Helpers::iniGetSize('upload_max_filesize')) {
	// 			$ini = ini_get('upload_max_filesize');
	// 			// \Tracy\Debugger::barDump($ini, "ini");
	// 			trigger_error("Value of MAX_FILE_SIZE ($arg) is greater than value of directive upload_max_filesize ($ini).", E_USER_WARNING);
	// 		}
	// 		$this->getRules()->removeRule($validator);
	// 	}
	// 	return parent::addRule($validator, $errorMessage, $arg);
	// }
}
