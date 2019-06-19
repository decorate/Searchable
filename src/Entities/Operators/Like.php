<?php

namespace Decorate\Entities\Operators;

use Decorate\Entities\Interfaces\ISearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Decorate\Entities\CommandAnalyze;

class Like implements ISearch {

    const TYPE_FORWARD_MATCH = 'TYPE_FORWARD_MATCH';
    const TYPE_BACKWARD_MATCH = 'TYPE_BACKWARD_MATCH';
    const TYPE_PARTIAL_MATCH = 'TYPE_PARTIAL_MATCH';

    private $type = self::TYPE_PARTIAL_MATCH;


    /**
     * @var CommandAnalyze
     */
    private $analyze;

    public function __construct(CommandAnalyze $analyze)
    {
        $this->analyze = $analyze;
        $this->allocateType($analyze->operator);
    }

    public function getQuery(Builder $builder, Request $request)
    {

        if($request->query($this->analyze->queryKey, null)) {
            $val = $request->query($this->analyze->queryKey, null);
            switch ($this->type) {
                case self::TYPE_FORWARD_MATCH:
                    $value = "{$val}%";
                    break;
                case self::TYPE_BACKWARD_MATCH:
                    $value = "%{$val}";
                    break;
                default:
                    $value = "%{$val}%";
                    break;
            }

            $this->analyze->operator = 'LIKE';
            (new Where($this->analyze, $value))->getQuery($builder, $request);
        }

        return $builder;
    }

    private function allocateType($value) {
        switch (true) {
            case str_contains($value, 'forward'):
                $this->type = self::TYPE_FORWARD_MATCH;
                break;
            case str_contains($value, 'backward'):
                $this->type = self::TYPE_BACKWARD_MATCH;
                break;
            default:
                $this->type = self::TYPE_PARTIAL_MATCH;
                break;
        }
    }
}