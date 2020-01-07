<?php

abstract class Import extends ProcessExecutor {

    protected $parameters = [];
    protected $folder     = null;
    protected $books      = null;
    protected $steps      = null;
    protected $until      = null;
    protected $continue   = IMPORT_CONTINUE;
    protected $execute    = true;
    
    protected $count    = 0;
    protected $progress = 0;
    protected $success  = 0;
    
    protected $done     = false;
    protected $error    = null;
    
    protected abstract function book($isbn, $metadata);
    protected abstract function parts($isbn, $metadata);
    
    public function __construct() {
        $this->class  = 'Import';
        $this->folder = Store::getImportFolder();
    }
    
    public function execute($parameters = []) {
        try {
            $this->configure($parameters);
        } catch (Throwable $thrown) {
            $this->report(0, 'bold', $thrown);
            return;
        }
        if (count($this->books) == 0) {
            $this->report(0, 'warn', $this->locale->execute->nodata);
            $this->report();
        } else {
            try {
                $this->preBooks();
            } catch (Throwable $thrown) {
                $this->report(0, 'bold', $thrown);
                return;
            }
            $this->report(0, 'info', Zord::substitute($this->locale->execute->start, [
                'X' => count($this->books), 
            ]));
            $this->report();
            foreach ($this->books as $isbn) {
                $score = $this->locale->book.' '.($this->count + 1).' / '.count($this->books);
                try {
                    $this->resetBook($isbn);
                    $this->progress(round(100 * $this->progress));
                    $this->step($score);
                } catch (Throwable $thrown) {
                    $this->report(0, 'bold', $thrown);
                    continue;
                }
                $this->report(0, 'info', "┌──────────────────────┐");
                $this->report(0, 'info', "│ ISBN : " . $isbn . " │");
                $this->report(0, 'info', "└──────────────────────┘");
                $this->report(1, 'info', $score);
                foreach ($this->steps as $step) {
                    $this->report(1, 'info', Zord::str_pad($this->locale->steps->$step, 50, "."));
                    if ($this->handle($this->execute, true, $isbn, $step)) {
                        try {
                            if (method_exists($this, $step)) {
                                $this->done = $this->$step($isbn);
                            } else {
                                $this->report(1, 'info', Zord::str_pad('', 50 - mb_strlen($this->locale->steps->status->KO), "."), false);
                                $this->report(0, 'KO', $this->locale->steps->status->KO);
                                $this->report(2, 'error', $this->locale->steps->status->unknown);
                                if (!$this->handle($this->continue, false, $isbn, $step)) break;
                            }
                        } catch(Throwable $thrown) {
                            $this->report(1, 'info', Zord::str_pad('', 50 - mb_strlen($this->locale->steps->status->KO), "."), false);
                            $this->report(0, 'KO', $this->locale->steps->status->KO);
                            $this->report(2, 'bold', $thrown);
                            $this->done = false;
                            if (!$this->handle($this->continue, false, $isbn, $step)) break;
                        }
                        $this->report(1, 'info', Zord::str_pad('', 50 - mb_strlen($this->done ? $this->locale->steps->status->OK : $this->locale->steps->status->KO), "."), false);
                        $this->report(0, $this->done ? 'OK' : 'KO', $this->done ? $this->locale->steps->status->OK : $this->locale->steps->status->KO);
                        if ((!$this->done && !$this->handle($this->continue, false, $isbn, $step)) || $step == $this->until) {
                            break;
                        }
                    }
                }
                $this->report();
                if ($this->done) {
                    $this->success++;
                } else if (!$this->handle($this->continue, false, $isbn))  {
                    break;
                }
            }
            $this->report(0, 'info', Zord::substitute($this->locale->execute->end, [
                'X' => count($this->books),
                'Y' => $this->success
            ]));
            $this->report();
            try {
                $this->postBooks();
            } catch (Throwable $thrown) {
                $this->report(0, 'bold', $thrown);
                return;
            }
        }
    }
    
    protected function configure($parameters = []) {
        $this->parameters = $parameters;
        if (isset($parameters['folder'])) {
            $this->folder = $parameters['folder'];
            if (substr($this->folder, -strlen(DS), strlen(DS)) != DS) {
                $this->folder = $this->folder.DS;
            }
        }
        if (isset($parameters['books'])) {
            $this->books = $parameters['books'];
            if (!is_array($this->books)) {
                $this->books = [$this->books];
            }
        }
        if (isset($parameters['steps'])) {
            $this->steps = $parameters['steps'];
            if (!is_array($this->steps)) {
                $this->steps = [$this->steps];
            }
        }
        if (!isset($this->steps)) {
            $this->steps = Zord::value('import', 'steps');
        }
        if (isset($parameters['until'])) {
            $this->until = $parameters['until'];
        }
        if (isset($parameters['continue'])) {
            $this->continue = $parameters['continue'];
        }
    }
    
