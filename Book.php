<?php

class Book {
    public static $schema = [
        'id' => 'INTEGER UNSIGNED NOT NULL AUTO_INCREMENT',
        'name' => 'VARCHAR(255)',
        'author' => 'VARCHAR(255)',
        'year' => 'INTEGER',
        'page_numbers' => 'INTEGER',
    ];
    
    public static function getFields(){
        $fields = [];
        foreach(self::$schema as $field => $properties){
            if($field == key(self::$schema)){
                continue;
            }
            $fields[] = $field;
        }
        return $fields;
    }
    
    public function formatField($field){
        $properties = self::$schema[$field];
        
        if(stripos($properties, 'INTEGER') !== false){
            return $this->{$field};
        }
        else if(stripos($properties, 'VARCHAR') !== false){
            return "'{$this->{$field}}'";
        }
    }
}
