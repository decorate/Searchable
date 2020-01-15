<?php

namespace Decorate\Entities\Operators;

use Decorate\Entities\Interfaces\ISearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Decorate\Entities\CommandAnalyze;

class WhereIn implements ISearch {

    /**
     * @var CommandAnalyze
     */
    private $analyze;

    public function __construct(CommandAnalyze $analyze)
    {
        $this->analyze = $analyze;
    }

    public function getQuery(Builder $builder, Request $request)
    {
        $value = $request->query($this->analyze->queryKey);
        if($value !== null) {
            $values = explode(',', $value);

            if($this->analyze->relationTable) {
                $builder->whereHas($this->analyze->relationTable, function(Builder $builder) use($values){
                    $builder->whereIn($this->analyze->columnKey, $values);
                });
            } else {
                $builder->whereIn($this->analyze->columnKey, $values);
            }
        }
        return $builder;
    }
}