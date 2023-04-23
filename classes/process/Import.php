<?php

class Import extends ProcessExecutor {

    protected $parameters = [];
    protected $folder     = null;
    protected $refs       = null;
    protected $steps      = null;
    protected $until      = null;
    protected $continue   = null;
    protected $execute    = true;
    
    protected $count    = 0;
    protected $progress = 0;
    protected $success  = 0;
    
    protected $done     = false;
    protected $error    = null;
    
    public function __construct() {
        $this->class    = 'Import';
        $this->folder   = self::getFolder();
        $this->continue = defined('IMPORT_CONTINUE') ? IMPORT_CONTINUE : false;
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
                $prefix = " ";
                $suffix = " ";
                $header = $prefix.$ean.$suffix;
                $pad = str_repeat("─", strlen($header));
                $this->report(0, 'info', "┌".$pad."┐");
                $this->report(0, 'info', "│".$header."│");
                $this->report(0, 'info', "└".$pad."┘");
                $this->report(1, 'info', $score);
                foreach ($this->steps as $step) {
                    if ($this->handle($this->execute, true, $ean, $step)) {
                        $this->report(1, 'info', $this->locale->steps->$step, true, true);
                        try {
                            if (method_exists($this, $step)) {
                                $this->done = $this->$step($ean);
                            } else {
                                $this->report(1, 'info', '', false, true);
                                $this->report(0, 'KO', $this->locale->steps->status->KO);
                                $this->report(2, 'error', $this->locale->steps->status->unknown);
                                if (!$this->handle($this->continue, false, $ean, $step)) break;
                            }
                        } catch(Throwable $thrown) {
                            $this->report(1, 'info', '', false, true);
                            $this->report(0, 'KO', $this->locale->steps->status->KO);
                            $this->report(2, 'bold', $thrown);
                            $this->done = false;
                            if (!$this->handle($this->continue, false, $ean, $step)) break;
                        }
                        $this->report(1, 'info', '', false, true);
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
        if (isset($parameters['lang'])) {
            $this->setLang($parameters['lang']);
        }
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
    
    protected function folders($ean) {
        return null;
    }
    
    protected function resources($ean) {
        $result = true;
        $folders = $this->folders($ean);
        if (!isset($folders) || !is_array($folders) || count($folders) !== 2) {
            $this->info(2, $this->locale->messages->resources->info->none);
            return true;
        }
        list($source,$target) = $folders;
        if (isset($target) && isset($source) && file_exists($source) && is_dir($source)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);
            if ($iterator->current()) {
                $this->info(2, $target);
                foreach ($iterator as $file) {
                    if (is_dir($file)) {
                        continue;
                    }
                    $name = substr($file, strlen($source.DS));
                    $this->info(3, $name);
                    $dir = dirname($target.$name);
                    if (!is_dir($dir)) {
                        mkdir($dir, 0777, true);
                    }
                    if (!copy($file, $target.$name)) {
                        $this->logError('resources', Zord::substitute($this->locale->messages->resources->error->copy, [
                            'source' => $file,
                            'target' => $target
                        ]));
                        $result = false;
                    }
                }
            } else {
                $this->info(2, $this->locale->messages->resources->info->none);
            }
        } else {
            $this->info(2, $this->locale->messages->resources->info->none);
        }
        return $result;
    }
    
    protected function contents($ean) {
        return null;
    }
        
    protected function index($ean) {
        $result = true;
        $contents = $this->contents($ean);
        if (!isset($contents) || !is_array($contents) || count($contents) === 0) {
            $this->info(2, $this->locale->messages->resources->info->none);
            return true;
        }
        $matches = Zord::value('index', ['match','fields']);
        $keys = array_keys(Zord::value('index', 'fields'));
        if (!empty($contents)) {
            list($index, $key, , $field, $delete) = Store::deindex($ean, false);
            if ($delete->success()) {
                foreach ($contents as $content) {
                    if (!isset($content[$key])) {
                        $this->logError('index', Zord::substitute($this->locale->messages->index->error->missing, [
                            'key'     => $key,
                            'content' => $content['name']
                        ]));
                        $result = false;
                        continue;
                    }
                    $document = new SolrInputDocument();
                    $document->addField('id', $ean.'_'.$content['name']);
                    foreach ($keys as $key) {
                        $value = $content[$key] ?? Zord::value('index', ['default',$key]);
                        if (isset($value)) {
                            $field = Store::field($key);
                            if (!is_array($value)) {
                                $value = [$value];
                            }
                            foreach ($value as $item) {
                                $document->addField($field, $item);
                            }
                            if (in_array($key, $matches ?? [])) {
                                $field = Store::field($key, true);
                                foreach ($value as $item) {
                                    $document->addField($field, Zord::collapse($item, false));
                                }
                            }
                        }
                    }
                    $update = $index->addDocument($document);
                    if (!$update->success()) {
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
