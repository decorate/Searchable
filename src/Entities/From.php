<?php

namespace Decorate\Entities;

use Decorate\Entities\Operators\Like;
use Decorate\Entities\Operators\Between;
use Decorate\Entities\Operators\Where;
use Decorate\Entities\Operators\WhereIn;

class From {

    private $instance;

    public function __construct(KeyValue $keyValue)
    {
        $command = new CommandAnalyze($keyValue);

        if($this->hasLike($command)) {
            $this->instance = new Like($command);
            return;
        }

        if($this->hasBetween($command)) {
            $this->instance = new Between($command);
            return;
        }

        if($this->hasIn($command)) {
            $this->instance = new WhereIn($command);
            return;
        }

        $this->instance = new Where($command);
    }

    public function getInstance() {
        return $this->instance;
    }

    private function hasLike(CommandAnalyze $analyze) {
        if(!is_string($analyze->operator)) {
            return false;
        }

        return str_contains($analyze->operator, 'like');
    }

    private function hasBetween(CommandAnalyze $analyze) {
        if(!is_string($analyze->operator)) {
            return false;
        }

        return str_contains($analyze->operator, 'between');
    }

    private function hasIn(CommandAnalyze $analyze) {
        if(!is_string($analyze->operator)) {
            return false;
        }

        return str_contains($analyze->operator, 'in');
    }

}