    protected function preBooks() {}
    
    protected function postBooks() {}
    
    protected function resetBook($isbn) {
        $this->count++;
        $this->done = false;
        $this->progress = $this->count / count($this->books);
        $folder = Store::data($isbn);
        if (!file_exists($folder)) {
            mkdir($folder);
        }
        $this->error = LOGS_FOLDER.$isbn.'.error.log';
        if (file_exists($this->error)) {
            unlink($this->error);
        }
    }
    
    protected function metadata($isbn) {
        $result = true;
        if (!file_exists(Store::data($isbn, 'meta'))) {
            $this->logError('metadata', $this->locale->messages->metadata->error->missing);
            $result = false;
        } else {
            $book = $this->book($isbn, Store::data($isbn, 'meta', 'array'));
            if ((new BookEntity())->retrieve($isbn)) {
                (new BookEntity())->update($isbn, $book);
            } else {
                (new BookEntity())->create($book);
            }
        }
        return $result;
    }
    
    protected function medias($isbn) {
        $result = true;
        $folder = $this->folder.$isbn;
        if (file_exists($folder) && is_dir($folder)) {
            $target = Store::media($isbn);
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder), RecursiveIteratorIterator::SELF_FIRST);
            if ($iterator->current()) {
                $this->info(2, $target);
                foreach ($iterator as $file) {
                    if (is_dir($file)) {
                        continue;
                    }
                    $name = substr($file, strlen($folder) + 1);
                    $this->info(3, $name);
                    $dir = dirname($target.$name);
                    if (!is_dir($dir)) {
                        mkdir($dir, 0777, true);
                    }
                    if (!copy($file, $target.$name)) {
                        $this->logError('medias', Zord::substitute($this->locale->messages->medias->error->copy, [
                            'source' => $file,
                            'target' => $target
                        ]));
                        $result = false;
                    }
                }
            } else {
                $this->info(2, $this->locale->messages->medias->info->nomedia);
            }
        } else {
            $this->info(2, $this->locale->messages->medias->info->nomedia);
        }
        return $result;
    }
        
    protected function index($isbn) {
        $result = true;
        $metadata = Store::data($isbn, 'meta', 'array');
        $parts = $this->parts($isbn, $metadata);
        if (!empty($parts)) {
            $index = new SolrClient(Zord::value('connection', ['solr','zord']));
            $delete = $index->deleteByQuery('ean_s:'.$isbn);
            $response = $delete->getResponse();
            if ($response) {
                foreach ($parts as &$part) {
                    $document = new SolrInputDocument();
                    $document->addField('id', $isbn.'_'.$part['name']);
                    foreach (Zord::value('index', 'fields') as $key => $type) {
                        $default = Zord::value('index', ['default',$key]);
                        $value = isset($part[$key]) ? $part[$key] : (isset($metadata[$key]) ? $metadata[$key] : (isset($default) ? $default : null));
                        if (isset($value)) {
                            $field = $key.Zord::value('index', ['suffix',$type]);
                            if (!is_array($value)) {
                                $value = [$value];
                            }
                            foreach ($value as $item) {
                                $document->addField($field, $item);
                            }
                        }
                    }
                    $update = $index->addDocument($document);
                    $response = $update->getResponse();
                    if (!$response) {
                        $this->logError('index', Zord::substitute($this->locale->messages->index->error->add), [
                            'part' => $part['name']
                        ]);
                        $result = false;
                    }
                }
                $index->commit();
            } else {
                $this->logError('index', $this->locale->messages->index->error->delete);
                $result = false;
            }
        } else {
            $this->logError('index', $this->locale->messages->index->error->nodata);
            $result = false;
        }
        return $result;
    }
    
    protected function logError($step, $message) {
        parent::error(2, $message, true);
        file_put_contents($this->error, '['.$step.'] '.$message."\n", FILE_APPEND);
    }
    
    private function handle($operation, $default, $isbn, $step = null) {
        if ($operation === !$default) {
            return !$default;
        } else if (is_array($operation)) {
            if (isset($operation[$isbn])) {
                if ($operation[$isbn] === !$default) {
                    return !$default;
                } else if (is_array($operation[$isbn])) {
                    if (isset($operation[$isbn][$step]) && $operation[$isbn][$step] === !$default) {
                        return !$default;
                    }
                }
            } else if (isset($step) && isset($operation[$step])) {
                if ($operation[$step] === !$default) {
                    return !$default;
                } else if (is_array($operation[$step])) {
                    if (isset($operation[$step][$isbn]) && $operation[$step][$isbn] === !$default) {
                        return !$default;
                    }
                }
            }
        }
        return $default;
    }
}

?>
