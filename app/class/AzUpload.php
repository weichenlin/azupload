<?php

class AzUpload {
    private $view;
    
    public function __construct() {
        $this->view = new Template();
    }
    public function __destruct() {}
    
    public function handleRequest() {
        if ('upload' == $_GET['act']) {
            $this->processUploadFile();
        }
        elseif (!empty($_GET['dl'])) {
            $this->processDownloadFile($_GET['dl']);
        }
        else {
            $this->view->render('upload.tpl');
        }
    }
    
    private function processUploadFile() {
        if (empty($_FILES['file']['name'])) {
            $this->view->render('upload.tpl');
            return false;
        }
        if ($_FILES["file"]["error"] > 0)
        {
            // TODO: turn this into view
            echo "Error: " . $_FILES["file"]["error"] . "<br>";
        }
        else
        {
            $fm = new FileManager();
            $saveName = $fm->saveFile($_FILES["file"]);
            $this->view->fileSize = ($_FILES["file"]["size"] / 1024);
            $this->view->downloadURL = 'http://' . $_SERVER['SERVER_NAME'] . "/?dl=$saveName";
            $this->view->render('success.tpl');
        }
    }
    
    private function processDownloadfile($saveName) {
        $fm = new FileManager();
        if (!$fm->isFileExists($saveName)) {
            // error, requested file unexists
            return;
        }
        $name = $fm->getFileName($saveName);
        header('Content-type: application/octet-stream; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"$name\"");
        $fm->getFilecontent($saveName);
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