<?php

namespace Ceddyg\QueryBuilderRepository;

use DB;
use File;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Repository using query builder.
 * 
 * This class return a Collection of stdClass or a simple stdClass.
 * It's faster and use less memory than Eloquent if you just want data from
 * database.
 * 
 * @author Ceddyg
 * @package Ceddyg\QueryBuilderRepository
 */
abstract class QueryBuilderRepository
{
    /**
     * The table associated with the repository.
     *
     * @var string
     */
    protected $sTable = '';
    
    /**
     * The name of the primary key.
     *
     * @var string
     */
    protected $sPrimaryKey  = 'id';
    
    /**
     * Set config to order
     * 
     * @var array
     */
    protected $aOrderBy = [];
    
    /**
     * Set config to limit
     * 
     * @var array 
     */
    protected $aLimit = [];

    /**
     * List of the Belongs to relation.
     * 
     * @var array
     */
    protected $aBelongsTo = [];
    
    /**
     * List of the Belongs to many relation.
     * 
     * @var array
     */
    protected $aBelongsToMany = [];
    
    /**
     * List of the Has many relation.
     * 
     * @var array
     */
    protected $aHasMany = [];
    
    /**
     * List of the attributes we want in the query.
     * 
     * @var array
     */
    protected $aFillForQuery = [];
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $aFillable = [];
    
    /**
     * The relationships that should be eager loaded.
     *
     * @var array
     */
    protected $aEagerLoad = [];

    public function __construct()
    {        
        if ($this->sTable == '')
        {
            $this->sTable = preg_replace(
                '~_repository(?!.*_repository)~', 
                '', 
                str_replace('\\', '', Str::snake(class_basename($this)))
            );
            
            $this->sTable = Str::plural($this->sTable);
        }
    }
    
    /**
     * Getter
     */    
    public function getTable()
    {
        return $this->sTable;
    }
    
    public function getPrimaryKey()
    {
        return $this->sPrimaryKey;
    }
    
    /**
     * Get all record in the database.
     * 
     * @param array $aColumns
     * 
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function all(array $aColumns = ['*'])
    {
        $this->setColumns($aColumns);
        
        $aQuery = $this->setQuery()->get($aColumns);
        
        return $this->setResponse($aQuery);
    }
    
    /**
     * Get the first record in the database.
     * 
     * @param array $aColumns
     * 
     * @return stdClass
     */
    public function first(array $aColumns = ['*'])
    {
        $this->setColumns($aColumns);
        
        $aQuery = $this->setQuery()->take(1)->get($aColumns);
        
        return $this->setResponse($aQuery)->first();
    }
    
    /**
     * Paginate the given query into a simple paginator.
     * 
     * @param int $iLimit
     * @param array $aColumns
     * @param string $sPageName
     * 
     * @return LengthAwarePaginator
     */
    public function paginate($iLimit = 15, array $aColumns = ['*'], $sPageName = 'page', $iPage = null)
    {
        $this->setColumns($aColumns);
        
        $aQuery = $this->setQuery()
            ->paginate($iLimit, $aColumns, $sPageName, $iPage);
        
        $oQuery = $this->setResponse($aQuery);
        
        return $oQuery;
    }
    
    /**
     * Find a record with his ID.
     * 
     * @param int $id
     * @param array $aColumns
     * 
     * @return stdClass
     */
    public function find($id, array $aColumns = ['*'])
    {
        $this->setColumns($aColumns);
        
        $aQuery = $this->setQuery()
            ->where($this->sPrimaryKey, $id)
            ->get($aColumns);
        
        return $this->setResponse($aQuery)->first();
    }
    
