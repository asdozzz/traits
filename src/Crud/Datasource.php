<?php

namespace Asdozzz\Traits\Crud;

trait Datasource
{
	function create($data)
    {
        $result = \DB::table($this->table)
            ->insertGetId($data);

        return $result;
    }

	function read($id)
    {
        $result = $this->getByPK($id);

        return $result;
    }

    function getByPK($pk)
    {
        $record = $this->getByArray([$this->primary_key, $pk], true);
        return $record;
    }

    function getByArray($wharr,$bFirst = false)
    {
        $query = \DB::table($this->table);

        $query = $this->helpWhere($query, $wharr);

        if (empty($bFirst))
        {
            $result = $query->get();
        }
        else
        {
            $result = $query->first();
        }

        return $result;
    }

    function helpWhere($query, $wharr)
    {
        if (is_scalar($wharr[0]))
        {
            $wharr = [$wharr];
        }

        foreach ($wharr as $filter) 
        {
            if (count($filter) == 2)
            {
                $query->where($filter[0],'=',$filter[1]);
            }

            if (count($filter) == 3)
            {
                $query->where($filter[0],$filter[1],$filter[2]);
            }
        }

        return $query;
    }

    function update($pk,$data)
    {
        $result = $this->updateByArray([$this->primary_key, $pk], $data);
        return $result;
    }

    function updateByArray($wharr, $data)
    {
        if (empty($wharr)) return false;
        if (empty($data)) return false;

        $query = \DB::table($this->table);

        $query = $this->helpWhere($query, $wharr);

        $result = $query->update($data);
        return $result;
    }

    function delete($pk)
    {
        $result = $this->deleteByArray([$this->primary_key, $pk]);
        return $result;
    }

    function deleteByArray($wharr)
    {
        if (empty($wharr)) return false;
        $query = \DB::table($this->table);

        $query = $this->helpWhere($query, $wharr);

        $result = $query->delete();
        return $result;
    }

    function extendSoftDelete($query)
    {
        if (!empty($this->softDeletes))
        {
            $query->whereNull($this->deleted_field);
        }

        return $query;
    }

    function getList($schema,$input)
    {
        //\DB::enableQueryLog();
        $query = \DB::table($this->table);

        $query = $this->extendSoftDelete($query);

        $query = $this->addSelectedColumnByScheme($query,$schema);
        $query = $this->addFilterColumnByData($query,$schema,$input);
        $query = $this->setOrderByData($query,$schema,$input);
        $query = $this->setLimitByData($query,$schema,$input);
        $result = $query->get();
        //dd(\DB::getQueryLog());
        return $result;
    }

    function getCount($schema = [],$input = [])
    {
        $query = \DB::table($this->table);

        $query = $this->extendSoftDelete($query);

        if (!empty($schema))
        {
            $query = $this->addSelectedColumnByScheme($query,$schema);
            $query = $this->addFilterColumnByData($query,$schema,$input);
        }
        return $query->count();
    }

    function datatable($schema,$input)
    {

        $count = $this->getCount($schema);
        $filter_count = $this->getCount($schema,$input);
        $records = $this->getList($schema,$input);
        
        $response = [
            'recordsTotal' => $count,
            "recordsFiltered" =>  $filter_count,
            'data' => $records
        ];

        return $response;
    }

    function addSelectedColumnByScheme($query,$schema)
    {
        $query->addSelect($this->table.".".$this->primary_key.' as row_id');
        foreach ($schema['columns'] as $key => $value) 
        {
            if (empty($value['data'])) continue;

            $table = !empty($value['table'])?$value['table']:'';

            if (empty($table))
            {
                $table = $this->table;
            }
            
            $column_name = !empty($value['column_name'])?$value['column_name']:$value['data'];

            $query->addSelect($table.".".$column_name." AS ".$value['data']);
        }

        return $query;
    }

    function addFilterColumnByData($query,$schema,$input)
    {
        if (isset($input['filter']))
        {
            foreach ($input['filter'] as $filter) 
            {
                foreach ($schema['columns'] as $value) 
                {
                    if ($value['data'] == $filter['colname'])
                    {
                        $col = $value;
                        break;
                    }
                }

                if (!empty($col))
                {
                    $query = \Operator::factory($filter['operator'])->setFilter($query,$filter);
                    /*switch ($filter['operator'])
                    {
                        default:
                            $query->where($filter['colname'],$filter['operator'],$filter['value']);
                        break;
                    }*/
                }
            }
        }

        if (!empty($input['filterForAll']))
        {
            $query->where(
                function ($query) use ($schema,$input)
                {
                    $cols = [];
                    foreach ($schema['columns'] as $value)
                    {
                        if (empty($value['data'])) continue;
                        $cols[] = $value['data'];
                    }

                    foreach ($cols as $col)
                    {
                        $query->orWhere($col,'LIKE','%'.$input['filterForAll'].'%');
                    }
                }
            );
        }

        return $query;
    }

    function setOrderByData($query,$schema,$input)
    {
        if (isset($input['order']))
        {
            $order = [];
            foreach ($input['order'] as $key => $value) 
            {
                if (empty($value['direction'])) continue;
                $query->orderBy($value['column'],$value['direction']);
            }
        }

        return $query;
    }

    function setLimitByData($query,$schema,$input)
    {
        if (isset($input['start']))
        {
            $start = (int)$input['start'];
        }
        else
        {
            $start = 0;
        }

        $query->skip($start);

        if (isset($input['length']))
        {
            $length = (int)$input['length'];
        }
        else
        {
            $length = 10;
        }
        //DB::enableQueryLog();
        $query->take($length);

        return $query;
    }

    /*function getSubdataForList($schema)
    {
        $result = [];
        foreach ($schema['datatable']['columns'] as $key => $value) 
        {
            if (empty($value['data'])) continue;
            
            //Если у поля указан источник и значение источника имеет строковый тип, то это Источник-Таблица
            if (isset($value['source']) && is_string($value['source']))
            {
                //Источник-Таблица
                $sub_table = $value['source'];
                //Собираем значения для источника
                $fieldsIds = keyField($response['data'] ,$value['data']);
                if (!empty($fieldsIds))
                {
                    $fieldsIds = array_unique($fieldsIds);
                }

                if (!empty($value['source_settings']))
                {
                    $settings = $value['source_settings'];
                }
                else
                {
                    $settings = ['field' => 'id'];
                }

                $new_query = NULL;
                $new_query = DB::table($sub_table);
                $result[$sub_table] = $new_query->whereIn($settings['field'],$fieldsIds)->get();
                if (!empty($result[$sub_table]))
                {
                    $result[$sub_table] = setKeys($response['sub_data'][$sub_table], $settings['field']);
                }
            }
        }

        return $result;
    }*/
}