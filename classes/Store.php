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
	
	public static function deindex($ean, $commit = true) {
	    $index = new SolrClient(Zord::value('connection', ['solr','zord']));
	    $key = Zord::value('index', 'key');
	    $type = Zord::value('index', ['fields',$key]);
	    $field = $key.Zord::value('index', ['suffix',$type]);
	    $delete = $index->deleteByQuery($field.':'.$ean);
	    if ($commit && $delete->success()) {
	        $index->commit();
	    }
	    return [$index, $key, $type, $field, $delete];
	}
	
	public static function field($key, $collapse = false) {
	    foreach (Zord::value('index', 'fields') as $field => $type) {
	        if ($key == $field) {
	            return $key.($collapse ? '_collapse' : '').Zord::value('index', ['suffix',$type]);
	        }
	    }
	    return false;
	}
	
	public static function match($keywords, $values = null, $rows = 10000) {
	    $config = Zord::value('index', 'match');
	    $keywords = implode(' AND ', array_map(function($val) {
	        return '*'.Zord::collapse($val, false).'*';
	    }, explode(' ', str_replace(['(',')','*',':'], ' ', $keywords))));
        $results = [];
        $client = new SolrClient(Zord::value('connection', ['solr','zord']));
        $query  = new SolrQuery();
        $query->setQuery('*:*');
        $query->setStart(0);
        $query->setRows($rows);
        $query->addField(self::field(Zord::value('index', 'key')));
        $filter = implode(' AND ', array_map(function($key, $value) use ($values) {
            $field = self::field($key);
            if ($field === false) {
                $field = $key;
            }
            return $field.':('.Zord::substitute($value, $values).')';
        }, array_keys($config['select']), array_values($config['select']))).' AND ';
        $filter .= '('.implode(' OR ', array_map(function($key) use ($keywords) {
            return self::field($key, true).':('.$keywords.')';
        }, $config['fields'])).')';
        $query->addFilterQuery($filter);
        $result = $client->query($query);
        $result = Zord::objectToArray(json_decode($result->getRawResponse()));
        if (!empty($result['response']['docs'])) {
            foreach ($result['response']['docs'] as $doc) {
                $ean = $doc['ean_s'];
                if (!in_array($ean, $results)) {
                    $results[] = $ean;
                }
            }
        }
        return $results;
	}
	
}
