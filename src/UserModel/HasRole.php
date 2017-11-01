<?php
/**
 * Created by PhpStorm.
 * User: asd
 * Date: 27.10.2017
 * Time: 17:19
 */
namespace Asdozzz\Traits\UserModel;

trait HasRole
{
    protected $users_roles = array();
    function hasRole($mark)
    {
        if (empty($this->users_roles[$this->id]))
        {
            $roles = \Asdozzz\Users\Datasource\Roles::getRolesByUserId($this->id);
            $this->users_roles[$this->id] = $roles;
        }
        else
        {
            $roles = $this->users_roles[$this->id];
        }

        return $roles->contains('slug',$mark);
    }
}