    /**
     * Find records with a given field.
     * 
     * @param string $sField
     * @param mixed $mValue
     * @param array $aColumns
     * 
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function findByField($sField, $mValue, array $aColumns = ['*'])
    {
        $this->setColumns($aColumns);
        
        $aQuery = DB::table($this->sTable)
            ->where($sField, $mValue)
            ->get($aColumns);
        
        return $this->setResponse($aQuery);
    }
    
    /**
     * Find records with a given where clause.
     * 
     * @param array $aWhere
     * @param array $aColumns
     * 
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function findWhere(array $aWhere, array $aColumns = ['*'])
    {
        $this->setColumns($aColumns);
        
        $oQuery = DB::table($this->sTable);
        
        foreach($aWhere as $iKey => $mCondition)
        {
            if(is_array($mCondition))
            {
                $oQuery = $oQuery->where($mCondition[0], $mCondition[1], $mCondition[2]);
            }
            else
            {
                $oQuery = $oQuery->where($iKey, $mCondition);
            }
        }
        
        $aQuery = $oQuery->get($aColumns);
        
        return $this->setResponse($aQuery);
    }
    
    /**
     * Find records with a given where in clause.
     * 
     * @param string $sField
     * @param array $aWhere
     * @param array $aColumns
     * 
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function findWhereIn($sField, array $aWhere, array $aColumns = ['*'])
    {
        $this->setColumns($aColumns);
        
        $aQuery = DB::table($this->sTable)
            ->whereIn($sField, $aWhere)
            ->get($aColumns);
        
        return $this->setResponse($aQuery);
    }
    
    /**
     * Find records with a given where not in clause.
     * 
     * @param string $sField
     * @param array $aWhere
     * @param array $aColumns
     * 
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function findWhereNotIn($sField, array $aWhere, array $aColumns = ['*'])
    {
        $this->setColumns($aColumns);
        
        $aQuery = DB::table($this->sTable)
            ->whereNotIn($sField, $aWhere)
            ->get($aColumns);
        
        return $this->setResponse($aQuery);
    }
    
    /**
     * Find record in given fields.
     * 
     * @param string $sSearch
     * @param array $aFiealdToSearch
     * @param array $aColumns
     * 
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function search($sSearch, array $aFiealdToSearch, array $aColumns = ['*'])
    {
        $this->setColumns($aColumns);
        
        $oQuery = $this->setQuery();
        
        if ($sSearch != '')
        {
            foreach ($aFiealdToSearch as $sColumn)
            {
                $oQuery->orWhere($sColumn, 'like', '%'. $sSearch .'%');
            }
        }
    
        $oObjects = $oQuery->get($aColumns);
        
        return $this->setResponse($oObjects);
    }
    
    public function count()
    {
        return DB::table($this->sTable)->count();
    }
    
    /**
     * Add order by to the query.
     * 
     * @param string $sField
     * @param string $sDirection
     * 
     * @return \Ceddyg\QueryBuilderRepository\QueryBuilderRepository
     */
    public function orderBy($sField, $sDirection = 'asc')
    {
        $this->aOrderBy = [
            'field'     => $sField,
            'direction' => $sDirection
        ];
        
        return $this;
    }
    
    /**
     * Add limit to the query.
     * 
     * @param int $iOffset
     * @param int $iLength
     * 
     * @return \Ceddyg\QueryBuilderRepository\QueryBuilderRepository
     */
    public function limit($iOffset, $iLength)
    {
        $this->aLimit = [
            'offset' => (int) $iOffset,
            'length' => (int) $iLength
        ];
        
        return $this;
    }
    
    /**
     * Create a record.
     * 
     * We can store one record or multiple record. 
     * For exemple : $aAttributes = [
     *      'field1'    => $value1,
     *      'field2'    => $value2,
     * ] (will insert 1 record)
     * or
     * $aAttributes = [
     *      [
     *          'field1'    => $value1,
     *          'field2'    => $value2,
     *      ],
     *      [
     *          'field1'    => $value3,
     *          'field2'    => $value4,
     *      ]
     * ] (will insert 2 record)
     * 
     * @param array $aAttributes
     * 
     * @return bool|int if multiple return bool, if simple return the ID.
     */
    public function create(array $aAttributes)
    {
        if(is_array(array_values($aAttributes)[0]))
        {
            $aFormattedAttributes = [];
            
            foreach ($aAttributes as $aAttribute)
            {
                $aFormattedAttributes[] = $this->fillableFromArray($aAttribute);
            }
            
            return DB::table($this->sTable)->insert($aFormattedAttributes);
        }
        else
        {
            $aAttributes = $this->fillableFromArray($aAttributes);
            return DB::table($this->sTable)->insertGetId($aAttributes);
        }
    }
    
