<?php

namespace Decorate\Entities\Operators;

use Decorate\Entities\Interfaces\ISearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Decorate\Entities\CommandAnalyze;

class Where implements ISearch {

    /**
     * @var CommandAnalyze
     */
    private $analyze;
    private $value = null;
    private $boolean = 'and';

    /**
     * Where constructor.
     * @param CommandAnalyze $analyze
     * @param null $value
     * @param string $boolean
     */
    public function __construct(CommandAnalyze $analyze, $value = null, $boolean = 'and')
    {
        $this->analyze = $analyze;
        $this->value = $value;
        $this->boolean = $boolean;
    }

    public function getQuery(Builder $builder, Request $request)
    {
        $this->value = $this->value ?? $request->query($this->analyze->queryKey);

        if($this->value === null || $this->value === '') {
            return false;
        }

        if($this->analyze->relationTable) {
            $this->setRelation();
        }

        if($this->analyze->relationTable && $this->analyze->operator instanceof \Closure) {
            $value = $request->query($this->analyze->queryKey);
            return $builder->whereHas($this->analyze->relationTable, function ($q) use($value){
                ($this->analyze->operator)($q, $value);
            });
        }

        if($this->analyze->operator instanceof \Closure) {
            $value = $request->query($this->analyze->queryKey);
            return $builder->where(function(Builder $builder) use($value){
                ($this->analyze->operator)($builder, $value);
            });
        }

        return $builder->where(
            $this->analyze->columnKey,
            $this->analyze->operator,
            $this->value, $this->boolean);
    }

    private function setRelation() {
        $operator = $this->analyze->operator;
        if($operator instanceof \Closure) {
            return;
        }
        $this->analyze->operator = function (Builder $q) use($operator){
            $analyze = $this->analyze;
            $q->where($analyze->columnKey, $operator, $this->value);
        };
    }

}