<?php

namespace App\CoreModule\AdminModule\Traits;

use Nette\Application\Responses\FileResponse;
use App\CoreModule\Model\FilesManager;
use Monty\Form;


trait FilesTrait {

	/** @var \App\Model\FilesManager @inject */
	public $FilesManager;


	public function handleDownloadFile($key) {
		\Tracy\Debugger::barDump($key, "key");
		$file = $this->FilesManager->getFile($key);

		if (!$file) $this->error("Soubor nenalezen", 404);

		if (strpos($file->url, "http") !== false) {
			// header("Content-type: application/x-file-to-save"); 
			// header("Content-Disposition: attachment; filename=".basename($remoteURL));
			// ob_end_clean();
			// readfile($remoteURL);
			$path = $file->url;
			$name = $file->name;

			$hResp = $this->getHttpResponse();
	    	$hResp->setHeader('Content-type', 'application/octet-stream');
	    	$hResp->setHeader('Content-Disposition', 'attachment; filename='.$name);
	    	readfile($file->url);
		} else {
			$path = FilesManager::FILES_DIR . $file->key;
		    $fileResponse = new FileResponse($path, $file->name);
		    $fileResponse->send($this->getHttpRequest(), $this->getHttpResponse());
		}

		exit();
	}

	public function createComponentFileUploadForm() {
		$form = new Form;

		$form->addMultiUpload("files", "Soubor/y");
		$form->addSubmit("submit", "Nahrát");

		$form->onSuccess[] = function($f, $v) {
			\Tracy\Debugger::barDump($v, "vals");
			foreach($v->files as $file) {
				$this->FilesManager->uploadFile($file, $this->getUser()->id);
			}
		};

		return $form;
	}

	public function deleteFile($id) {
		$this->FilesManager->fileDelete($id);
	}

	public function cleanUpFiles(): void {
		$this->FilesManager->cleanUpFiles();
	}

}