    /**
     * Update a record in the database.
     * 
     * @param int $id 
     * @param array $aAttribute
     * 
     * @return int ID of the record
     */
    public function update($id, array $aAttribute)
    {
        $aAttribute = $this->fillableFromArray($aAttribute);
        
        return DB::table($this->sTable)
            ->where($this->sPrimaryKey, $id)
            ->update($aAttribute);
    }
    
    /**
     * Insert or update a record matching the attributes, and fill it with values.
     * 
     * @param array $aAttribute
     * @param array $aValues
     * 
     * @return bool
     */
    public function updateOrCreate(array $aAttribute, array $aValues = [])
    {
        $aAttribute = $this->fillableFromArray($aAttribute);
        
        return DB::table($this->sTable)->updateOrInsert($aAttribute, $aValues);
    }
    
    /**
     * Delete one or many records from the database.
     * 
     * @param int|array $id
     * 
     * @return int Count of deleted records
     */
    public function delete($id)
    {
        if(is_array($id))
        {
            return DB::table($this->sTable)
                ->whereIn($this->sPrimaryKey, $id)
                ->delete();
        }
        else
        {
            return DB::table($this->sTable)
                ->where($this->sPrimaryKey, $id)
                ->delete();
        }
    }
    
    /**
     * Set a Belongs to relation.
     * 
     * @param type $sRepository
     * @param type $sForeignKey
     * 
     * @return void
     */
    protected function belongsTo($sRepository, $sForeignKey = null)
    {
        $sName = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
        
        if (array_search($sName, array_column($this->aBelongsTo, 'name')) === false)
        {
            $oRepository = new $sRepository();
            $sForeignKey = $sForeignKey ?: Str::snake($oRepository->getTable()).'_id';

            $this->aBelongsTo[] = [
                'name'          => $sName,
                'repository'    => $oRepository,
                'foreign_key'   => $sForeignKey
            ];
        }
    }
    
    /**
     * Set a Belongs to many relation.
     * 
     * @param string $sRepository
     * @param string $sPivotTable
     * @param string $sForeignKey
     * @param string $sOtherForeignKey
     * 
     * @return void
     */
    protected function belongsToMany(
        $sRepository, 
        $sPivotTable, 
        $sForeignKey        = null, 
        $sOtherForeignKey   = null
    )
    {
        $sName = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
        
        if (array_search($sName, array_column($this->aBelongsToMany, 'name')) === false)
        {
            $oRepository        = new $sRepository();
            $sForeignKey        = $sForeignKey ?: Str::snake($this->sTable).'_id';
            $sOtherForeignKey   = $sOtherForeignKey ?: Str::snake($oRepository->getTable()).'_id';

            $this->aBelongsToMany[] = [
                'name'              => $sName,
                'repository'        => $oRepository,
                'table_pivot'       => $sPivotTable,
                'foreign_key'       => $sForeignKey,
                'other_foreign_key' => $sOtherForeignKey
            ];
        }
    }
    
    /**
     * Set a Has many relation.
     * 
     * @param string $sRepository
     * @param string $sForeignKey
     * 
     * @return void
     */
    protected function hasMany($sRepository, $sForeignKey = null)
    {
        $sName = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
        
        if (array_search($sName, array_column($this->aHasMany, 'name')) === false)
        {
            $oRepository = new $sRepository();
            $sForeignKey = $sForeignKey ?: Str::snake($this->sTable).'_id';

            $this->aHasMany[] = [
                'name'          => $sName,
                'repository'    => $oRepository,
                'foreign_key'   => $sForeignKey
            ];
        }
    }
    
