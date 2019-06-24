
## Searchable
This is a Laravel search support package like a validator

### Installation

With composer:

    composer require decorate/searchable
    
### Command Examples
```php
[  
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
    'endDate' => 'between_end:begin_time',
    'level' => 'in:level_id',
    'level2' => in:detail.level_id  
];
```

### Usage
```php
class User extends Model {
  use Searchable;
  
  protected function getSearches(){
    return [
      'name' => 'like',
      'detail_name' => 'like:detail.name' 
    ];
  }
}
```
```php
class UsersController extends Controller {
  
  public function index(Request $request) {
    return User::search($request);
  }
  
}
```

### injection search
```php
protected function injectSearch(Builder $builder, Request $request){
  $builder->where('id', '=', $request->query('id'));
}
```