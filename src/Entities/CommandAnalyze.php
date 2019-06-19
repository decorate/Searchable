<?php

namespace Decorate\Entities;

use Illuminate\Database\Eloquent\Builder;

class CommandAnalyze {

    /**
     * @var KeyValue
     */
    public $keyValue;
    public $queryKey;
    public $columnKey;
    public $operator;
    public $relationTable = null;
    public $commandParam = null;
    public $macro;

    private $analyzeCount = 0;


    function __construct(KeyValue $keyValue)
    {
        $this->keyValue = $keyValue;

        $this->macro = function($val) {
            return $val;
        };

        $this->analyze();
    }

    private function analyze(KeyValue $keyValue = null) {
        $this->analyzeCount++;

        if(!$keyValue) {
            $this->queryKey = $this->keyValue->key;
            $keyValue = $this->keyValue;
            $v = $keyValue->value;
        } else {
            $v = $keyValue->key;
        }

        if(is_array($keyValue->value)) {
            $this->arrayAnalyze();
            return;
        }

        if($this->hasClosure($keyValue->value)) {
            $this->setOperator($keyValue->value);
            return;
        }

        $this->columnAnalyze($v);

        if($this->columnKey) {
            $this->relationAnalyze($this->columnKey);
        }
    }

    private function arrayAnalyze() {
        list($key, $value) = array_divide($this->keyValue->value);
        $keyValue = new KeyValue($key[0], $value[0]);

        if(is_int($keyValue->key) || $this->hasClosure($keyValue->value)) {
            $this->setOperator($keyValue->value);

            if(!$this->hasClosure($keyValue->value)) {
                $this->analyze(new KeyValue($keyValue->value, ''));
            } else {
                $this->relationAnalyze($keyValue->key);
            }
        } else {
            $this->analyze($keyValue);
        }
    }

    private function columnAnalyze($value) {
        $ex = explode(':', $value);
        if(isset($ex[1])) {
            $this->setOperator($ex[0]);
            $this->setColumnKey($ex[1]);
            $this->commandAnalyze($value);
        } else {
            if($this->analyzeCount === 1) {
                $this->setOperator($ex[0]);
            } else {
                $value = array_first($this->keyValue->value);
                $this->setOperator($value);
            }
            $this->setColumnKey($ex[0]);
        }
    }

    private function commandAnalyze($value) {
        $ex = explode('|', $value);
        if(isset($ex[1])) {
            $this->setCommandParam($ex[1]);
            $this->columnAnalyze($ex[0]);
        }
    }

    private function relationAnalyze($value) {
        $ex = explode('.', $value);
        if(isset($ex[1])) {
            $this->relationTable = $ex[0];
            $this->columnKey = $ex[1];

            if(!$this->columnKey) {
                $this->columnKey = $this->queryKey;
            }
        }
    }

    private function hasClosure($value) {
        return $value instanceof \Closure;
    }

    private function setOperator($value) {
        $this->operator = $value;
    }

    private function setColumnKey($value) {
        if(preg_match('/(like|max|min)|[!-\-:-\@]/', $value)) {
            $this->columnKey = $this->keyValue->key;
            return;
        }
        $this->columnKey = $value;
    }

    private function setCommandParam($value) {
        $this->commandParam = $value;
    }

    public function toArray(...$keys) {
        $c = collect($this)
            ->map(function($x) {
                return $x;
            })->toArray();

        if($keys) {
            return collect($keys)
                ->flatMap(function ($x) use($c){
                    return [$x => $c[$x]];
                })->toArray();
        }
        return $c;

    }

    function sample() {
        return [
            'name' => '>=',
            'name2' => ['>='],
            'name3' => ['name' => '='],
            'name4' => 'like:name',
            'name5' => 'like_forward:name',
            'name6' => 'like_backward:name',
            'name7' => function(Builder $q, $value) {
                $q->whereNotNull($value);
            },
            'name8' => ['name' => function(Builder $q, $value) {
                $q->where('id', $value);
            }],
            'title' => 'like:detail.title',
            'title2' => ['detail.title' => '='],
            'title3' => ['detail.title' => function(Builder $q, $value) {
                $q->where('age', '>=', $value);
            }],
            'title4' => 'like:detail.',
            'startDate' => 'between:begin_time',
            'endDate' => 'between_end:begin_time'
        ];
    }
}

