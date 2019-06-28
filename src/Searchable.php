<?php

namespace Decorate;

use Decorate\Entities\From;
use Decorate\Entities\Interfaces\ISearch;
use Decorate\Entities\KeyValue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait Searchable {

    protected $guarded = ['searches'];

    public function scopeSearch(Builder $builder, Request $request) {

        $this->injectSearch($builder, $request);

        collect($this->_convert($this->getSearches()))
            ->each(function (ISearch $search) use(&$builder, $request){
                $search->getQuery($builder, $request);
            });
        return $builder;
    }

    protected function injectSearch(Builder $builder, Request $request) {
    }

    public function getSearches() : array{
        return [];
    }

    /**
     * @param array $arr
     * @return array[ISearch]
     */
    private function _convert(array $arr): array {
        $res = collect($arr)
            ->map(function ($x, $i){
                return new KeyValue($i, $x);
            })
            ->map(function (KeyValue $x) {
                return (new From($x))->getInstance();
            })->toArray();
        return $res;
    }
}
