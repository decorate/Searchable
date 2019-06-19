<?php

namespace Decorate\Entities\Operators;

use Decorate\Entities\Interfaces\ISearch;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Decorate\Entities\CommandAnalyze;

class Between implements ISearch {

    const TYPE_START = 'TYPE_START';
    const TYPE_END = 'TYPE_END';

    private $type = self::TYPE_START;


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

            try {
                $c = new Carbon($val);

                if($this->type === self::TYPE_END) {
                    $val = $c->addDay()->format('Y-m-d');
                }
            } catch (\Exception $e) {}

            (new Where($this->analyze, $val))->getQuery($builder, $request);
        }

        return $builder;
    }

    private function allocateType($value) {
        switch (true) {
            case str_contains($value, 'end'):
                $this->type = self::TYPE_END;
                $this->analyze->operator = '<=';
                break;
            case str_contains($value, 'start'):
                $this->type = self::TYPE_START;
                $this->analyze->operator = '>=';
                break;
            default:
                $this->type = self::TYPE_START;
                $this->analyze->operator = '>=';
                break;
        }
    }
}