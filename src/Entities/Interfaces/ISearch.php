<?php

namespace Decorate\Entities\Interfaces;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

interface ISearch {

    public function getQuery(Builder $builder, Request $request);
}