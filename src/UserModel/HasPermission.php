<?php
/**
 * Created by PhpStorm.
 * User: asd
 * Date: 27.10.2017
 * Time: 17:19
 */
namespace Asdozzz\Traits\UserModel;

trait HasPermission
{
    protected $users_permissions = array();
    function hasPermission($mark)
    {
        if (empty($this->users_permissions[$this->id]))
        {
            $permissions = \Asdozzz\Users\Datasource\Permissions::getPermissionsByUserId($this->id);
            $this->users_permissions[$this->id] = $permissions;
        }
        else
        {
            $permissions = $this->users_permissions[$this->id];
        }

        return $permissions->contains('slug',$mark);
    }
}