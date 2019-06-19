<?php

namespace Searchable\Test;

use Decorate\Entities\CommandAnalyze;
use Decorate\Entities\KeyValue;
use Illuminate\Database\Eloquent\Builder;

class CommandAnalyzeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @group analyze
     */
    function testNormalOK() {
        $k = new KeyValue('name', '<=');

        $actual = (new CommandAnalyze($k))->toArray();

        $assert = [
            'queryKey' => 'name',
            'columnKey' => 'name',
            'operator' => '<=',
        ];

        $this->assert($assert, $actual);
    }

    /**
     * @group analyze
     */
    function testNormalArrayOK() {
        $k = new KeyValue('name', ['<=']);

        $actual = (new CommandAnalyze($k))->toArray();

        $assert = [
            'queryKey' => 'name',
            'columnKey' => 'name',
            'operator' => '<=',
        ];

        $this->assert($assert, $actual);
    }

    /**
     * @group analyze
     */
    function testNormalArrayKeyOperatorOK() {
        $k = new KeyValue('user_name', ['name' => '<=']);

        $actual = (new CommandAnalyze($k))->toArray();

        $assert = [
            'queryKey' => 'user_name',
            'columnKey' => 'name',
            'operator' => '<=',
        ];

        $this->assert($assert, $actual);
    }

    /**
     * @group analyze
     */
    function testNormalLikeOK() {
        $k = new KeyValue('user_name', 'like:name');

        $actual = (new CommandAnalyze($k))->toArray();

        $assert = [
            'queryKey' => 'user_name',
            'columnKey' => 'name',
            'operator' => 'like',
        ];

        $this->assert($assert, $actual);

        $k = new KeyValue('user_name', 'like_forward:name');

        $actual = (new CommandAnalyze($k))->toArray();

        $assert = [
            'queryKey' => 'user_name',
            'columnKey' => 'name',
            'operator' => 'like_forward',
        ];

        $this->assert($assert, $actual);
    }

    /**
     * @group analyze
     */
    function testOperatorClosureOK() {
        $c = function (Builder $q) {
            $q->where('id', 1);
        };
        $k = new KeyValue('user_name', $c);

        $actual = (new CommandAnalyze($k))->toArray();

        $assert = [
            'queryKey' => 'user_name',
            'columnKey' => null,
            'operator' => $c,
        ];

        $this->assert($assert, $actual);
    }

    /**
     * @group analyze
     */
    function testOperatorArrayClosureOK() {
        $c = function (Builder $q) {
            $q->where('id', 1);
        };
        $k = new KeyValue('user_name', ['name' => $c]);

        $actual = (new CommandAnalyze($k))->toArray();

        $assert = [
            'queryKey' => 'user_name',
            'columnKey' => null,
            'operator' => $c,
        ];

        $this->assert($assert, $actual);
    }

    /**
     * @group analyze
     */
    function testNormalRelationLikeOK() {
        $k = new KeyValue('title', 'like:detail.title');

        $actual = (new CommandAnalyze($k))->toArray();

        $assert = [
            'queryKey' => 'title',
            'columnKey' => 'title',
            'operator' => 'like',
            'relationTable' => 'detail'
        ];

        $this->assert($assert, $actual);
    }

    /**
     * @group analyze
     */
    function testRelationArrayOperatorOK() {
        $k = new KeyValue('title', ['detail.title' => '=']);

        $actual = (new CommandAnalyze($k))->toArray();

        $assert = [
            'queryKey' => 'title',
            'columnKey' => 'title',
            'operator' => '=',
            'relationTable' => 'detail'
        ];

        $this->assert($assert, $actual);
    }

    /**
     * @group analyze
     */
    function testRelationArrayClosureOK() {
        $c = function(Builder $q) {
            $q->where('age', '>=', 15);
        };
        $k = new KeyValue('title', ['detail.title' => $c]);

        $actual = (new CommandAnalyze($k))->toArray();

        $assert = [
            'queryKey' => 'title',
            'columnKey' => 'title',
            'operator' => $c,
            'relationTable' => 'detail'
        ];

        $this->assert($assert, $actual);
    }

    /**
     * @group analyze
     */
    function testCommandValueOK() {
        $k = new KeyValue('ageParam', 'max:age|100');

        $actual = (new CommandAnalyze($k))->toArray();

        $assert = [
            'queryKey' => 'ageParam',
            'columnKey' => 'age',
            'operator' => 'max',
            'commandParam' => '100'
        ];

        $this->assert($assert, $actual);

        $k = new KeyValue('ageParam', 'min:age|10');
        $assert['operator'] = 'min';
        $assert['commandParam'] = '10';

        $actual = (new CommandAnalyze($k))->toArray();

        $this->assert($assert, $actual);
    }

    /**
     * @group analyze
     */
    function testClosureRelation() {
        $k = new KeyValue('title', ['detail.' => function(Builder $q, $value) {
            $q->whereHas('col', function(Builder $q, $value) {
                $q->where('col', $value);
            });
        }]);

        $actual = (new CommandAnalyze($k))->toArray();

        $assert = [
            'queryKey' => 'title',
            'columnKey' => 'title',
            'relationTable' => 'detail'
        ];

        $this->assert($assert, $actual);
    }

    /**
     * @group analyze
     */
    function testOmissionRelationOK() {
        $k = new KeyValue('title', 'like:detail.');

        $actual = (new CommandAnalyze($k))->toArray();

        $assert = [
            'queryKey' => 'title',
            'columnKey' => 'title',
            'operator' => 'like',
            'relationTable' => 'detail'
        ];

        $this->assert($assert, $actual);
    }

    function assert($assert, $actual) {
        collect($assert)
            ->each(function ($x, $i) use($actual){
                $this->assertEquals($x, $actual[$i]);
            });
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
