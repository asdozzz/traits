<?php

namespace Asdozzz\Traits\Crud;

/**
 * Class Model
 *
 * @package Asdozzz\Traits\Crud
 */
trait Model
{
    /**
     * @param $key
     * @return bool
     */
    function hasPermission($key)
    {
        $User = \Auth::user();

        if (empty($User))
        {
            return false;
        }

        if (!empty($this->essence->permissions) && !empty($this->essence->permissions[$key]))
        {
            return $User->hasPermission($this->essence->permissions[$key]);
        }

        return true;
    }

    /**
     * @param array $input
     * @return mixed
     * @throws \Exception
     */
    public function create($input = array())
	{
        if (empty($input) || empty($input['data']))
        {
            throw new \Exception(\Lang::get('vika.exceptions.model.data_not_found'));
        }

		$form = $this->essence->forms['create'];

        if (isset($form['columns'][$this->essence->primary_key]))
        {
            unset($form['columns'][$this->essence->primary_key]);
        }

        $input['data'] = $this->beforeCreate($input['data']);

        $new_data = $this->setDefaultValue($form['columns'],$input['data']);
       
        $rules = $this->getValidationRulesByConfig($form['columns'],$new_data);

        $errors = $this->validationByRules($new_data,$rules);

        if (!empty($errors))
        {
            throw new \Exception($errors[0]);
        }

        $new_data = $this->convertationData($form['columns'],$new_data); 
        $new_data = $this->clearDataBeforeSave($form['columns'],$new_data);
        
        $id = $this->datasource->create($new_data);
        $read = $this->datasource->read($id);

        return $read;
	}

    /**
     * @param array $input
     * @return array
     */
    public function beforeCreate($input = array())
    {
        return $input;
    }

    /**
     * @param $input
     * @return mixed
     * @throws \Exception
     */
    public function update($input)
	{
        if (empty($input) || empty($input['data']))
        {
            throw new \Exception(\Lang::get('vika.exceptions.model.data_not_found'));
        }

        if (empty($input['data'][$this->essence->primary_key]))
        {
            throw new \Exception(\Lang::get('vika.exceptions.model.pk_not_found'));
        }

		$form = $this->essence->forms['edit'];

        $input['data'] = $this->beforeUpdate($input['data']);

        $new_data = $this->setDefaultValue($form['columns'],$input['data']);
       
        $rules = $this->getValidationRulesByConfig($form['columns'],$new_data);

        $errors = $this->validationByRules($new_data,$rules);

        if (!empty($errors))
        {
            throw new \Exception($errors[0]);
        }

        $new_data = $this->convertationData($form['columns'],$new_data);
        $new_data = $this->clearDataBeforeSave($form['columns'],$new_data);
       
        $result = $this->datasource->update($input['data'][$this->essence->primary_key],$new_data);
        $read = $this->datasource->read($input['data'][$this->essence->primary_key]);

        return $read;
	}

    /**
     * @param array $input
     * @return array
     */
    public function beforeUpdate($input = array())
    {
        return $input;
    }

    /**
     * @param $id
     * @return mixed
     * @throws \Exception
     */
    public function read($id)
	{
        if (empty($id))
        {
            throw new \Exception(\Lang::get('vika.exceptions.model.pk_not_found'));
        }

		$result = $this->datasource->read($id);
		return $result;
	}

    /**
     * @param $id
     * @return mixed
     * @throws \Exception
     */
    public function delete($id)
	{
		if (!empty($this->datasource->softDeletes))
        {
            if (empty($this->datasource->deleted_field))
            {
                throw new \Exception(\Lang::get('vika.exceptions.crud.delete.not_exists_deleted_field'));
            }

            if (!array_key_exists($this->datasource->deleted_field, $this->essence->columns))
            {
                throw new \Exception(\Lang::get('vika.exceptions.crud.delete.not_found_deleted_field'));
            }

            $value = $this->getDefaultValueForColumn($this->essence->columns[$this->datasource->deleted_field]);

            $new_data = [$this->datasource->deleted_field => $value];
            $result = $this->updateBy([[$this->essence->primary_key,$id]],$new_data);
        }
        else
        {
            $result = $this->deleteBy([[$this->essence->primary_key,$id]]);
        }
		
		return $result;
	}

    /**
     * @param $wharr
     * @param $new_data
     * @return mixed
     */
    public function updateBy($wharr, $new_data)
    {
        return $this->datasource->updateByArray($wharr,$new_data);
    }

    /**
     * @param $wharr
     * @return mixed
     */
    public function deleteBy($wharr)
    {
        return $this->datasource->deleteByArray($wharr);
    }

