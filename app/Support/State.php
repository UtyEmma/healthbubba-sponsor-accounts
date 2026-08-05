<?php

namespace App\Support;

use Exception;

class State {

    function __construct(
        public bool $state = true, 
        public mixed $message = '', 
        public array $context = [])
    { }

    function __call($method, $args){
        if(str_starts_with($method, 'with')) {
            $count = count($args);
            if($count <> 1) throw new Exception("{$method}() expects exactly 1 argument, {$count} given");
            $method = strtolower(substr($method, 4));
            return $this->set($method, $args[0]);
        }

        if(property_exists($this, $method)) return $this->{$method};

        return $this;
    }

    public static function __callStatic($method, $args){
        if(str_starts_with($method, 'with')) {
            $static = new static;
            $method = strtolower(substr($method, 4));
            return $static->set($method, $args[0]);
            
        }
    }

    function __get($name){
        if(property_exists($this, $name)) {
            return $this->{$name};
        }
    }

    function __set($key, $val) {
        $this->{$key} = $val;
        return $this;
    }

    function set($key, $val) {
        $this->{$key} = $val;
        return $this;
    }

    function withMessage(string $message){
        $this->message = $message;
        return $this;
    }

    function withContext(array $context) {
        $this->context = $context;
        return $this;
    }

    public static function dispatch(?bool $state = null){
        return new static($state);
    }

    public static function ok(){
        return static::dispatch(true);
    }

    public static function failed(){
        return static::dispatch(false);
    }

    public function isOk(){
        return $this->state;
    }


}