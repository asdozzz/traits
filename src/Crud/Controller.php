<?php

namespace Asdozzz\Traits\Crud;

trait Controller
{
    public $exceptions_crud = [
        'create' => 'vika.exceptions.permissions.missings.create',
        'edit' => 'vika.exceptions.permissions.missings.edit',
        'read' => 'vika.exceptions.permissions.missings.read',
        'delete' => 'vika.exceptions.permissions.missings.delete',
        'listing' => 'vika.exceptions.permissions.missings.listing',
    ];

    function store()
    {
        $input = \Request::all();
        
        if (!$this->business->hasPermission('create'))
        {
            throw new \Exception(\Lang::get($this->exceptions_crud['create']));
        }

        $result = $this->business->create($input);

        if (empty($result))
        {
            throw new \Exception(\Lang::get('vika.exceptions.other'));
        }

        return \Response::json($result);
    }

    function update()
    {
        $input = \Request::all();

        if (!$this->business->hasPermission('edit'))
        {
            throw new \Exception(\Lang::get($this->exceptions_crud['edit']));
        }

        $result = $this->business->update($input);

        if (empty($result))
        {
            throw new \Exception(\Lang::get('vika.exceptions.other'));
        }

        return \Response::json($result);
    }

    function show($id)
    {
        $input = \Request::all();

        if (!$this->business->hasPermission('read'))
        {
            throw new \Exception(\Lang::get($this->exceptions_crud['read']));
        }
        
        $result = $this->business->read($id);
        return \Response::json($result);
    }

    function destroy($id)
    {
        if (!$this->business->hasPermission('delete'))
        {
            throw new \Exception(\Lang::get($this->exceptions_crud['delete']));
        }
        
        $result = $this->business->delete($id);

        return \Response::json($result);
    }


    public function index()
    {
        $input = \Request::all();

        if (!$this->business->hasPermission('listing'))
        {
            throw new \Exception(\Lang::get($this->exceptions_crud['listing']));
        }

        $result = $this->business->getDatatable($input);
        
        return \Response::json($result);
    }

    public function all()
    {
        $input = \Request::all();

        if (!$this->business->hasPermission('listing'))
        {
            throw new \Exception(\Lang::get($this->exceptions_crud['listing']));
        }

        $result = $this->business->getAll($input);

        return \Response::json($result);
    }
}