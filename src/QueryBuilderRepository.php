<?php

namespace Ceddyg\QueryBuilderRepository;

use DB;
use File;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
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
     * Set config to order.
     * 
     * @var array
     */
    protected $aOrderBy = [];
    
    /**
     * Set config to limit.
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
     * List of relations we allow in getFillFromView.
     * 
     * @var array 
     */
    protected $aRelations = [];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $aFillable = [];
    
    /**
     * List of date field.
     * 
     * @var array 
     */
    protected $aDates = [];

    /**
     * Default format for a date field.
     *
     * @var string
     */
    protected $sDateFormatToGet = 'Y-m-d';

    /**
     * Default format for a date field.
     *
     * @var string
     */
    protected $sDateFormatToStore = 'Y-m-d';
    
    /**
     * Contain custom attribute we want in the response.
     * 
     * @var array
     */
    protected $aCustomAttributeRequest = [];

    /**
     * The relationships that should be eager loaded.
     *
     * @var array
     */
    protected $aEagerLoad = [];
    
    /**
     * Total record found.
     * 
     * @var int
     */
    protected $iTotalFiltered = 0;
    
    /**
     * Array of the Ids return by the query.
     * 
     * @var array
     */
    protected $aIdList = [];
    
    /**
     * List of the customs attributes.
     * 
     * @var array
     */
    protected $aCustomAttribute = [];

    /**
     * If we want simple array for the relations or collection.
     * 
     * @var bool
     */
    protected $bReturnCollection = true;
    
    /**
     * Set the database connection, which in config/database.php
     * 
     * @var string
     */
    protected $sConnection = '';
    
    /**
     * Indicates if the query should be timestamped.
     *
     * @var bool
     */
    protected $bTimestamp = false;

    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_AT = 'created_at';

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = 'updated_at';
    

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
     * Getter.
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
     * Setter
     */
    public function setReturnCollection(bool $bReturnCollection)
    {
        $this->bReturnCollection = $bReturnCollection;
    }
    
    public function setConnection($sConnection)
    {
        $this->sConnection = $sConnection;
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
        
        $aQuery = $this->setQuery()
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
        
        $oQuery = $this->setQuery();
        
        $this->addWhereClause($aWhere, $oQuery, $aColumns);
        
        $aQuery = $oQuery->get($aColumns);
        
        return $this->setResponse($aQuery);
    }
    
    /**
     * Find records with a given where in clause.
     * 
     * @param string $sField
     * @param array $aWhere
     * @param array $aColumns
     * @param array $aAdditionnalWhere
     * 
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function findWhereIn($sField, array $aWhere, array $aColumns = ['*'], array $aAdditionnalWhere = [])
    {
        $this->setColumns($aColumns);
        
        $oQuery = $this->setQuery()
            ->whereIn($sField, $aWhere);
        
        $this->addWhereClause($aAdditionnalWhere, $oQuery, $aColumns);
        
        $aQuery = $oQuery->get($aColumns);
        
        return $this->setResponse($aQuery);
    }
    
    /**
     * Find records with a given where not in clause.
     * 
     * @param string $sField
     * @param array $aWhere
     * @param array $aColumns
     * @param array $aAdditionnalWhere
     * 
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function findWhereNotIn($sField, array $aWhere, array $aColumns = ['*'], array $aAdditionnalWhere = [])
    {
        $this->setColumns($aColumns);
        
        $oQuery = $this->setQuery()
            ->whereNotIn($sField, $aWhere);
        
        $this->addWhereClause($aAdditionnalWhere, $oQuery, $aColumns);
        
        $aQuery = $oQuery->get($aColumns);
        
        return $this->setResponse($aQuery);
    }
    
    /**
     * Add where clause to the query.
     * 
     * @param array $aWhere
     * @param Object $oQuery
     * 
     * @return void
     */
    private function addWhereClause($aWhere, &$oQuery, $aColumns) 
    {
        $bWhereInRelation = false;
        foreach($aWhere as $mKey => $mCondition)
        {
            if(!is_array($mCondition))
            {
                $aWhere[$mKey] = [
                    $mKey, '=', $mCondition
                ];
            }
            
            if (strpos($mCondition[0], '.') !== false)
            {
                $bWhereInRelation = true;
            }
        }
        
        if ($bWhereInRelation)
        {
            $oQueryWhere = $this->setQuery();
            foreach($aWhere as $mKey => $mCondition)
            {
                if (strpos($mCondition[0], '.') !== false)
                {
                    $aRelation = explode('.', $mCondition[0], 2);
                    $sRelation = $aRelation[0];     

                    if (method_exists($this, $sRelation))
                    {
                        $sRelation = isset($aRelation[1]) ? $sRelation.'.'.$aRelation[1] : $sRelation;
                        $this->setJoin($oQueryWhere, $sRelation);

                        $aRelation = explode('.', $sRelation);
                        $mCondition[0] = 
                            $aRelation[count($aRelation)-2]
                            .'.'.
                            $aRelation[count($aRelation)-1];
                    }
                }
                else
                {
                    $mCondition[0] = $this->sTable.'.'.$mCondition[0];
                }

                $oQueryWhere->where($mCondition[0], $mCondition[1], $mCondition[2]);
            }
            
            if ($aColumns != [$this->sTable.'.'.$this->sPrimaryKey])
            {
                $mId = $oQueryWhere->groupBy($this->sTable.'.'.$this->sPrimaryKey);
        
                if (!empty($this->aOrderBy))
                {
                    $mId = $mId->orderBy($this->aOrderBy['field'], $this->aOrderBy['direction']);
                }

                $mId = $mId->get([$this->sTable.'.'.$this->sPrimaryKey]);

                if (!$mId instanceof Collection)
                {
                    $mId = collect($mId);
                }

                $aId = $mId->pluck($this->sPrimaryKey)
                    ->unique()
                    ->all();

                $oQuery->whereIn($this->sPrimaryKey, $aId);
            }
            else
            {
                $oQuery = $oQueryWhere;
            }
        }
        else
        {
            foreach($aWhere as $mKey => $mCondition)
            {
                $oQuery->where($mCondition[0], $mCondition[1], $mCondition[2]);
            }
        }
    }
    
    /**
     * Find record in given fields.
     * 
     * @param string $sSearch
     * @param array $aFiealdToSearch
     * @param array $aColumns
     * @param array $aWhere
     * 
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function search($sSearch, array $aFiealdToSearch, array $aColumns = ['*'], array $aWhere = [])
    {
        $oQuery = $this->setQuery();
        
        if ($sSearch != '')
        {
            if ($this->sConnection != '')
            {
                $oQueryToCount = DB::connection($this->sConnection)->table($this->sTable);
            }
            else
            {
                $oQueryToCount = DB::table($this->sTable);            
            }
        }
        
        foreach ($aFiealdToSearch as $sColumn)
        {
            if (strpos($sColumn, '.') !== false)
            {
                $aColumn = explode('.', $sColumn, 2);
                $sColumn = $aColumn[0];            
            }

            if (method_exists($this, $sColumn))
            {
                $sRelation = isset($aColumn[1]) ? $sColumn.'.'.$aColumn[1] : $sColumn;
                $this->setJoin($oQuery, $sRelation);
                
                if ($sSearch != '')
                {
                    $this->setJoin($oQueryToCount, $sRelation);
                }

                $aRelation = explode('.', $sRelation);
                $sColumn = 
                    $aRelation[count($aRelation)-2]
                    .'.'.
                    $aRelation[count($aRelation)-1];

                if (isset($aColumn))
                {
                    unset($aColumn);
                }
            }
            else
            {
                $sColumn = $this->sTable.'.'.$sColumn;
            }
                
            if ($sSearch != '')
            {
                $oQuery->orWhere($sColumn, 'like', '%'. $sSearch .'%');
                $oQueryToCount->orWhere($sColumn, 'like', '%'. $sSearch .'%');
            }
        }
		
		if (!empty($aWhere))
		{
			$this->addWhereClause($aWhere, $oQuery, $aColumns);
		}
        
        if ($sSearch != '')
        {
            if ($this->sConnection != '')
            {
                $sPrefix = DB::connection($this->sConnection)->getTablePrefix();
                $this->iTotalFiltered = $oQueryToCount
                ->count(
                    DB::connection($this->sConnection)
                    ->raw('DISTINCT '.$sPrefix.$this->sTable.'.'.$this->sPrimaryKey)
                ); 
            }
            else
            {
                $this->iTotalFiltered = $oQueryToCount
                ->count(DB::raw('DISTINCT '.DB::getTablePrefix().$this->sTable.'.'.$this->sPrimaryKey));
            }            
        }
        
        $this->aLimit = [];
        
        $mId = $oQuery->groupBy($this->sTable.'.'.$this->sPrimaryKey);
        
        if (!empty($this->aOrderBy))
        {
            $mId = $mId->orderBy($this->aOrderBy['field'], $this->aOrderBy['direction']);
        }
        
        $mId = $mId->get([$this->sTable.'.'.$this->sPrimaryKey]);

        if (!$mId instanceof Collection)
        {
            $mId = collect($mId);
        }

        $aId = $mId->pluck($this->sPrimaryKey)
            ->unique()
            ->all();
            
        return $this->findWhereIn($this->sPrimaryKey, $aId, $aColumns);
        
    }
    
    /**
     * Return the total record in a database.
     * 
     * @return int
     */
    public function count()
    {
        return $this->setQuery()->count();
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
     * Check if a query already has a join relation.
     * 
     * @param \Illuminate\Database\Query\Builder $oQuery
     * @param type $sTable
     * 
     * @return boolean
     */
    public function hasJoin($oQuery, $sTable)
    {
        if ($oQuery->joins !== null)
        {
            foreach($oQuery->joins as $oJoinClause)
            {
                if($oJoinClause->table == $sTable)
                {
                    return true;
                }
            }
        }
        
        return false;
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
                $aFill = $this->fillableFromArray($aAttribute);
                
                $this->setCreateTimestamp($aFill);
                
                $aFormattedAttributes[] = $aFill;
            }
            
            return $this->setQuery()->insert($aFormattedAttributes);
        }
        else
        {
            $aFill    = $this->fillableFromArray($aAttributes);
            
            $this->setCreateTimestamp($aFill);
            
            $id             = $this->setQuery()->insertGetId($aFill);
            
            $this->syncRelations($id, $aFill);
            
            return $id;
        }
    }
    
    /**
     * Add the current date to the creation if timestamp is set.
     * 
     * @param type $aAttributes
     * 
     * @return void
     */
    private function setCreateTimestamp(&$aAttributes)
    {
        if ($this->bTimestamp)
        {
            $aAttributes[static::CREATED_AT] = Carbon::now();
            $aAttributes[static::UPDATED_AT] = Carbon::now();
        }
    }
    
    /**
     * Update a record in the database.
     * 
     * @param int $id 
     * @param array $aAttributes
     * 
     * @return int ID of the record
     */
    public function update($id, array $aAttributes)
    {        
        $aFill = $this->fillableFromArray($aAttributes);
        
        $this->setUpdateTimestamp($aFill);
        
        $mId = $this->setQuery()
            ->where($this->sPrimaryKey, $id)
            ->update($aFill);
        
        $this->syncRelations($id, $aAttributes);
        
        return $mId;
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
        $oItem = $this->findWhere($aAttribute, [$this->sPrimaryKey])->first();
        
        if ($oItem !== null)
        {
            $sPrimaryKey = $this->sPrimaryKey;
            return $this->update($oItem->$sPrimaryKey, $aValues);
        }
        else
        {
            return $this->create($aValues);
        }
    }
    
    /**
     * Add the current date to the update if timestamp is set.
     * 
     * @param type $aAttributes
     * 
     * @return void
     */
    private function setUpdateTimestamp(&$aAttributes)
    {
        if ($this->bTimestamp)
        {
            $aAttributes[static::UPDATED_AT] = Carbon::now();
        }
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
            return $this->setQuery()
                ->whereIn($this->sPrimaryKey, $id)
                ->delete();
        }
        else
        {
            return $this->setQuery()
                ->where($this->sPrimaryKey, $id)
                ->delete();
        }
    }
    
    private function syncRelations($id, $aAttributes)
    {
        foreach ($aAttributes as $sKey => $mValue)
        {
            if (method_exists($this, $sKey))
            {
                $this->sync($id, $sKey, $aAttributes[$sKey]);
            }
        }
    }
    
    private function sync($id, $sRelation, $aIdToSync)
    {
        if (method_exists($this, $sRelation))
        {
            $this->flushRelation();
            $this->$sRelation();

            $aRelation = $this->aBelongsToMany[0];
            
            if (is_array($aRelation) && !empty($aIdToSync))
            {
                DB::table($aRelation['table_pivot'])
                    ->whereNotIn($aRelation['other_foreign_key'], $aIdToSync)
                    ->where($aRelation['foreign_key'], $id)
                    ->delete();

                $aIdAlready = collect(DB::table($aRelation['table_pivot'])
                    ->where($aRelation['foreign_key'], $id)
                    ->get([$aRelation['other_foreign_key']]));

                $aDiff = array_diff(
                    $aIdToSync,
                    $aIdAlready->pluck($aRelation['other_foreign_key'])->toArray()
                );

                $aFinal = [];

                foreach ($aDiff as $sId)
                {
                    $aFinal[] = [
                        $aRelation['foreign_key']       => (int) $id,
                        $aRelation['other_foreign_key'] => (int) $sId
                    ];
                }

                DB::table($aRelation['table_pivot'])->insert($aFinal);
            }
            else
            {
                DB::table($aRelation['table_pivot'])
                    ->where($aRelation['foreign_key'], $id)
                    ->delete();
            }

            $this->flushRelation();
        }
    }
    
    /**
     * Set a Belongs to relation.
     * 
     * @param type $sRepository
     * @param type $sForeignKey
     * @param array $aWhere
     * 
     * @return void
     */
    protected function belongsTo($sRepository, $sForeignKey = null, array $aWhere = [])
    {
        $sName = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
        
        if (array_search($sName, array_column($this->aBelongsTo, 'name')) === false)
        {
            $oRepository = new $sRepository();
            $sForeignKey = $sForeignKey ?: Str::snake($oRepository->getTable()).'_id';

            $this->aBelongsTo[] = [
                'name'          => $sName,
                'repository'    => $oRepository,
                'foreign_key'   => $sForeignKey,
                'where'         => $aWhere
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
     * @param array $aWhere
     * 
     * @return void
     */
    protected function belongsToMany(
        $sRepository, 
        $sPivotTable, 
        $sForeignKey        = null, 
        $sOtherForeignKey   = null,
        array $aWhere = []
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
                'other_foreign_key' => $sOtherForeignKey,
                'where'             => $aWhere
            ];
        }
    }
    
    /**
     * Set a Has many relation.
     * 
     * @param string $sRepository
     * @param string $sForeignKey
     * @param array $aWhere
     * 
     * @return void
     */
    protected function hasMany($sRepository, $sForeignKey = null, array $aWhere = [])
    {
        $sName = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
        
        if (array_search($sName, array_column($this->aHasMany, 'name')) === false)
        {
            $oRepository = new $sRepository();
            $sForeignKey = $sForeignKey ?: Str::snake($this->sTable).'_id';

            $this->aHasMany[] = [
                'name'          => $sName,
                'repository'    => $oRepository,
                'foreign_key'   => $sForeignKey,
                'where'         => $aWhere
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
        
        if (in_array('*', $aColumns))
        {
            foreach ($this->aFillable as $sFillable)
            {
                if (method_exists($this, $this->getCustomAttributeFunction($sFillable)))
                {
                    $this->aCustomAttributeRequest[] = $sFillable;
                    
                    if (array_key_exists($sFillable, $this->aCustomAttribute))
                    {
                        foreach ($this->aCustomAttribute[$sFillable] as $sNeededColumn)
                        {
                            $aColumns[] = $sNeededColumn;
                        }
                    }
                }
            }
        }
        
        foreach ($aColumns as $iKey => $sColumn)
        {
            if (method_exists($this, $this->getCustomAttributeFunction($sColumn)))
            {
                $this->aCustomAttributeRequest[] = $sColumn;
                
                if (!in_array($sColumn, $this->aFillable))
                {
                    unset($aColumns[$iKey]);
                }
                
                if (array_key_exists($sColumn, $this->aCustomAttribute))
                {
                    foreach ($this->aCustomAttribute[$sColumn] as $sNeededColumn)
                    {
                        $aColumns[] = $sNeededColumn;
                    }
                }
            }
        }
        
        foreach ($aColumns as $iKey => $sColumn)
        {
            if (strpos($sColumn, '.') !== false)
            {
                $aColumn = explode('.', $sColumn, 2);
                $sColumn = $aColumn[0];            
            }
                      
            if (method_exists($this, $sColumn))
            {
                if (isset($aColumn[1]))
                {
                    $this->aEagerLoad[$sColumn][] = $aColumn[1];
                    unset($aColumn);
                }
                
                $this->$sColumn();
                
                unset($aColumns[$iKey]);
            }
        }
        
        if (empty($aColumns))
        {
            $aColumns[] = '*';
        }
        
        if (!in_array('*', $aColumns))
        {
            if (!in_array($this->sPrimaryKey, $aColumns))
            {
                $aColumns[] = $this->sPrimaryKey;
            }
            
            foreach ($this->aBelongsTo as $aBelongsTo)
            {
                $aColumns[] = $aBelongsTo['foreign_key'];
            }
            
            $aColumns       = array_values($aColumns);
            $iTotalColumns  = count($aColumns);
            
            for ($i = 0 ; $i < $iTotalColumns ; $i++)
            {
                $aColumns[$i] = $this->sTable.'.'.$aColumns[$i];
            }
        }
        
        $this->aFillForQuery = [];
    }
    
    /**
     * Create the query.
     * 
     * @return object
     */
    private function setQuery()
    {
        if ($this->sConnection != '')
        {
            $oQuery = DB::connection($this->sConnection)->table($this->sTable);
        }
        else
        {
            $oQuery = DB::table($this->sTable);            
        }
        
        if (!empty($this->aOrderBy) && strpos($this->aOrderBy['field'], '.') === false)
        {
            $oQuery->orderBy($this->sTable.'.'.$this->aOrderBy['field'], $this->aOrderBy['direction']);
        }
        
        if (!empty($this->aLimit))
        {
            $oQuery->offset($this->aLimit['offset'])
                ->limit($this->aLimit['length']);
        }
        
        return $oQuery;
    }
    
    /**
     * Set the join to the query.
     * 
     * @param \Illuminate\Database\Query\Builder $oQuery
     * @param string $sRelation
     * 
     * @return void
     */
    public function setJoin(&$oQuery, $sRelation, $sAlias = null)
    {
        if ($sAlias == null)
        {
            $sAlias = $this->sTable;
        }
        
        $this->flushRelation();
        
        if (strpos($sRelation, '.') !== false)
        {
            $aRelation  = explode('.', $sRelation, 2);
            $sRelation  = $aRelation[0];            
        }

        if (method_exists($this, $sRelation))
        {
            $this->$sRelation();
            
            $this->setLeftJoinOnBelongsTo($sAlias, $oQuery, $oRepository);
            $this->setLeftJoinOnBelongsToMany($sAlias, $oQuery, $oRepository);
            $this->setLeftJoinOnHasMany($sAlias, $oQuery, $oRepository);
            
            if (isset($aRelation[1]) && $oRepository !== null)
            {
                $oRepository->setJoin($oQuery, $aRelation[1], $sRelation);
            }
        }
        
        $this->flushRelation();
    }
    
    /**
     * Add left join query to a given query, if a "belongs to" relation is set.
     * 
     * @param \Illuminate\Database\Query\Builder $oQuery
     * 
     * @return object|bool
     */
    private function setLeftJoinOnBelongsTo($sAlias, &$oQuery, &$oRepository = null)
    {
        if (!empty($this->aBelongsTo))
        {
            $sName          = $this->aBelongsTo[0]['name'];
            $sForeignKey    = $this->aBelongsTo[0]['foreign_key'];
            $oRepository    = $this->aBelongsTo[0]['repository'];
 
            if (!$this->hasJoin($oQuery, $oRepository->getTable().' as '.$sName))
            {
                $oQuery->leftJoin(
                    $oRepository->getTable().' as '.$sName, 
                    $sName.'.'.$oRepository->getPrimaryKey(), 
                    '=', 
                    $sAlias.'.'.$sForeignKey
                );
            }
        }
    }
    
    /**
     * Add left join query to a given query, if a "belongs to many" relation is set.
     * 
     * @param \Illuminate\Database\Query\Builder $oQuery
     * 
     * @return object|bool
     */
    private function setLeftJoinOnBelongsToMany($sAlias, &$oQuery, &$oRepository = null)
    {
        if (!empty($this->aBelongsToMany))
        {
            $sName              = $this->aBelongsToMany[0]['name'];
            $oRepository        = $this->aBelongsToMany[0]['repository'];
            $sTablePivot        = $this->aBelongsToMany[0]['table_pivot'];
            $sForeignKey        = $this->aBelongsToMany[0]['foreign_key'];
            $sOtherForeignKey   = $this->aBelongsToMany[0]['other_foreign_key'];

            if (!$this->hasJoin($oQuery, $oRepository->getTable().' as '.$sName))
            {
                $oQuery->leftJoin(
                    $sTablePivot,
                    $sTablePivot.'.'.$sForeignKey, 
                    '=', 
                    $sAlias.'.'.$this->sPrimaryKey
                );

                $oQuery->leftJoin(
                    $oRepository->getTable().' as '.$sName, 
                    $sName.'.'.$oRepository->getPrimaryKey(), 
                    '=', 
                    $sTablePivot.'.'.$sOtherForeignKey
                );
            }
        }
    }
    
    /**
     * Add left join query to a given query, if a "belongs to" relation is set.
     * 
     * @param \Illuminate\Database\Query\Builder $oQuery
     * 
     * @return object|bool
     */
    private function setLeftJoinOnHasMany($sAlias, &$oQuery, &$oRepository = null)
    {
        if (!empty($this->aHasMany))
        {
            $sName          = $this->aHasMany[0]['name'];
            $sForeignKey    = $this->aHasMany[0]['foreign_key'];
            $oRepository    = $this->aHasMany[0]['repository'];

            if (!$this->hasJoin($oQuery, $oRepository->getTable().' as '.$sName))
            {
                $oQuery->leftJoin(
                    $oRepository->getTable().' as '.$sName, 
                    $sName.'.'.$sForeignKey, 
                    '=', 
                    $sAlias.'.'.$this->sPrimaryKey
                );
            }
        }
    }
    
    /**
     * Formatte the query.
     * 
     * @param array $mQuery
     * 
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    private function setResponse($mQuery)
    {
        if (!$mQuery instanceof Collection)
        {
            $mQuery = collect($mQuery);
        }
        
        $this->aIdList = $mQuery->pluck($this->sPrimaryKey)->unique()->all();
        
        $this->setRelations($mQuery);
        $this->getCustomAttribute($mQuery);
        
        $this->aIdList = [];
        $this->aCustomAttributeRequest = [];
        
        return $mQuery;
    }
    
    /**
     * Format all the date field and custom attribute.
     * 
     * @param \Illuminate\Database\Eloquent\Collection $oQuery
     * 
     * @return void
     */
    private function getCustomAttribute(&$oQuery)
    {
        $oQuery->transform(
            function ($oItem, $i)
            {
                foreach ($oItem as $sAttribute => $mValue)
                {
                    if (in_array($sAttribute, $this->aDates))
                    {
                        $oItem->$sAttribute = Carbon::parse($mValue)
                            ->format($this->sDateFormatToGet);
                    }
                }
                
                foreach ($this->aCustomAttributeRequest as $sCustomAttribute)
                {
                    $sFunction = $this->getCustomAttributeFunction($sCustomAttribute);
                    $oItem->$sCustomAttribute = $this->$sFunction($oItem);
                }
            
                return $oItem;
            }
        );
    }
    
    private function getCustomAttributeFunction($sAttribute)
    {
        return 'get'.ucfirst(camel_case($sAttribute)).'Attribute';
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
        
        $this->flushRelation();
        $this->aEagerLoad = [];
    }
    
    /**
     * Empty the relations array.
     * 
     * @return void
     */
    private function flushRelation()
    {
        $this->aBelongsTo       = [];
        $this->aHasMany         = [];
        $this->aBelongsToMany   = [];
    }
    
    /**
     * Sort a collection if an order by on a relation is set.
     * 
     * @param \Illuminate\Database\Eloquent\Collection|static[] $oQuery
     * @param array $aColumn
     * 
     * @return void
     */
    private function sortCollection(&$oQuery, $aColumn)
    { 
        if ($this->aOrderBy['direction'] == 'asc')
        {
            $oQuery = $oQuery->sortBy($aColumn['data'])->values();
        }
        else
        {
            $oQuery = $oQuery->sortByDesc($aColumn['data'])->values();
        }
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
        $aWhere         = $aRelation['where'];
        
        $sPrimaryKey    = $oRepository->getPrimaryKey();
        $aIdrelation    = $oQuery->pluck($sForeignKey)->unique()->all();
        
        $aEagerLoad = (isset($this->aEagerLoad[$sName])) ? $this->aEagerLoad[$sName] : ['*'];
        
        $oRepository->setReturnCollection($this->bReturnCollection);
        $this->changeConnectionRepository($oRepository);
        $oQueryRelation = $oRepository->findWhereIn($sPrimaryKey, $aIdrelation, $aEagerLoad, $aWhere);
                
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
        $sOtherPrimaryKey   = $oRepository->getPrimaryKey();
        $aWhere             = $aRelation['where'];
        
        if ($this->sConnection != '')
        {
            $oQueryPivot = DB::connection($this->sConnection)->table($sTablePivot);
        }
        else
        {
            $oQueryPivot = DB::table($sTablePivot);            
        }
        
        $oTablePivots = collect(
            $oQueryPivot
            ->whereIn($sForeignKey, $this->aIdList)
            ->get()
        );
        
        $aIdrelation = $oTablePivots->pluck($sOtherForeignKey)->unique()->all();
        
        $aEagerLoad = (isset($this->aEagerLoad[$sName])) ? $this->aEagerLoad[$sName] : ['*'];
        
        $oRepository->setReturnCollection($this->bReturnCollection);
        $this->changeConnectionRepository($oRepository);
        $aQueryRelation = $oRepository
            ->findWhereIn($sOtherPrimaryKey, $aIdrelation, $aEagerLoad, $aWhere)
            ->all();
        
        $aFinalRelation = [];
        foreach ($oTablePivots as $oTablePivot)
        {
            if (!isset($aFinalRelation[$oTablePivot->$sForeignKey]))
            {
                $aFinalRelation[$oTablePivot->$sForeignKey] = [];
            }
            
            $aFinalRelation[$oTablePivot->$sForeignKey] += array_filter(
                $aQueryRelation, 
                function ($oSubItem) use ($oTablePivot, $sOtherPrimaryKey, $sOtherForeignKey)
                {
                    return $oSubItem->$sOtherPrimaryKey == $oTablePivot->$sOtherForeignKey;
                }
            );
        }
        unset($oTablePivots);
        
        $oQuery->transform(
            function ($oItem, $i) 
            use (
                $sName,  
                $sPrimaryKey,
                $aFinalRelation
            )
            {          
                if (isset($aFinalRelation[$oItem->$sPrimaryKey]))
                {
                    $oItem->$sName = $this->bReturnCollection ? 
                        collect($aFinalRelation[$oItem->$sPrimaryKey])
                        : $aFinalRelation[$oItem->$sPrimaryKey];
                }
                else
                {
                    $oItem->$sName = [];
                }
                
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
        $aWhere         = $aRelation['where'];
        
        $sPrimaryKey    = $this->sPrimaryKey;
        
        $aEagerLoad     = (isset($this->aEagerLoad[$sName])) ? $this->aEagerLoad[$sName] : ['*'];
        
        if ($aEagerLoad[0] != '*' && !in_array($sForeignKey, $aEagerLoad))
        {
            $aEagerLoad[] = $sForeignKey;
        }
        
        $oRepository->setReturnCollection($this->bReturnCollection);
        $this->changeConnectionRepository($oRepository);
        $oQueryRelation = $oRepository
            ->findWhereIn($sForeignKey, $this->aIdList, $aEagerLoad, $aWhere);
        
        $aFinalRelation = [];
        foreach ($oQueryRelation as $oRelation)
        {
            $aFinalRelation[$oRelation->$sForeignKey][] = $oRelation;
        }
        unset($oQueryRelation);
        
        $oQuery->transform(
            function ($oItem, $i) 
            use (
                $sName,  
                $sPrimaryKey,
                $aFinalRelation
            )
            {          
                if (isset($aFinalRelation[$oItem->$sPrimaryKey]))
                {
                    $oItem->$sName = $this->bReturnCollection ? 
                        collect($aFinalRelation[$oItem->$sPrimaryKey])
                        : $aFinalRelation[$oItem->$sPrimaryKey];
                }
                else
                {
                    $oItem->$sName = [];
                }
                
                return $oItem;
            }
        );
    }
    
    /**
     * Change the connection of a given repository.
     * 
     * @param Ceddyg\QueryBuilderRepository $oRepository
     */
    public function changeConnectionRepository(&$oRepository)
    {
        if ($this->sConnection != '')
        {
            $oRepository->setConnection($this->sConnection);
        }
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
            $aAttributes = array_intersect_key($aAttributes, array_flip($this->aFillable));
        
            $this->formatFieldToStore($aAttributes);
        
            return $aAttributes;
        }
        
        $this->formatFieldToStore($aAttributes);
        
        return $aAttributes;
    }
    
    /**
     * If we want transform some attributes before storing.
     * 
     * @param array $aAttributes
     * 
     * return void
     */
    private function formatFieldToStore(&$aAttributes)
    {
        foreach ($aAttributes as $sAttribute => $mValue)
        {
            if (in_array($sAttribute, $this->aDates))
            {
                $sTmpDate = str_replace('/', '-', $mValue);
                
                $aAttributes[$sAttribute] = date($this->sDateFormatToStore, strtotime($sTmpDate));
            }
        }
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
        foreach ($this->aFillable as $sFillable)
        {
            if (stripos($sContents, $sFillable) !== false)
            {
                $this->aFillForQuery[] = $sFillable;
            }
        }
        
        foreach ($this->aRelations as $sRelation)
        {
            if (stripos($sContents, $sRelation) !== false)
            {
                $this->aFillForQuery[] = $sRelation;
            }
        }
        
        return $this;
    }
    
    /**
     * Build the columns list for the query.
     * 
     * @param array $aData
     * 
     * @return array
     */
    private function buildColumns($aData)
    {
        $aColumns = [];
        
        foreach ($aData['columns'] as $aColumn)
        {
            $aColumns[] = $aColumn['name'] != '' 
                ? $aColumn['name'] 
                : $aColumn['data'];
        }
        
        return array_unique($aColumns);
    }
    
    /**
     * Build the order column for the query.
     * 
     * @param array $aColumns
     * @param string $sOrder
     * 
     * @return string
     */
    private function buildOrderColumn($aColumns, $sOrder)
    {
        $sColumn = $aColumns[$sOrder];
        
        if (strpos($sColumn, '.') !== false)
        {
            $aColumn = explode('.', $sColumn);
            $iCount = count($aColumn);
            
            return $aColumn[$iCount-2].'.'.$aColumn[$iCount-1];
        }
        else
        {
            return $sColumn;
        }
    }

    /**
     * Add custom values to the returned query with its attributes.
     * 
     * @param Collection $oQuery
     * @param array $aData
     * 
     * @return void
     */
    private function addCustomValues(&$oQuery, $aData)
    {
        $oQuery->transform(function ($oItem, $iKey) use ($aData)
        {
            foreach ($aData['columns'] as $aColumn)
            {
                if ($aColumn['name'] != '')
                {
                    $sAttributeName = $aColumn['name'];
                    
                    $aAttribute = $this->buildEasterValue($oItem, $sAttributeName);
                    $sAttribute = implode(' / ', $aAttribute);
                    
                    $sNewAttributeName         = $aColumn['data'];
                    $oItem->$sNewAttributeName = $sAttribute;
                }
            }
            
            return $oItem;
        });
    }

    /**
     * Parse an object or Collection  for the response.
     * 
     * @param Collection|StdClass $oItem
     * @param string $sColumnsName
     * @param array $aValue
     * 
     * @return StdClass
     */
    private function buildEasterValue($oItem, $sColumnsName, $aValue = [])
    {
        if (strpos($sColumnsName, '.') !== false)
        {
            $aAttribute = explode('.', $sColumnsName, 2);
            $sAttribute = $aAttribute[0];
            
            if ($oItem->$sAttribute instanceof Collection || is_array($oItem->$sAttribute))
            {
                foreach ($oItem->$sAttribute as $oSubItem)
                {
                    $aValue = $this->buildEasterValue($oSubItem, $aAttribute[1], $aValue);
                }
            }
            else
            {
                $aValue = $this->buildEasterValue($oSubItem, $aAttribute[1], $aValue);
            }
        }
        else
        {
            if (!$oItem->$sColumnsName instanceof Collection)
            {
                $aValue[] = $oItem->$sColumnsName;
            }
        }
        
        return $aValue;
    }
    
    /**
     * Build a Json to be use with the Jquery Datatable server side.
     * 
     * @param array $aData
     * @param array $aWhere
     * 
     * @return JsonResponse
     */
    public function datatable(array $aData, array $aWhere = [])
    {
        $aColumns   = $this->buildColumns($aData);
        
        $aOrder     = $aData['order'][0];
        $sOrder     = $this->buildOrderColumn($aColumns, $aOrder['column']);
        
        $oQuery = $this->orderBy($sOrder, $aOrder['dir'])
            ->limit($aData['start'], $aData['length'])
            ->search($aData['search']['value'], $aColumns, $aColumns, $aWhere);
        
        $this->addCustomValues($oQuery, $aData);
        $this->sortCollection($oQuery, $aData['columns'][$aOrder['column']]);
        
        $iTotal = $this->count();
        
        $aOutput = array_merge([
            'recordsTotal'      => $iTotal,
            'recordsFiltered'   => ($this->iTotalFiltered == 0) ? $iTotal : $this->iTotalFiltered,
            'data'              => $oQuery
        ], $aData);
        
        return new JsonResponse($aOutput);
    }
    
    public function select2(array $aData)
    {
        $sPrimaryKey    = $this->getPrimaryKey();
        $sSearch        = isset($aData['q']) ? $aData['q'] : '';
        $sField         = $aData['field'];
        $iPage          = (int) isset($aData['page']) ? $aData['page'] : 1;
        
        $oItems = $this->limit(($iPage-1)*30, 30)
            ->orderBy($sField)
            ->search($sSearch, [$sField], [$sPrimaryKey, $sField]);
        
        $iCount = $sSearch != '' 
            ? $this->iTotalFiltered
            : $this->count();
        
        $oItems->transform(function ($oItem) use ($sPrimaryKey, $sField){
            $oItem->id = $oItem->$sPrimaryKey;
            $oItem->text = $oItem->$sField;
            
            return $oItem;
        });
        
        $aOutput = [
            'items'         => $oItems,
            'total_count'   => $iCount
        ];
        
        return new JsonResponse($aOutput);
    }
}
