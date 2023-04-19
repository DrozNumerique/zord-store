<?php

class MinMaxFilter extends Filter {
    
    public function add(&$query, $field, $value) {
        if (isset($value['min']) && !empty($value['min']) && isset($value['max']) && !empty($value['max'])) {
            $query->addFilterQuery($field.':['.$value['min'].' TO '.$value['max'].']');
        }
    }
    
}

?>