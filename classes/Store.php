<?php

class Store {
    
    private static $SOLR_ORDERS = [
        "ASC"  => SolrQuery::ORDER_ASC,
        "DESC" => SolrQuery::ORDER_DESC
    ];
    
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
	
	public static function query($criteria) {
	    $query  = new SolrQuery();
	    if (!isset($criteria['operator']) || empty($criteria['operator'])) {
	        $criteria['operator'] = Zord::value('portal', ['default','search','operator']);
	    }
	    if (isset($criteria['query']) && !empty($criteria['query'])) {
	        $query->setQuery($criteria['query']);
	    } else {
	        $query->setQuery('*:*');
	        $criteria['query'] = '';
	        $criteria['rows'] = SEARCH_PAGE_MAX_SIZE;
	    }
	    $filters = [];
	    foreach (($criteria['filters'] ?? []) as $key => $value) {
	        $field = Store::field($key);
	        $filter = Zord::value('search', ['filters',$key]);
	        if ($filter) {
	            $filter = new $filter();
	            $filter->add($query, $field, $value);
	        } else {
	            $filter = null;
	            if (!is_array($value)) {
	                if (strpos($value, '*') === false) {
	                    $value = '"'.$value.'"';
	                }
	                $filter = $field.':'.$value;
	            } else if (count($value) > 0) {
	                $filter = $field.':('.implode(' ', array_map(function($val) use($field) {
	                    return '"'.$val.'"';
	                }, $value)).')';
	            }
	            if ($filter) {
	                if (in_array($key, Zord::value('search', 'facets') ?? [])) {
	                    $filters[] = $filter;
	                } else {
	                    $query->addFilterQuery($filter);
	                }
	            }
	        }
	    }
	    if (!empty($filters)) {
	        $query->addFilterQuery('('.implode(' '.$criteria['operator'].' ', $filters).')');
	    }
	    $query->addField('id');
	    foreach (Zord::value('search', ['fetch']) as $key) {
	        $query->addField(self::field($key));
	    }
	    foreach (Zord::value('search', ['sort']) as $key => $order) {
	        $query->addSortField(self::field($key), self::$SOLR_ORDERS[$order]);
	    }
	    $criteria['rows'] = $criteria['rows'] ?? SEARCH_PAGE_DEFAULT_SIZE;
	    if ($criteria['rows'] > SEARCH_PAGE_MAX_SIZE) {
	        $criteria['rows'] = SEARCH_PAGE_MAX_SIZE;
	    }
	    $criteria['start'] = $criteria['start'] ?? 0;
	    $query->setStart($criteria['start']);
	    $query->setRows($criteria['rows']);
	    return $query;
	}
	
	public static function search($query) {
	    if (is_array($query)) {
	        $query = self::query($query);
	    }
	    $client = new SolrClient(Zord::value('connection', ['solr','zord']));
	    $result = $client->query($query);
	    $result = Zord::objectToArray(json_decode($result->getRawResponse()));
	    return [
	        $result['response']['numFound'] ?? 0,
	        $result['response']['docs'] ?? [],
	        $result['highlighting'] ?? []
	    ];
	}

	public static function align($content, $type, $collapse = false) {
	    switch ($type) {
	        case 'xml':
	        case 'xhtml':
	        case 'html': {
	            $content = html_entity_decode(strip_tags(str_replace(
	                '<br/>', ' ',
	                $content
	            )), ENT_QUOTES | ENT_XML1, 'UTF-8');
	            break;
	        }
	        case 'pdf':
	        case 'txt': {
	            break;
	        }
	    }
	    $chunks = explode("\n", wordwrap(trim(preg_replace(
	        '#\s+#s', ' ', $content
	    )), INDEX_MAX_CONTENT_LENGTH));
	    if ($collapse) {
	        foreach ($chunks as $index => $chunk) {
	            $chunks[$index] = Zord::collapse($chunk, false);
	        }
	    }
	    return $chunks;
	}
	
}
