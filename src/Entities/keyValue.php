<?php

namespace Decorate\Entities;

class KeyValue {

    public $key;
    public $value;

    public function __construct($key, $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    public function toArray() {
        return [$this->key => $this->value];
    }

    public function keyToValue() {
        $this->key = $this->value;
    }

}