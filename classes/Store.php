<?php

class Store {
	
	public static function resource($category, $ean, $names = null, $types = ['jpg','png']) {
	    if (!isset($names)) {
	        return STORE_FOLDER.$category.DS.$ean.DS;
	    }
	    if (isset($names) && !is_array($names)) {
	        $names = [$names];
	    }
	    if (isset($types) && !is_array($types)) {
	        $types = [$types];
	    }
	    foreach ($names as $name) {
	        if (!isset($types) || empty($types)) {
	            $resource = $category.DS.$ean.DS.$name;
	            if (file_exists(STORE_FOLDER.$resource)) {
	                return $resource;
	            }
	        } else {
    	        foreach ($types as $ext) {
    	            $resource = $category.DS.$ean.DS.$name.'.'.$ext;
    	            if (file_exists(STORE_FOLDER.$resource)) {
    	                return $resource;
    	            }
    	        }
	        }
	    }
	    return false;
	}
	
	public static function isbn($ean) {
	    $tokens = [];
	    $pos = 0;
	    foreach ([3,1,3,5,1] as $length) {
	        $tokens[] = substr($ean, $pos, $length);
	        $pos += $length;
	    }
	    return implode('-', $tokens);
	}
	
	public static function data($path = null, $format = 'path') {
	    $data = null;
	    switch ($format) {
	        case 'path': {
	            $data = $path;
	            break;
	        }
	        case 'content': {
	            $data = file_get_contents($path);
	            break;
	        }
	        case 'object': {
	            $data = json_decode(file_get_contents($path));
	            break;
	        }
	        case 'array': {
	            $data = Zord::arrayFromJSONFile($path);
	            break;
	        }
	        case 'document': {
	            $data = new DOMDocument();
	            $data->load($path);
	            break;
	        }
	        case 'xml': {
	            $data = simplexml_load_string(file_get_contents($path), "SimpleXMLElement", LIBXML_NOCDATA);
	            break;
	        }
	    }
	    return $data;
	}
	
	public static function deindex($ean) {
	    $index = new SolrClient(Zord::value('connection', ['solr','zord']));
	    $key = Zord::value('index', 'key');
	    $type = Zord::value('index', ['fields',$key]);
	    $field = $key.Zord::value('index', ['suffix',$type]);
	    return $index->deleteByQuery($field.':'.$ean);
	}
}
