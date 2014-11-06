<?php

class AzUpload
{
    private $view;

    public function __construct()
    {
        $this->view = new Template();
    }

    public function handleRequest()
    {
        if (isset($_GET['act']) && 'upload' == $_GET['act']) {
            $ajax = (isset($_GET['ajax']) && 'true' == $_GET['ajax']) ? true : false;
            $this->processUploadFile($ajax);
        } elseif (isset($_GET['dl']) && !empty($_GET['dl'])) {
            $this->processDownloadFile($_GET['dl']);
        } else {
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

    private function processUploadFile($ajax = false)
    {
        if (empty($_FILES['file']['name'])) {
            return false;
        }
        if ($_FILES["file"]["error"] > 0) {
            // TODO: turn this into view
            echo "Error: " . $_FILES["file"]["error"] . "<br>";
        } else {
            $fm = new FileManager();
            $saveName = $fm->saveFile($_FILES["file"]);
            if ($ajax) {
                $data = array(
                    'uploadedSize' => $_FILES["file"]["size"],
                    'downloadUrl' => $this->buildDownloadUrl($saveName),
                    'downloadName' => $saveName
                );
                echo json_encode($data);
            } else {
                $this->view->fileSize = ($_FILES["file"]["size"] / 1024);
                $this->view->downloadURL = $this->buildDownloadUrl($saveName);
                $this->view->render('success.tpl');
            }
        }
    }

    private function processDownloadfile($saveName)
    {
        $fm = new FileManager();
        if (!$fm->isFileExists($saveName)) {
            // error, requested file unexists
            return;
        }
        $name = $fm->getFileName($saveName);
        if (preg_match("/MSIE/", $_SERVER["HTTP_USER_AGENT"])) {
            $name = urlencode($name);
        }

        $size = $fm->getFileSize($saveName);
        header('Content-type: application/octet-stream; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"$name\"");
        header("Content-Length: $size");
        $fm->sendFilecontent($saveName);
    }

    private function buildDownloadUrl($file)
    {
        return 'http://' . $_SERVER['SERVER_NAME'] . "/?dl=$file";
    }
}

class FileManager
{
    private $fdata;

    public function __construct()
    {
        $this->fdata = new FileData();
    }

    public function saveFile($fdata)
    {
        // make unique name
        do {
            $saveName = $this->getSaveName();
        } while ($this->isFileExists($saveName));

        $saveto = FILE_STORE . $saveName;
        move_uploaded_file($_FILES["file"]["tmp_name"], $saveto);

        // also save original name
        $this->fdata->saveFileName($_FILES["file"]["name"], $saveName);

        return $saveName;
    }

    public function getFileList($limit = 10)
    {
        return $this->fdata->getFileList($limit);
    }

    public function isFileExists($saveName)
    {
        return file_exists(FILE_STORE . $saveName);
    }

    public function getFileName($saveName)
    {
        return $this->fdata->getFileName($saveName);
    }

    public function sendFilecontent($saveName)
    {
        $file = FILE_STORE . $saveName;
        readfile($file);
    }

    public function getFileSize($saveName)
    {
        return filesize(FILE_STORE . $saveName);
    }

    private function getSaveName()
    {
        list($u, $s) = explode(" ", microtime());
        //$saveName = dechex($s) . dechex($u * 10000);
        $saveName = dechex($u * 10000);
        return $saveName;
    }
}

class Template
{
    private $data;

    public function __get($property)
    {
        return $this->data[$property];
    }

    public function __set($property, $value)
    {
        $this->data[$property] = $value;
    }

    public function render($template)
    {
        require_once "tpl" . DIRECTORY_SEPARATOR . $template;
    }
}

class FileData
{
    private $pdo = null;

    public function __construct()
    {
        $this->initPDO();
    }

    private function initPDO()
    {
        try {
            $need_install = false;
            $dbFile = DB_STORE . DIRECTORY_SEPARATOR . "file.db";
            if (!file_exists($dbFile)) {
                $need_install = true;
            }

            $this->pdo = new PDO("sqlite:$dbFile", null, null);
            if ($need_install) {
                $this->pdo->exec("CREATE TABLE files(id INTEGER PRIMARY KEY AUTOINCREMENT, filename, savename);");
            }
        } catch (PDOException $e) {
            $this->pdo = null;
        }
    }

    public function getFileName($saveName)
    {
        $sth = $this->pdo->prepare("SELECT filename FROM files WHERE savename=:savename;");
        $sth->execute(array(':savename' => $saveName));
        $data = $sth->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return $saveName;
        }

        return $data['filename'];
    }

    public function saveFileName($filename, $saveName)
    {
        $sth = $this->pdo->prepare("INSERT INTO files (filename, savename) VALUES (:filename, :savename);");
        $value = array(
            ":filename" => $filename,
            ":savename" => $saveName
        );
        $sth->execute($value);
    }
    
    public function getFileList($limit = 10)
    {
        static $sth = null;
        if (!$sth) {
            $sth = $this->pdo->prepare("SELECT savename FROM files ORDER BY id DESC LIMIT :limit;");
        }
        $sth->execute(array(':limit' => $limit));
        $datas = $sth->fetchAll(PDO::FETCH_ASSOC);
        $files = array_map(function($e) {return $e['savename'];}, $datas);
        
        return $files;
    }
}