    /**
     * @param $columns
     * @param $data
     * @return mixed
     */
    function setDefaultValue($columns, $data)
    {   
        foreach ($columns as $key => $col) 
        {
            if (!empty($data[$col['data']])) continue;
            if (!array_key_exists('default_value',$col)) continue;
            
            $value = $this->getDefaultValueForColumn($col, $data);
            $data[$col['data']] = $value;
        }

        return $data;
    }

    /**
     * @param       $col
     * @param array $data
     * @return bool|float|int|mixed|string
     * @throws \Exception
     */
    function getDefaultValueForColumn($col, $data = [])
    {
        

        if (is_scalar($col['default_value'])) return $col['default_value'];

        if (is_array($col['default_value']))
        {
            switch ($col['default_value']['type']) 
            {
                case 'scalar':
                    return $col['default_value']['value'];
                break;

                case 'function':
                   return $col['default_value']['value']($data);
                break;
                
                default:
                    $exs = \Lang::get('vika.exceptions.default_value.not_found_handler_type');
                    $exs = sprintf($exs, $col['default_value']['type']);
                    throw new \Exception($exs);
                break;
            }
        }
    }

    /**
     * @param $columns
     * @param $data
     * @return array
     */
    function getValidationRulesByConfig($columns, $data)
    {
        $rules = array();

        foreach ($columns as $col_name => $col) 
        {
            $rules[$col['data']] = '';
            if (!empty($col['validation_rules']))
            {
                $rules[$col['data']] = $col['validation_rules'];
                $replace_pk = !empty($data) && !empty($data[$this->essence->primary_key])?','.$data[$this->essence->primary_key]:'';
                $rules[$col['data']] = preg_replace('/{pk}/',$replace_pk, $rules[$col['data']]);
            }
        }

        return $rules;
    }

    /**
     * @param $data
     * @param $rules
     * @return array
     */
    function validationByRules($data, $rules)
    {
        $errors = [];
        $validator = \Validator::make($data, $rules);
        
        if ($validator->fails())
        {
            $errors = $errors = $validator->errors()->all();
        }

        return $errors;
    }

    /**
     * @param $columns
     * @param $data
     * @return mixed
     */
    function convertationData($columns, $data)
    {
        foreach ($columns as $key => $col) 
        {
            //Конвертирование данных
            if (!empty($col['convertation_rules']))
            {
                $value = $this->getConvertValueForColumn($col, $data[$col['data']]);
                $data[$col['data']] = $value;
            }
        }

        return $data;
    }

    /**
     * @param $col
     * @param $value
     * @return mixed
     */
    function getConvertValueForColumn($col, $value)
    {
        if (empty($col['convertation_rules'])) return $value;

        foreach ($col['convertation_rules'] as $rule) 
        {
           $value = $rule($value);
        }

        return $value;
    }

    /**
     * @param $columns
     * @param $data
     * @return array
     */
    function clearDataBeforeSave($columns, $data)
    {
        $new_data = [];

        foreach ($data as $key => $value) 
        {
            $col = array();
            foreach ($columns as $column)
            {
                if ($column['data'] == $key)
                {
                    $col = $column;
                    break;
                }
            }
            if (empty($col)) continue;
            if (!empty($col['nosave'])) continue;

            $new_data[$key] = $value;
        }
       
        return $new_data;
    }

    /**
     * @param $input
     * @return mixed
     */
    function getDatatable($input)
    {
        $datatablesSchema = !empty($input['datatablesSchema'])?$input['datatablesSchema']:'default';

        $schema = $this->getConfig($datatablesSchema);
        $response = $this->datasource->datatable($schema,$input);

        return $response;
    }

    /**
     * @param $input
     * @return mixed
     */
    function getList($input)
    {
        $datatablesSchema = !empty($input['datatablesSchema'])?$input['datatablesSchema']:'default';

        $schema = $this->getConfig($datatablesSchema);

        $response = $this->datasource->getList($schema,$input);

        return $response;
    }

    /**
     * @param $input
     * @return mixed
     */
    function getAll($input)
    {
        $datatablesSchema = !empty($input['datatablesSchema'])?$input['datatablesSchema']:'default';

        $schema = $this->getConfig($datatablesSchema);

        $response = $this->datasource->getAll($schema,$input);

        return $response;
    }

    /**
     * @param string $schemaName
     * @return mixed
     */
    function getConfig($schemaName = 'default')
    {
        $config = $this->essence->datatables[$schemaName];

        $perms = [];

        $aliases = $this->essence->permissions;

        foreach ($aliases as $alias => $perm)
        {
            $perms[$alias] = $this->hasPermission($perm);
        }

        $config['permissions'] = $perms;
        return $config;
    }
}