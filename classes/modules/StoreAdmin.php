<?php

class StoreAdmin extends Admin {
    
    public function import() {
        $tmp = $_FILES['file']['tmp_name'];
        $file = $_FILES['file']['name'];
        $type = pathinfo($file, PATHINFO_EXTENSION);
        $name = basename($file);
        $folder = Import::getFolder();
        Zord::resetFolder($folder);
        move_uploaded_file($tmp, $folder.$name);
        if ($type == 'zip') {
            $zip = new ZipArchive();
            if ($zip->open($folder.$name) === true) {
                $zip->extractTo($folder);
                $zip->close();
                $dirs = glob($folder.'*', GLOB_ONLYDIR);
                foreach ($dirs as $dir) {
                    $files = glob($dir.DS.'*');
                    $same = false;
                    foreach ($files as $file) {
                        $new = $folder.basename($file);
                        if (is_dir($file) && file_exists($new)) {
                            $same = true;
                            foreach (glob($file.DS.'*') as $sub) {
                                rename($sub, $new.DS.basename($sub));
                            }
                            rmdir($file);
                        } else {
                            rename($file, $new);
                        }
                    }
                    if (!$same) {
                        rmdir($dir);
                    }
                }
            }
        }
        $publish = [];
        foreach (array_keys(Zord::getConfig('context')) as $name) {
            if (isset($this->params[$name]) && $this->params[$name] !== 'no') {
                $publish[$name] = $this->params[$name];
            }
        }
        if (!empty($publish)) {
            file_put_contents(Import::getFolder().'publish.json', Zord::json_encode($publish));
        }
        $parameters = Zord::objectToArray(json_decode($this->params['parameters']));
        return ['pid' => ProcessExecutor::start(Zord::getClassName('Import'), $this->user, $this->lang, $parameters)];
    }
}

?>