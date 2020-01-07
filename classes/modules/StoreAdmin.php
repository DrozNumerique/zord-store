<?php

class StoreAdmin extends Admin {
    
    public function import() {
        $tmp = $_FILES['file']['tmp_name'];
        $file = $_FILES['file']['name'];
        $type = pathinfo($file, PATHINFO_EXTENSION);
        $name = basename($file);
        $folder = Store::getImportFolder();
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
            file_put_contents(Store::getImportFolder().'publish.json', Zord::json_encode($publish));
        }
        $parameters = Zord::objectToArray(json_decode($this->params['parameters']));
        return ['pid' => ProcessExecutor::start(Zord::getClassName('Import'), $this->user, $this->lang, $parameters)];
    }
    
    public function publish() {
        if (isset($this->params['name']) &&
            isset($this->params['books'])) {
            $name = $this->params['name'];
            $books = Zord::objectToArray(json_decode($this->params['books']));
            (new BookHasContextEntity())->delete([
                'many' => true,
                'where' => ['context' => $name]
            ]);
            foreach ($books as $book) {
                if ($book['status'] !== 'del') {
                    (new BookHasContextEntity())->create([
                        'book'    => $book['isbn'],
                        'context' => $name,
                        'status'  => $book['status']
                    ]);
                } else if ($this->user->isManager()) {
                    (new BookEntity())->delete($book['isbn'], true);
                    foreach($this->deletePaths($book['isbn']) as $path) {
                        Zord::deleteRecursive(DATA_FOLDER.$path);
                    }
                }
            }
        }
        return $this->index('publish');
    }
    
    protected function deletePaths($isbn) {
        return [
            'medias'.DS.$isbn,
            'zord'.DS.$isbn,
        ];
    }
}

?>