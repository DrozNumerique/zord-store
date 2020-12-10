<?php

abstract class StorePortal extends Portal {
    
    protected abstract function metadata($ean);
    
    public function unapi() {
        $formats = Zord::value('unapi', 'formats');
        $format = $formats[$this->params['format'] ?? DEFAULT_UNAPI_FORMAT] ?? null;
        if (isset($this->params['id']) && isset($format)) {
            return $this->view($format['template'], $this->metadata($this->params['id']), $format['type'], false, false);
        } else {
            return $this->view('/xml/unapi/formats', ['formats' => $formats], 'application/xml', false, false);
        }
    }
    
}

?>