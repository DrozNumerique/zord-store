<?php

class Import extends ProcessExecutor {

    protected $parameters = [];
    protected $folder     = null;
    protected $refs       = null;
    protected $steps      = null;
    protected $until      = null;
    protected $continue   = IMPORT_CONTINUE;
    protected $execute    = true;
    
    protected $count    = 0;
    protected $progress = 0;
    protected $success  = 0;
    
    protected $done     = false;
    protected $error    = null;
    
    public function __construct() {
        $this->class  = 'Import';
        $this->folder = self::getFolder();
    }
    
    public function execute($parameters = []) {
        try {
            $this->configure($parameters);
        } catch (Throwable $thrown) {
            $this->report(0, 'bold', $thrown);
            return;
        }
        if (!isset($this->refs) || count($this->refs) == 0) {
            $this->report(0, 'warn', $this->locale->execute->nodata);
            $this->report();
        } else {
            try {
                $this->preRefs();
            } catch (Throwable $thrown) {
                $this->report(0, 'bold', $thrown);
                return;
            }
            $this->report(0, 'info', Zord::substitute($this->locale->execute->start, [
                'X' => count($this->refs), 
            ]));
            $this->report();
            foreach ($this->refs as $ean) {
                $score = $this->locale->reference.' '.($this->count + 1).' / '.count($this->refs);
                try {
                    $this->resetRef($ean);
                    $this->progress(round(100 * $this->progress));
                    $this->step($score);
                } catch (Throwable $thrown) {
                    $this->report(0, 'bold', $thrown);
                    continue;
                }
                $this->report(0, 'info', "┌─────────────────────┐");
                $this->report(0, 'info', "│ EAN : " . $ean . " │");
                $this->report(0, 'info', "└─────────────────────┘");
                $this->report(1, 'info', $score);
                foreach ($this->steps as $step) {
                    if ($this->handle($this->execute, true, $ean, $step)) {
                        $this->report(1, 'info', Zord::str_pad($this->locale->steps->$step, 50, "."));
                        try {
                            if (method_exists($this, $step)) {
                                $this->done = $this->$step($ean);
                            } else {
                                $this->report(1, 'info', Zord::str_pad('', 50 - mb_strlen($this->locale->steps->status->KO), "."), false);
                                $this->report(0, 'KO', $this->locale->steps->status->KO);
                                $this->report(2, 'error', $this->locale->steps->status->unknown);
                                if (!$this->handle($this->continue, false, $ean, $step)) break;
                            }
                        } catch(Throwable $thrown) {
                            $this->report(1, 'info', Zord::str_pad('', 50 - mb_strlen($this->locale->steps->status->KO), "."), false);
                            $this->report(0, 'KO', $this->locale->steps->status->KO);
                            $this->report(2, 'bold', $thrown);
                            $this->done = false;
                            if (!$this->handle($this->continue, false, $ean, $step)) break;
                        }
                        $this->report(1, 'info', Zord::str_pad('', 50 - mb_strlen($this->done ? $this->locale->steps->status->OK : $this->locale->steps->status->KO), "."), false);
                        $this->report(0, $this->done ? 'OK' : 'KO', $this->done ? $this->locale->steps->status->OK : $this->locale->steps->status->KO);
                        if ((!$this->done && !$this->handle($this->continue, false, $ean, $step)) || $step == $this->until) {
                            break;
                        }
                    }
                }
                $this->report();
                if ($this->done) {
                    $this->success++;
                } else if (!$this->handle($this->continue, false, $ean))  {
                    break;
                }
            }
            $this->report(0, 'info', Zord::substitute($this->locale->execute->end, [
                'X' => count($this->refs),
                'Y' => $this->success
            ]));
            $this->report();
            try {
                $this->postRefs();
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
        if (isset($parameters['refs'])) {
            $this->refs = $parameters['refs'];
            if (!is_array($this->refs)) {
                $this->refs = [$this->refs];
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
    
    protected function preRefs() {}
    
    protected function postRefs() {}
    
    protected function resetRef($ean) {
        $this->count++;
        $this->done = false;
        $this->progress = $this->count / count($this->refs);
        $this->error = LOGS_FOLDER.$ean.'.error.log';
        if (file_exists($this->error)) {
            unlink($this->error);
        }
    }
    
    protected function metadata($ean) {
        return true;
    }
    
    protected function medias($ean) {
        $result = true;
        $folder = $this->folder.$ean.DS;
        if (file_exists($folder) && is_dir($folder)) {
            $target = Store::media($ean);
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder), RecursiveIteratorIterator::SELF_FIRST);
            if ($iterator->current()) {
                $this->info(2, $target);
                foreach ($iterator as $file) {
                    if (is_dir($file)) {
                        continue;
                    }
                    $name = substr($file, strlen($folder));
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
    
    protected function contents($ean) {
        return null;
    }
        
    protected function index($ean) {
        $result = true;
        $contents = $this->contents($ean);
        if (!empty($contents)) {
            $index = new SolrClient(Zord::value('connection', ['solr','zord']));
            $key = Zord::value('index', 'key');
            $type = Zord::value('index', ['fields',$key]);
            $field = $key.Zord::value('index', ['suffix',$type]);
            $delete = $index->deleteByQuery($field.':'.$ean);
            $response = $delete->getResponse();
            if ($response) {
                foreach ($contents as $content) {
                    if (!isset($content[$key])) {
                        $this->logError('index', Zord::substitute($this->locale->messages->index->error->key), [
                            'key'     => $key,
                            'content' => $content['name']
                        ]);
                        $result = false;
                        continue;
                    }
                    $document = new SolrInputDocument();
                    $document->addField('id', $ean.'_'.$content['name']);
                    foreach (Zord::value('index', 'fields') as $key => $type) {
                        $value = $content[$key] ?? Zord::value('index', ['default',$key]);
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
                            'content' => $content['name']
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
    
    protected function handle($operation, $default, $ean, $step = null) {
        if ($operation === !$default) {
            return !$default;
        } else if (is_array($operation)) {
            if (isset($operation[$ean])) {
                if ($operation[$ean] === !$default) {
                    return !$default;
                } else if (is_array($operation[$ean])) {
                    if (isset($operation[$ean][$step]) && $operation[$ean][$step] === !$default) {
                        return !$default;
                    }
                }
            } else if (isset($step) && isset($operation[$step])) {
                if ($operation[$step] === !$default) {
                    return !$default;
                } else if (is_array($operation[$step])) {
                    if (isset($operation[$step][$ean]) && $operation[$step][$ean] === !$default) {
                        return !$default;
                    }
                }
            }
        }
        return $default;
    }
    
    public static function getFolder() {
        return Zord::liveFolder('import');
    }
}

?>
