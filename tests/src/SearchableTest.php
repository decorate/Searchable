<?php

    namespace Searchable\Test;

    use Decorate\Searchable;
    use App\Models\Alcohol;
    use App\Models\Career;
    use App\Models\Education;
    use App\Models\Level;
    use App\Models\RequestLevel;
    use App\Models\RequestWork;
    use App\Models\Smoke;
    use App\Models\User;
    use App\Models\UserDetail;
    use Illuminate\Database\Eloquent\Builder;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Artisan;

    class SearchableTest extends TestCase {

        function setUp (): void
        {
            parent::setUp();

            factory(Level::class)->create();
            factory(Education::class)->create();
            factory(Career::class)->create();
            factory(Alcohol::class)->create();
            factory(Smoke::class)->create();

            factory(User::class, 10)->create(['type' => 0]);

            factory(User::class)
                ->create([
                    'type' => 0, 'name' => 'test_man',
                    'email' => 'test-man@email.com'
                ])
                ->each(function (User $user) {
                    factory(UserDetail::class)
                        ->create([
                            'user_id' => $user->id,
                            'description' => 'OK TEST!!',
                        ]);
                })
            ;
        }

        /**
         * @group searchable
         */
        function testUserNameSearchOK() {
            $r = new Request([
                'userName' => 'test_man',
            ]);

            MockUser::$inject = [
                'userName' => 'like:name',
            ];

            $actual = MockUser::query()->search($r)->first();
            $assert = MockUser::query()->where('name', 'test_man')->first();

            $this->assertEquals($assert, $actual);
        }

        /**
         * @group searchable
         */
        function testUserNameSearchOK_KeyValue同じ() {
            $r = new Request([
                'name' => 'test_man'
            ]);

            MockUser::$inject = [
                'name' => 'like'
            ];

            $actual = MockUser::query()->search($r)->first();
            $assert = MockUser::query()->where('name', 'test_man')->first();

            $this->assertEquals($assert, $actual);
        }

        /**
         * @group searchable
         */
        function testUserNameAndEmailSearchOK() {
            $r = new Request([
                'name' => 'test_man',
                'email' => 'test-man@email.com'
            ]);

            MockUser::$inject = [
                'name' => 'like',
                'email' => 'like'
            ];

            $actual = MockUser::query()->search($r)->first();
            $assert = MockUser::query()->where('name', 'test_man')->first();

            $this->assertEquals($assert, $actual);
        }

        /**
         * @group searchable
         */
        function testUserNameAndEmailSearchNotUser() {
            $r = new Request([
                'name' => 'test_man',
                'email' => 'test-man-failed@email.com'
            ]);

            MockUser::$inject = [
                'name' => 'like',
                'email' => 'like'
            ];

            $actual = MockUser::query()->search($r)->first();

            $this->assertEquals($actual, null);
        }

        /**
         * @group searchable
         */
        function testUserNameForwardLikeOK() {
            $r = new Request([
                'name' => 'test_'
            ]);

            MockUser::$inject = [
                'name' => 'like_forward'
            ];

            $actual = MockUser::query()->search($r)->first();
            $assert = MockUser::query()->where('name', 'test_man')->first();

            $this->assertEquals($actual, $assert);
        }

        /**
         * @group searchable
         */
        function testUserNameBackwardLikeOK() {
            $r = new Request([
                'name' => '_man'
            ]);

            MockUser::$inject = [
                'name' => 'like_backward'
            ];

            $actual = MockUser::query()->search($r)->first();
            $assert = MockUser::query()->where('name', 'test_man')->first();

            $this->assertEquals($actual, $assert);
        }

        /**
         * @group searchable
         */
        function testRelationLikeSearchOK() {
            $r = new Request([
                'description' => 'OK TEST!!',
            ]);

            MockUser::$inject = [
                'description' => 'like:detail.description',
            ];

            $actual = MockUser::query()->with(['detail'])->search($r)->first();
            $assert = MockUser::query()->with(['detail'])
                ->whereHas('detail', function ($q) {
                    $q->where('description', 'OK TEST!!');
                })->first();

            $this->assertEquals($actual, $assert);

        }

        /**
         * @group searchable
         */
        function testCallBackColumnOK() {
            $r = new Request([
                'userName' => 'test_man',
            ]);

            MockUser::$inject = [
                'userName' => function(Builder $builder, $value){
                    $builder->where('name', $value);
                },
            ];

            $actual = MockUser::query()->search($r)->first();
            $assert = MockUser::query()->where('name', 'test_man')->first();

            $this->assertEquals($actual, $assert);
        }

        /**
         * @group searchable
         */
        function testWhereOperator() {
            $r = new Request([
                'start' => 1,
                'end' => 3
            ]);

            MockUser::$inject = [
                'start' => ['id' => '>='],
                'end' => ['id' => '<=']
            ];
            $actual = MockUser::query()->search($r);

            $assert = [1,2,3];

            $this->assertEquals($actual->pluck('id')->toArray(), $assert);
        }

        /**
         * @group searchable
         */
        function testWhereHasInWhereHas() {
            factory(User::class)->create(['name' => 'test-test'])
                ->each(function (User $user) {
                    factory(\App\Models\Request::class)
                        ->create(['user_id' => $user->id])
                        ->each(function (\App\Models\Request $request) {
                            factory(RequestLevel::class)->create(['request_id' => $request->id, 'level_id' => 1]);
                            factory(RequestWork::class)
                                ->create([
                                    'request_id' => $request->id,
                                ]);
                        });
                });

            $r = new Request([
                'name' => 'test-test'
            ]);

            MockRequestWork::$inject = [
                'name' => ['request.name' => function(Builder $q, $value) {
                    $q->whereHas('user', function(Builder $q) use($value){
                        $q->where('name', $value);
                    });
                }]
            ];

            $actual = MockRequestWork::query()
                ->with(['request' => function($q) {
                    $q->with('user');
                }])->search($r)->first();

            $assert = MockRequestWork::query()
                ->whereHas('request', function ($q) {
                    $q->whereHas('user', function ($q) {
                        $q->where('name', 'test-test');
                    });
                })->with(['request' => function ($q) {
                    $q->with('user');
                }])->first();

            $this->assertEquals($actual->request->user, $assert->request->user);
        }

        /**
         * @group searchable
         */
        function testOmissionRelation() {
            $r = new Request([
                'description' => 'OK TEST!!',
            ]);

            MockUser::$inject = [
                'description' => 'like:detail.',
            ];
            $actual = MockUser::query()->search($r)->first();

            $assert = MockUser::query()
                ->whereHas('detail', function(Builder $q) {
                    $q->where('description', 'like', "%OK TEST!!%");
                })->first();

            $this->assertEquals($actual, $assert);
        }

        /**
         * @group searchable
         */
        function testInjectSearch() {
            MockUser::$injectSearch = function(Builder $builder, Request $request) {
                return $builder->where('id', 2);
            };

            $actual = MockUser::query()->search(new Request())->first();

            $this->assertEquals($actual->id, 2);

            $r = new Request(['id' => 1]);
            MockUser::$inject = ['id' => '='];

            $actual = MockUser::query()->search($r)->first();

            $this->assertEquals($actual, null);
        }

        function tearDown():void
        {
            Artisan::call('migrate:refresh');
            parent::tearDown();
            MockUser::$inject = [];
            MockUser::$injectSearch = null;
        }
    }

    class MockUser extends User {
        use Searchable;

        protected $table = 'users';

        static $inject = [];

        static $injectSearch;

        protected function getSearches()
        {
            return self::$inject;
        }

        protected function injectSearch(Builder $builder, Request $request)
        {
            if(self::$injectSearch) {
                (self::$injectSearch)($builder, $request);
            }

        }

        public function detail () {
            return $this->hasOne(UserDetail::class, 'user_id');
        }

    }

    class MockRequestWork extends RequestWork {
        use Searchable;

        protected $table = 'request_works';

        static $inject = [];

        public function getSearches(): array
        {
            return self::$inject;
        }
    }
