<?php

require("deploy_config.php");

class Deploy {

	public $config;

	public $tempFolder;		//Folder where zip will be downloaded
	public $zipFile;		//Name and path of the downloaded zipped source

	public $repoBranch;		//repo's branch
	public $repoUrl; 		//URL from repo
	public $repoUser;		//For authentication in the repo
	public $repoPass;		//For authentication in the repo

	public $destination;	//Folder where proyect will be placed, if it already exist's it will be moved to...
	public $oldDeploys;		//Folder where old proyects will be moved
	public $sourceFolder;	//Folder that conatins source files inside repo

	public $wasError;		//Indicates whether there was an error or not
	public $emails;			//Emails to notify when deploy has finished
	public $errorMessage;

	public function Deploy($config)
	{
		$this->config = $config;

		$this->repoBranch = $config["repo"]["branch"];
		$this->tempFolder = $config["path"]["temp"];
		$this->zipFile = $this->repoBranch .".zip";

		$this->repoUrl = $config["repo"]["url"];
		$this->repoUser = $config["repo"]["user"];
		$this->repoPass = $config["repo"]["pass"];

		$this->sourceFolder = $config["path"]["source"];
		$this->destination = $config["path"]["destination"];
		$this->oldDeploys = $config["path"]["old"];

		$this->wasError = false;
		$this->emails = $config["emails"];
	}


	public function makeDeploy()
	{

		try{
			if(is_dir($this->tempFolder)){
				throw new Exception("Deploy already in progress", 1);
			}

			$this->download();
			$this->unzip();
			$this->moveOld();
			$this->moveNew();
		
		}catch(Exception $e){
			$this->errorMessage = $e->getMessage();
			$this->wasError = true;
		}

		try{
			//$this->deleteTemp();
		}catch(Exception $e){
			$this->wasError = true;
			$this->errorMessage = $e->getMessage();
		}
		
		$this->notify();
	}



	/* Downloads the repo to temp folder using username and password*/
	public function download()
	{
		createFolder($this->tempFolder);
		$fp = fopen($this->tempFolder .$this->zipFile, 'w');
	 	
		// set download url of repository for the relating node
		$ch = curl_init($this->repoUrl .$this->zipFile);

		// http authentication to access the download
		curl_setopt($ch, CURLOPT_USERPWD, $this->repoUser .":" .$this->repoPass);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	 
	 	// disable ssl verification if your server doesn't have it
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		// save the transfered zip folder
		curl_setopt($ch, CURLOPT_FILE, $fp);
	 
		// run the curl command
		$result = curl_exec($ch);	//returns true / false
	 
		// close curl and file functions
		curl_close($ch);
		fclose($fp);
		if(!$result){
			throw new Exception("Error al descargar el archivo", 1);
		}
	}


	/* Unzip the repo */
	public function unzip()
	{
		$zip = new ZipArchive;
		$res = $zip->open($this->tempFolder .$this->zipFile);
		if ($res === TRUE) {
			$zip->extractTo($this->tempFolder);
			$zip->close();
			return;
		}

		throw new Exception("Error while trying to decompress");
	}

	public function moveOld()
	{
		/* If directory doesn't exist, do nothing*/
		if(!is_dir($this->destination)){
			return;
		}

		/* Create the old folder */
		createFolder($this->oldDeploys);
		$date = getdate();

		/* Move the old files to the olds folder*/
		$name = $this->oldDeploys .substr($this->destination, 0, -1) ."_" .$date['year'] ."_" .$date['mon'] ."_" .$date['mday'] ."_" .$date[0];
		if(!rename($this->destination, $name)){
			throw new Exception("Error while moving old proyect", 1);
		}
	}


	/* Moves all new files*/
	public function moveNew()
	{
		/*
		* Bitbucket sadly ads random characters, so it has to guess...
		*/
		$dirs = scandir($this->tempFolder);
		foreach ($dirs as $dir) {
			if($dir != "." && $dir != ".." && $dir != ($this->zipFile)){
				if(rename($this->tempFolder. $dir ."/" .$this->sourceFolder, $this->destination)){
					return;
				}
			}
		}
		throw new Exception("Error while trying to move new deploy");	
	}

	public function deleteTemp()
	{
		if(!deleteDirectory($this->tempFolder)){
			throw new Exception("Couldn't delete temp folder", 1);
		}
	}

	public function notify()
	{
		if(sizeof($this->emails)>0){
			if($this->wasError){
				$msg = "Deploy finished with error: $this->errorMessage";
			}else{
				$msg = "Deploy finished OK";
			}
			mail (implode(", ", $this->emails) , "Deploy finished", $msg);
		}
	}



}


function createFolder($dir){
	if(!is_dir($dir)){
		if(!mkdir($dir, 0777, true)){
			throw new Exception("No se pudo crear la carpeta", 1);
		}
	}
}

/* Deletes a not empty directory*/
function deleteDirectory($dir) {
    if (!file_exists($dir)) {
        return true;
    }

    if (!is_dir($dir)) {
        return unlink($dir);
    }

    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }

        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }

    }

    return rmdir($dir);
}



$deploy = new Deploy($config);
$deploy->makeDeploy();

