<?php
    
class DateFilter extends Filter {
    
    protected $range = null;
    
    public function add(&$query, $field, $value) {
        if (isset($value['to']) && !empty($value['to'])) {
            $field = isset($this->range) ? $this->range.'_from_s' : $field;
            $query->addFilterQuery($field.':[* TO '.$value['to'].']');
        }
        if (isset($value['from']) && !empty($value['from'])) {
            $field = isset($this->range) ? $this->range.'_to_s' : $field;
            $query->addFilterQuery($field.':['.$value['from'].' TO *]');
        }
    }
}

?>