    /**
     * Set columns to be use in the query.
     * 
     * Note : Relations are considered as colmuns, but not used in the query.
     * 
     * @param array $aColumns
     * 
     * @return void
     */
    private function setColumns(array &$aColumns)
    {
        if (!empty($this->aFillForQuery))
        {
            if (in_array('*', $aColumns))
            {
                $aColumns = $this->aFillForQuery;
            }
            else
            {
                $aColumns = array_merge($aColumns, $this->aFillForQuery);
            }
        }
        
        foreach ($aColumns as $iKey => $column)
        {
            if (strpos($column, '.') !== false)
            {
                $aColumn    = explode('.', $column, 2);
                $column     = $aColumn[0];            
            }
            
            if (method_exists($this, $column))
            {
                if (isset($aColumn[1]))
                {
                    $this->aEagerLoad[$column][] = $aColumn[1];
                }
                
                $this->$column();
                
                unset($aColumns[$iKey]);
            }
        }
        
        if (empty($aColumns))
        {
            $aColumns[] = '*';
        }
        
        if (!in_array('*', $aColumns))
        {
            $aColumns[] = $this->sPrimaryKey;
            
            foreach ($this->aBelongsTo as $aBelongsTo)
            {
                $aColumns[] = $aBelongsTo['foreign_key'];
            }
        }
        
        $this->aFillForQuery = [];
    }
    
    private function setQuery()
    {
        $oQuery = DB::table($this->sTable);
        
        if (!empty($this->aOrderBy))
        {
            $oQuery->orderBy($this->aOrderBy['field'], $this->aOrderBy['direction']);
        }
        
        if (!empty($this->aLimit))
        {
            $oQuery->offset($this->aLimit['offset'])
                ->limit($this->aLimit['length']);
        }
        
        return $oQuery;
    }
    
    /**
     * Formatte the query.
     * 
     * @param array $aQuery
     * 
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    private function setResponse($mQuery)
    {
        if (!$mQuery instanceof Collection)
        {
            $mQuery = collect($mQuery);
        }

        $this->setRelations($mQuery);
        
        return $mQuery;
    }
    
    /**
     * Set relations to the records of the given query.
     * 
     * @param \Illuminate\Database\Eloquent\Collection|static[] $oQuery
     * 
     * @return void
     */
    private function setRelations(&$oQuery)
    {
        foreach($this->aBelongsTo as $relation)
        {
            $this->belongsToQuery($relation, $oQuery);
        }
        
        foreach($this->aHasMany as $relation)
        {
            $this->hasManyQuery($relation, $oQuery);
        }
        
        foreach($this->aBelongsToMany as $relation)
        {
            $this->belongsToManyQuery($relation, $oQuery);
        }
        
        $this->aBelongsTo       = [];
        $this->aHasMany         = [];
        $this->aBelongsToMany   = [];
        $this->aEagerLoad       = [];
    }
    
    /**
     * Query the Belongs to relation.
     * 
     * @param array $aRelation
     * @param \Illuminate\Database\Eloquent\Collection|static[] $oQuery
     * 
     * @return void
     */
    private function belongsToQuery($aRelation, &$oQuery)
    {
        $sName          = $aRelation['name'];
        $sForeignKey    = $aRelation['foreign_key'];
        $oRepository    = $aRelation['repository'];
        
        $sPrimaryKey = $oRepository->getPrimaryKey();
        $aIdrelation = $oQuery->pluck($sForeignKey)->unique()->all();
        
        $aEagerLoad = (isset($this->aEagerLoad[$sName])) ? $this->aEagerLoad[$sName] : ['*'];
        
        $oQueryRelation = $oRepository->findWhereIn($sPrimaryKey, $aIdrelation, $aEagerLoad);
                
        $oQuery->transform(
            function ($oItem, $i) use ($sName, $sPrimaryKey, $sForeignKey, $oQueryRelation)
            {
                $oItem->$sName = $oQueryRelation
                    ->where($sPrimaryKey, $oItem->$sForeignKey)
                    ->first();
                
                return $oItem;
            }
        );
    }
    
