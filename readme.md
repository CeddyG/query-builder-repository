Query Builder Repository
===============

Laravel Repository using Query Builder (Fluent) instead of Eloquent. It returns a Collection of StdClass or a simple StdClass. It can receive arrays to create or update a record in the database and also delete a record or multiple record.

## Installation

```php
composer require ceddyg/query-builder-repository
```

## Usage

### Create a repository

Firstly you have to create a repository and define the table, primary key and fillable.

By default the table will take the snake case in the plural of the repository's name without "Repository" and primary key is "id" by default.

```php
namespace App\Repositories;

use CeddyG\QueryBuilderRepository\QueryBuilderRepository;

class ProductRepository extends QueryBuilderRepository
{
    //By default $sTable = 'products'
    protected $sTable = 'product';

    //By default $sPrimaryKey = 'id'
    protected $sPrimaryKey = 'id_products';

    //The attributes that are mass assignable.
    protected $fillable = ['name','category'];
}
```

### Avaible methods

- [all(array $aColumns)](#all)
- [find(int $id, array $aColumns)](#find)
- [findByField(string $sField, mixed $mValue, array $aColumns)](#findByField)
- [findWhere(array $aWhere, array $aColumns)](#findWhere)
- [findWhereIn(string $sField, array $aWhere, array $aColumns)](#findWhereIn)
- [findWhereNotIn(string $sField, array $aWhere, array $aColumns)](#findWhereNotIn)
- [first(array $aColumns)](#first)
- [paginate(int $iLimit, array $aColumns, string $sPageName, int $iPage)](#paginate)
- [create(array $aAttributes)](#create)
- [update(int $id, array $aAttributes)](#update)
- [updateOrCreate(array $aAttributes)](#updateOrCreate)
- [delete(array|int $id)](#delete)

#### Additional methods

- [getTable](#getTable)
- [getPrimaryKey](#getPrimaryKey)
- [getFillFromView](#getFillFromView)
- [datatable](#datatable)

#### all

Get all record in the database.

```php
$oRepository = new ProductRepository();

$oProducts = $oRepository->all(); //Collection
//or
$oProducts = $oRepository->all(['name']); //Collection

foreach ($oProducts as $oProduct)
{
    //$oProduct is a StdClass
    echo oProduct->name;
}
```

#### find

Find a record with his ID.

```php
$oRepository = new ProductRepository();

$oProduct = $oRepository->find(1); //StdClass with all columns
//or
$oProduct = $oRepository->find(1, ['name']); //StdClass with specific columns

echo oProduct->name; 
```

#### findByField

Find records with a given field.

```php
$oRepository = new ProductRepository();

$oProducts = $oRepository->findByField('name', 'Matrix'); //Collection
//or
$oProducts = $oRepository->findByField('name', 'Matrix', ['name', 'category']); //Collection

foreach ($oProducts as $oProduct)
{
    //$oProduct is a StdClass
    echo oProduct->name;
    echo oProduct->category;
}
```

#### findWhere

Find records with a given where clause.

```php
$oRepository = new ProductRepository();

$oProducts = $oRepository->findWhere(['price' => 20]); //Will find all products where the price = 20 and return a Collection
//or
$oProducts = $oRepository->findWhere(['price', '<', 20]); //Will find all products where the price < 20 and return a Collection
//or
$oProducts = $oRepository->findWhere([['price', '<', 20]], ['name']); //Will find all products where the price < 20 and return a Collection 
//or
$oProducts = $oRepository->findWhere([['price', '<', 20], ['name', 'LIKE', 'Mat%']], ['name']);

foreach ($oProducts as $oProduct)
{
    //$oProduct is a StdClass
    echo oProduct->name;
}
```

#### findWhereIn

Find records with a given where in clause.

```php
$oRepository = new ProductRepository();

$oProducts = $oRepository->findWhereIn('name', ['Matrix', 'Matrix 2'])); //Collection
//or
$oProducts = $oRepository->findWhereIn('name', ['Matrix', 'Matrix 2'], ['name', 'category'])); //Collection

foreach ($oProducts as $oProduct)
{
    //$oProduct is a StdClass
    echo oProduct->name;
    echo oProduct->category;
}
```

#### findWhereNotIn

Find records with a given where not in clause.

```php
$oRepository = new ProductRepository();

$oProducts = $oRepository->findWhereNotIn('name', ['Matrix', 'Matrix 2'])); //Collection
//or
$oProducts = $oRepository->findWhereNotIn('name', ['Matrix', 'Matrix 2'], ['name', 'category'])); //Collection

foreach ($oProducts as $oProduct)
{
    //$oProduct is a StdClass
    echo oProduct->name;
    echo oProduct->category;
}
```

#### first

Return the first record.

```php
$oRepository = new ProductRepository();

$oRepository->first(); //StdClass
//or
$oRepository->first(['name']); //StdClass
```

#### paginate

Same use than [fluent](https://laravel.com/docs/5.3/pagination).

#### create

Create a record.

```php
$oRepository = new ProductRepository();

$aAttributes = [
    'name'      => 'Matrix 2',
    'category'   => 'DVD'
];
//or
$aAttributes = [
    [
        'name'      => 'Matrix 2',
        'category'   => 'DVD'
    ],
    [
        'name'      => 'Matrix 3',
        'category'   => 'DVD'
    ]
];

$oRepository->create($aAttributes);//Return insert id if 1 create or bool if multiple
```

#### update

Update a record in the database.

```php
$oRepository = new ProductRepository();

$aAttributes = [
    'name'      => 'Matrix 1',
    'category'   => 'DVD'
];

$oRepository->update(1, $aAttributes);
```

#### updateOrCreate

Insert or update a record matching the attributes, and fill it with values.

```php
$oRepository = new ProductRepository();

$aAttributes = [
    'name'      => 'Matrix 2',
    'category'   => 'DVD'
];

$oProduct = $oRepository->updateOrCreate($aAttributes)
```

#### delete

Delete one or many records from the database.

```php
$oRepository = new ProductRepository();

$oRepository->delete(1); //Delete the record with id 1
//or
$oRepository->delete([1, 2, 3]); //Delete the record with id 1, 2 and 3
```

#### getTable

Return the table.

```php
$oRepository = new ProductRepository();

$sTable = $oRepository->getTable();
```

#### getPrimaryKey

Return the primary key.

```php
$oRepository = new ProductRepository();

$sPrimaryKey = $oRepository->getPrimaryKey();
```

#### getFillFromView

Get columns from a given view. It will check if fillable are in the view and add them in the columns list for the query.

```php
$oRepository = new ProductRepository();

$oProducts = $oRepository->getFillFromView('product/index')->all();
//or
$oProducts = $oRepository->getFillFromView('product/index')->all(['name']);//Will merge fill in the view and parameters in all()

//Other
$oProduct = $oRepository->getFillFromView('product/index')->find(1);
$oProducts = $oRepository
    ->getFillFromView('product/index')
    ->findWhere([['price', '<', 20], ['name', 'LIKE', 'Mat%']], ['name']);
```

#### datatable

It's to use with Jquery [Datatable](https://datatables.net/), when you want to use [Server Side](https://datatables.net/examples/data_sources/server_side.html). indexAjax() is the method's controller you call in Ajax.

```php
public function indexAjax(Request $oRequest)
{
    $oRepository = new ProductRepository();

    return $this->oRepository->datatable($oRequest->all());
}
```

## Relationship

To configure relationship, it's like Eloquent, you have to define a belongsTo, belongsToMany or hasMany with other repositories.

```php
belongsTo($sRepository, $sForeignKey = null)
belongsToMany($sRepository, $sPivotTable, $sForeignKey = null, $sOtherForeignKey = null)
hasMany($sRepository, $sForeignKey = null)
```

```php
namespace App\Repositories;

use Ceddyg\QueryBuilderRepository\QueryBuilderRepository;

class ProductRepository extends QueryBuilderRepository
{
    //By default $sTable = 'product'
    protected $sTable = 'products';

    //By default $sPrimaryKey = 'id'
    protected $sPrimaryKey = 'id_products';

    //The attributes that are mass assignable.
    protected $fillable = ['name','category'];

    public function tag()
    {
        $sForeignKey = 'fk_tag';

        //If $sForeignKey is null, the method will set tag_id (<table name of TagRepository>.'_id')
        $this->belongsTo('App\Repositories\TagRepository', $sForeignKey);
    }
    
    //or
    public function tag()
    {
        $sForeignKey = 'fk_product';
        $sOtherForeignKey = 'fk_tag';

        //If $sForeignKey is null, the method will set 'product_id' (<table name>.'_id')
        //If $sOtherForeignKey is null, the method will set tag_id (<table name of TagRepository>.'_id')
        $this->belongsToMany('App\Repositories\TagRepository', 'product_tag', $sForeignKey, $sOtherForeignKey);
    }
    
    //or
    public function tag()
    {
        $sForeignKey = 'fk_product';

        //If $sForeignKey is null, the method will set 'product_id' (<table name>.'_id')
        $this->hasMany('App\Repositories\TagRepository', 'product_id');
    }
}
```

Relations are considered like columns, so to add it :

```php
$oRepository = new ProductRepository();

//It will take the name attribut ad add the relation tag to an attribut "tag"
$oProduct = $oRepository->find(1, ['name', 'tag']);

echo $oProduct->name;
echo $oProduct->tag->name;

//If belongsToMany or hasMany relation, $oProduct->tag is a Collection
foreach ($oProduct->tag as $oTag)
{
    //$oTag is a StdClass
    echo $oTag->name;
}
```

## ToDo List

- Add relation in Datatable (done, but without eagerloading)
- Add relation in getFillFromView
- Add limit and order
- Add through relation
- Mix paginate and avaible methods
- Add a command to generate repository