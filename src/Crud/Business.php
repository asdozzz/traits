<?php

namespace Asdozzz\Traits\Crud;

trait Business
{
    public function getList($input = array())
    {
        return $this->model->getList($input);
    }

	public function create($data)
	{
		return $this->model->create($data);
	}

	public function update($data)
	{
		return $this->model->update($data);
	}

	public function read($data)
	{
		return $this->model->read($data);
	}

	public function delete($data)
	{
		return $this->model->delete($data);
	}

	public function hasPermission($mark)
	{
		return $this->model->hasPermission($mark);
	}

	public function getConfig($schema)
	{
		return $this->model->getConfig($schema);
	}

	public function getDatatable($input)
	{
		return $this->model->getDatatable($input);
	}
}