    /**
     * Query the Belongs to many relation.
     * 
     * @param array $aRelation
     * @param \Illuminate\Database\Eloquent\Collection|static[] $oQuery
     * 
     * @return void
     */
    private function belongsToManyQuery($aRelation, &$oQuery)
    {
        $sName              = $aRelation['name'];
        $oRepository        = $aRelation['repository'];
        $sTablePivot        = $aRelation['table_pivot'];
        $sPrimaryKey        = $this->sPrimaryKey;
        $sForeignKey        = $aRelation['foreign_key'];
        $sOtherForeignKey   = $aRelation['other_foreign_key'];
        
        $aIdrelation = $oQuery->pluck($sPrimaryKey)->unique()->all();
        
        $oTablePivot = collect(
            DB::table($sTablePivot)
            ->whereIn($sForeignKey, $aIdrelation)
            ->get()
        );
        
        $aIdrelation = $oTablePivot->pluck($sOtherForeignKey)->unique()->all();
        
        $aEagerLoad = (isset($this->aEagerLoad[$sName])) ? $this->aEagerLoad[$sName] : ['*'];
        
        $oQueryRelation = $oRepository
            ->findWhereIn($oRepository->getPrimaryKey(), $aIdrelation, $aEagerLoad);
         
        $oQuery->transform(
            function ($oItem, $i) 
            use (
                $sName, 
                $oRepository, 
                $oTablePivot, 
                $sForeignKey, 
                $sPrimaryKey, 
                $sOtherForeignKey, 
                $oQueryRelation
            )
            {
                $aIds = $oTablePivot
                    ->where($sForeignKey, $oItem->$sPrimaryKey)
                    ->pluck($sOtherForeignKey)
                    ->unique()
                    ->all();
                    
                $oItem->$sName = $oQueryRelation
                    ->whereIn($oRepository->getPrimaryKey(), $aIds)
                    ->values();
                
                return $oItem;
            }
        );
    }
    
    /**
     * Query the Has many relation.
     * 
     * @param array $aRelation
     * @param \Illuminate\Database\Eloquent\Collection|static[] $oQuery
     * 
     * @return void
     */
    private function hasManyQuery(array $aRelation, &$oQuery)
    {
        $sName          = $aRelation['name'];
        $oRepository    = $aRelation['repository'];
        $sForeignKey    = $aRelation['foreign_key'];
        
        $sPrimaryKey = $this->sPrimaryKey;
        
        $aIdrelation = $oQuery->pluck($sPrimaryKey)->unique()->all();
        
        $aEagerLoad = (isset($this->aEagerLoad[$sName])) ? $this->aEagerLoad[$sName] : ['*'];
        
        $oQueryRelation = $oRepository
            ->findWhereIn($sForeignKey, $aIdrelation, $aEagerLoad);
        
        $oQuery->transform(
            function ($oItem, $i) 
            use (
                $sName,  
                $sPrimaryKey,
                $sForeignKey, 
                $oQueryRelation
            )
            {                    
                $oItem->$sName = $oQueryRelation
                    ->whereIn($sForeignKey, [$oItem->$sPrimaryKey])
                    ->values();
                
                return $oItem;
            }
        );
    }

    /**
     * Get the fillable attributes of a given array.
     *
     * @param  array  $aAttributes
     * 
     * @return array
     */
    private function fillableFromArray(array $aAttributes)
    {
        if (count($this->aFillable) > 0) 
        {
            return array_intersect_key($aAttributes, array_flip($this->aFillable));
        }

        return $aAttributes;
    }
    
    /**
     * Get fill from a given view.
     * 
     * @param type $sView
     * 
     * @return \App\Repositories\QueryBuilderRepository
     */
    public function getFillFromView($sView)
    {
        $sContents = File::get(base_path().'/resources/views/'. $sView .'.blade.php');
        
        $this->aFillForQuery = [];
        foreach ($this->aFillable as $fillable)
        {
            if (stripos($sContents, $fillable) !== false)
            {
                $this->aFillForQuery[] = $fillable;
            }
        }
        
        return $this;
    }
    
    /**
     * Build a Json to be use with the Jquery Datatable server side.
     * 
     * @param array $aData
     * 
     * @return JsonResponse
     */
    public function datatable(array $aData)
    {
        $aColumns = array_unique(
            array_column($aData['columns'], 'data')
        );
        
        $aOrder = $aData['order'][0];
        $sOrder = $aColumns[$aOrder['column']];
        
        $oQuery = $this->orderBy($sOrder, $aOrder['dir'])
            ->limit($aData['start'], $aData['length'])
            ->search($aData['search']['value'], $aColumns, $aColumns);
        
        $iTotal = $this->count();
        
        $aOutput = array_merge([
            'recordsTotal'      => $iTotal,
            'recordsFiltered'   => $iTotal,
            'data'              => $oQuery
        ], $aData);
        
        return new JsonResponse($aOutput);
    }
}