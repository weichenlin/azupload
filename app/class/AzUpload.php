<?php

class AzUpload {
    private $view;
    
    public function __construct() {
        $this->view = new Template();
    }
    public function __destruct() {}
    
    public function handleRequest() {
        if (isset($_GET['act']) && 'upload' == $_GET['act']) {
        	$ajax = (isset($_GET['ajax']) && 'true' == $_GET['ajax']) ? true : false;
            $this->processUploadFile($ajax);
        }
        elseif (isset($_GET['dl']) && !empty($_GET['dl'])) {
            $this->processDownloadFile($_GET['dl']);
        }
        else {
            $fm = new FileManager();
            $files = $fm->getFileList(10);
            $histories = array();
            foreach ($files as $file) {
                $histories[] = array(
                    'url' => $this->buildDownloadUrl($file),
                    'file' => $file
                );
            }
            $this->view->histories = $histories;
            $this->view->render('main.tpl');
        }
    }
    
    private function processUploadFile($ajax = false) {
        if (empty($_FILES['file']['name'])) {
            $this->view->render('upload.tpl');
            return false;
        }
        if ($_FILES["file"]["error"] > 0)
        {
            // TODO: turn this into view
            echo "Error: " . $_FILES["file"]["error"] . "<br>";
        }
        else {
            $fm = new FileManager();
            $saveName = $fm->saveFile($_FILES["file"]);
            if ($ajax) {
	            $data = array(
	            	'uploadedSize' => $_FILES["file"]["size"],
	            	'downloadUrl' => $this->buildDownloadUrl($saveName),
	            	'downloadName' => $saveName
	            );
	            echo json_encode($data);
            }
            else {
            	$this->view->fileSize = ($_FILES["file"]["size"] / 1024);
	            $this->view->downloadURL = $this->buildDownloadUrl($saveName);
	            $this->view->render('success.tpl');
            }
        }
    }
    
    private function processDownloadfile($saveName) {
        $fm = new FileManager();
        if (!$fm->isFileExists($saveName)) {
            // error, requested file unexists
            return;
        }
        $name = $fm->getFileName($saveName);
        if ( preg_match("/MSIE/", $_SERVER["HTTP_USER_AGENT"]) ) {
        	$name = urlencode($name);
        }
        
        $size = $fm->getFileSize($saveName);
        header('Content-type: application/octet-stream; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"$name\"");
        header("Content-Length: $size");
        $fm->getFilecontent($saveName);
    }
    
    private function buildDownloadUrl($file) {
        return 'http://' . $_SERVER['SERVER_NAME'] . "/?dl=$file";
    }
}

class FileManager {
    public function __construct() {}
    public function __destruct() {}
    
    public function saveFile($fdata) {
        // make unique name
        do {
            $saveName = $this->getSaveName();
        } while ($this->isFileExists($saveName));
        
        $saveto = FILE_STORE . $saveName;
        move_uploaded_file($_FILES["file"]["tmp_name"], $saveto);
        
        // also save original name
        file_put_contents(FILE_STORE . "$saveName.name.txt", $_FILES["file"]["name"]);
        
        return $saveName;
    }
    
    public function getFileList($limit = null) {
        $files = array();
        $count = 0;
        
        $all_files = scandir(FILE_STORE, SCANDIR_SORT_DESCENDING);
        foreach ($all_files as $file) {
            if ($file != "." && $file != ".." && !strstr($file, '.txt') ) {
                $files[] = $file;
                $count++;
                if ($count >= $limit) {
                    break;
                }
            }
        }
        
        return $files;
    }
    
    public function isFileExists($saveName) {
        return file_exists(FILE_STORE . $saveName);
    }
    
    public function getFileName($saveName) {
        $name = file_get_contents(FILE_STORE . "$saveName.name.txt");
        return $name;
    }
    
    public function getFilecontent($saveName) {
        $file = FILE_STORE . $saveName;
        readfile($file);
    }
    
    public function getFileSize($saveName) {
        return filesize(FILE_STORE . $saveName);
    }
    
    private function getSaveName() {
        list($u, $s)= explode(" ",microtime());
        $saveName = dechex($s) . dechex($u * 10000);
        return $saveName;
    }
}

class Template {
    private $data;
    
    public function __get($property) {
        return $this->data[$property];
    }

    public function __set($property, $value) {
        $this->data[$property] = $value;
    }
    
    public function render($template) {
        require_once APP_ROOT . "tpl/$template";
    }
}