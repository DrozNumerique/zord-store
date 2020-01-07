<?php

class Store {
    
    public static function getImportFolder() {
        return Zord::liveFolder('import');
    }
    
	public static function data($isbn, $type = 'path', $format = 'path') {
	    $base = DATA_FOLDER.'zord'.DS.$isbn;
	    $path = null;
	    $data = null;
	    switch ($type) {
	        case 'path': {
	            $path = $base.DS;
	            break;
	        }
	        case 'temp': {
	            $path = $base.'.tmp'.DS;
	            break;
	        }
	        case 'meta': {
	            $path = $base.DS.'metadata.json';
	            break;
	        }
	        default: {
	            $file = Zord::value('store', $type);
	            if (isset($file)) {
	                $path = $base.DS.$file;
	            } else {
	                $ext = Zord::value('store', 'default');
	                $path = $base.DS.$type.'.'.$ext;
	            }
	            break;
	        }
	    }
	    if (isset($path)) {
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
	    }
	    return $data;
	}
	
	public static function media($isbn, $names = null, $types = ['jpg','png']) {
	    if (!isset($names)) {
	        return DATA_FOLDER.'medias'.DS.$isbn.DS;
	    }
	    if (!is_array($names)) {
	        $names = [$names];
	    }
	    if (!is_array($types)) {
	        $types = [$types];
	    }
	    foreach ($names as $name) {
	        foreach ($types as $ext) {
	            $media = 'medias'.DS.$isbn.DS.$name.'.'.$ext;
	            if (file_exists(DATA_FOLDER.$media)) {
	                return $media;
	            }
	        }
	    }
	    return false;
	}
}
