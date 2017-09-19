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
- [findByField(string $sField, mixed $mValue, array $aColumns)](#findbyfield)
- [findWhere(array $aWhere, array $aColumns)](#findwhere)
- [findWhereIn(string $sField, array $aWhere, array $aColumns)](#findwherein)
- [findWhereNotIn(string $sField, array $aWhere, array $aColumns)](#findwherenotin)
- [first(array $aColumns)](#first)
- [paginate(int $iLimit, array $aColumns, string $sPageName, int $iPage)](#paginate)
- [create(array $aAttributes)](#create)
- [update(int $id, array $aAttributes)](#update)
- [updateOrCreate(array $aAttributes)](#updateorcreate)
- [delete(array|int $id)](#delete)

### Additional methods

- [getTable](#gettable)
- [getPrimaryKey](#getprimarykey)
- [getFillFromView](#getfillfromview)
- [orderBy](#orderby)
- [limit](#limit)
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
    'ref' => 'PROD-01'
];

$aValues = [
    'name'      => 'Matrix 2',
    'category'   => 'DVD'
];

$oProduct = $oRepository->updateOrCreate($aAttributes, $aValues)
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

##### PHP :
```php
public function indexAjax(Request $oRequest)
{
    $oRepository = new ProductRepository();

    return $this->oRepository->datatable($oRequest->all());
}
```
##### HTML :
```html
<table id="tab-admin" class="table no-margin table-bordered table-hover">
	<thead>
		<tr>
			<th>ID</th>
			<th>Name</th>
			<th>Price</th>
			<th>Category</th>
			<th>Tag</th>
			<th>Tag Category</th>
			<th></th>
			<th></th>
		</tr>
	</thead>
</table>
```
##### Javascript :
```javascript
$(document).ready(function() {
    $('#tab-admin').DataTable({
        serverSide: true,
        ajax: {
            url: '../ajax-url'
        },
        columns: [
            { data: "id" },
            { data: "name" },
            { data: "price" },
            { 
                data: "category_name",
                name: "category.name"
            },
            {
                data: "tag_name",
                name: "tag.name",
                //If you have many tag and want to replace ' / '
                render: function ( data, type, row, meta ) {
                    return data.replace(" / ", "</br>"); ;
                }
            },
            {
                data: "category_tag_name",
                name: "tag.category_tag.name"
            },
            //Add a button to edit
            { 
                data: "id",  
                render: function ( data, type, row, meta ) {
                    
                    var render = "{!! Button::warning('Edit')->asLinkTo(route('admin.admin.edit', 'dummyId'))->extraSmall()->block()->render() !!}";
                    render = render.replace("dummyId", data); 
                    
                    return render;
                }
            },
            //Add a button to delete
            { 
                data: "id",  
                render: function ( data, type, row, meta ) {
                    
                    var render = '{!! BootForm::open()->action( route("admin.admin.destroy", "dummyId") )->attribute("onsubmit", "return confirm(\'Are you sure to delete ?\')")->delete() !!}'
                        +'{!! BootForm::submit("Delete", "btn-danger")->addClass("btn-block btn-xs") !!}'
                        +'{!! BootForm::close() !!}';
                    render = render.replace("dummyId", data); 
                    
                    return render;
                } 
            }
        ],
        //Don't sort edit and delete column
        aoColumnDefs: [
            {
                bSortable: false,
                aTargets: [ -1, -2 ]
            }
        ]
    });
} );
```

#### orderBy

Order by a given field and direction. By defalut, the direction is 'asc'.

```php
$oRepository = new ProductRepository();

$oProducts = $oRepository->orderBy('name')->all();
//or
$oProducts = $oRepository->orderBy('name')->findWhere(['categorie_id', 1]);
//or
$oProduct = $oRepository->orderBy('id', 'desc')->find(1, ['name']); //Useless
```

#### limit

Limit the query.

```php
$oRepository = new ProductRepository();

$oProducts = $oRepository->limit(0, 10)->all(); //Will take the first 10 records
//or
$oProducts = $oRepository->limit(5, 5)->all(); //Will take the 5 records after the 5th record.
//or
$oProduct = $oRepository->limit(0, 10)->find(1, ['name']); //Useless
```

## Date

You can specify the default date format from the database and the default date format to store in the database.

```php
namespace App\Repositories;

use CeddyG\QueryBuilderRepository\QueryBuilderRepository;

class ProductRepository extends QueryBuilderRepository
{

    protected $aFillable = ['name', 'category', 'price', 'date_limit'];
    
    protected $aDates = ['date_limit'];
    
    //By default $sDateFormatToGet = 'Y-m-d'
    protected $sDateFormatToGet = 'd/m/Y';

    //By default $sDateFormatToStore = 'Y-m-d'
    protected $sDateFormatToStore = 'Y-m-d';
}
```

Then if you have 2017-05-24 in your database you will have :

```php
$oRepository = new ProductRepository();

$oProduct = $oRepository->first(['date_limit']);
echo $oProduct->date_limit; // 24/05/2017

$oRepository->update(1, ['date_limit' => '25/05/2017']) // Will store 2017-05-25 in the database
```

## Custom attribute

You can get specific attribute.

```php
namespace App\Repositories;

use CeddyG\QueryBuilderRepository\QueryBuilderRepository;

class ProductRepository extends QueryBuilderRepository
{

    protected $aFillable = ['name', 'category', 'price', 'date_limit'];
    
    /**
     * Will change a fill that came from the database
     *
     * @param Collection|StdClass $oItem
     */
    public function getPriceAttribute($oItem)
    {
        return oItem->price * 1.2;
    }
    
    /**
     * Will create a new attribute that not in database
     *
     * @param Collection|StdClass $oItem
     */
    public function getReferenceAttribute($oItem)
    {
        return oItem->name.' '.oItem->category;
    }
}
```

And you can use it simply.

```php
$oRepository = new ProductRepository();

$oProduct = $oRepository->first(['name', 'category', 'price', 'reference']);
```

You can specify what column you need for your custom attribute in the class.

```php
namespace App\Repositories;

use CeddyG\QueryBuilderRepository\QueryBuilderRepository;

class ProductRepository extends QueryBuilderRepository
{
    protected $aFillable = ['name', 'category', 'price', 'date_limit'];
    
    /**
     * List of the customs attributes.
     * 
     * @var array
     */
    protected $aCustomAttribute = [
        'reference' => [
            'name',
            'category'
        ],
        'tag_name' => [
            'tag.name'
        ]
    ];
    
    /**
     * Will create a new attribute that not in database
     *
     * @param Collection|StdClass $oItem
     */
    public function getReferenceAttribute($oItem)
    {
        return oItem->name.' '.oItem->category;
    }
    
    /**
     * Will create a new attribute that not in database
     *
     * @param Collection|StdClass $oItem
     */
    public function getTagNameAttribute($oItem)
    {
        return oItem->tag[0]->name;
    }

    public function tag()
    {
        $sForeignKey = 'fk_product';
        $sOtherForeignKey = 'fk_tag';

        //If $sForeignKey is null, the method will set 'product_id' (<table name>.'_id')
        //If $sOtherForeignKey is null, the method will set tag_id (<table name of TagRepository>.'_id')
        $this->belongsToMany('App\Repositories\TagRepository', 'product_tag', $sForeignKey, $sOtherForeignKey);
    }
}
```

And then.

```php
$oRepository = new ProductRepository();

$oProduct = $oRepository->first(['price', 'reference', 'tag_name']);
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

//It will take the name attribut and add the relation tag to an attribut "tag"
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

To use it with [getFillFromView](#getfillfromview) you have to define what relations you allow :

```php
/**
* List of relations we allow in getFillFromView.
* 
* @var array 
*/
protected $aRelations = ['tag'];
```

You can specify if the relations are returned as array or collection.

```php
$oRepository = new ProductRepository();

//True : collection | false : array (good way to work with a lot of data)
$oRepository->setReturnCollection(false); //True by default

//It will take the name attribut and add the relation tag to an attribut "tag"
$oProduct = $oRepository->find(1, ['name', 'tag']);

foreach ($oProduct->tag as $oTag)
{
    //$oTag is a StdClass
    echo $oTag->name;
}
```

## Connection

You can specify a database (set in config/database.php).

```php
namespace App\Repositories;

use Ceddyg\QueryBuilderRepository\QueryBuilderRepository;

class ProductRepository extends QueryBuilderRepository
{
    protected $sConnection = 'mysql';
}
```

Or

```php
$oRepository = new ProductRepository();
$oRepository->setConnection('mysql');

$oProduct = $oRepository->find(1);
```

## ToDo List

- Add specific setter
- Select only the fillable's relation in the getFillFromView method (if we have $oItem->tag->tag_name in the view, the system have to select tag_name only)
- Add through relation
- Mix paginate and avaible methods
- Add a command to generate repository
