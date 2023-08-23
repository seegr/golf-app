<?php

namespace Monty;

use Nette;
use Nette\Utils\ArrayHash;

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;


class Aws {

	use Nette\SmartObject;

	protected $iamKey, $iamSecret, $s3Bucket, $s3Region;

	public function __construct($settings) {
		$settings = ArrayHash::from($settings);

		$this->iamKey = $settings->iamKey;
		$this->iamSecret = $settings->iamSecret;
		$this->s3Bucket = $settings->s3->bucket;
		$this->s3Region = $settings->s3->region;
	}

	public function getS3Client() {
		// Connect to AWS
		try {
			// You may need to change the region. It will say in the URL when the bucket is open
			// and on creation.
			$s3 = S3Client::factory(
				array(
					'credentials' => array(
						'key' => $this->iamKey,
						'secret' => $this->iamSecret
					),
					'version' => 'latest',
					'region'  => $this->s3Region
				)
			);

			return $s3;
		} catch (Exception $e) {
			// We use a die, so if this fails. It stops here. Typically this is a REST call so this would
			// return a json object.
			die("Error: " . $e->getMessage());
		}		
	}

	public function s3UploadObject($fileName, $filePath, $protected = false) {
		$s3 = $this->getS3Client();

		$result = $s3->putObject(
			array(
				'Bucket'=> $this->s3Bucket,
				'Key' => $fileName,
				'SourceFile' => $filePath,
				'StorageClass' => 'REDUCED_REDUNDANCY',
				"ACL" => $protected ? "private" : "public-read"
				// "ACL" => "public-read"
			)
		);

		return $result;
	}

	public function getS3Object($keyName, $type = null, $downloadName = null) {
		$s3 = $this->getS3Client();

		try {
		    // Get the object.
		    $result = $s3->getObject([
		        'Bucket' => $this->s3Bucket,
		        'Key'    => $keyName
		    ]);

		    // Display the object in the browser.
		    //bdump($result, "result");
		    if (!$type) {
		    	$type = $result['ContentType'];
		    	if ($downloadName) header("Content-Disposition: attachment; filename=" . $downloadName);
		    }

			header("Content-Type: {$type}");
		    
		    echo $result['Body'];
		    //die();
		} catch (S3Exception $e) {
			throw new \Exception($e->getMessage());
		    //echo $e->getMessage() . PHP_EOL;
		    // die();
		}
	}

	public function deleteS3Object($keyName) {
		$s3 = $this->getS3Client();
		$s3->deleteObject([
		    'Bucket' => $this->s3Bucket,
		    'Key'    => $keyName
		]);
	}	

}