<?php

class StoreAdmin extends Admin {
    
    protected function uploadImport($folder) {
        if (isset($_FILES['file'])) {
            $tmp = $_FILES['file']['tmp_name'];
            $file = $_FILES['file']['name'];
            $type = pathinfo($file, PATHINFO_EXTENSION);
            $name = basename($file);
            move_uploaded_file($tmp, $folder.$name);
            if ($type == 'zip') {
                $zip = new ZipArchive();
                if ($zip->open($folder.$name) === true) {
                    $zip->extractTo($folder);
                    $zip->close();
                }
            }
        }
    }
    
    protected function prepareImport($folder) {}
    
    public function import() {
        $folder = Import::getFolder();
        Zord::resetFolder($folder);
        $this->uploadImport($folder);
        $this->prepareImport($folder);
        return ProcessExecutor::start(
            Zord::getClassName('Import'),
            $this->user,
            $this->lang,
            Zord::objectToArray(json_decode($this->params['parameters']))
        );
    }
}

?>