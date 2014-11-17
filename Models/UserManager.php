<?php

namespace Modules\User\Models;

use Mindy\Helper\Password;
use Mindy\Orm\Manager;

/**
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 24/04/14.04.2014 21:05
 */

/**
 * Class UserManager
 * @package Modules\User
 */
class UserManager extends Manager
{
    protected function getEventManager()
    {
        static $eventManager;
        if ($eventManager === null) {
            if (class_exists('\Mindy\Base\Mindy')) {
                $eventManager = \Mindy\Base\Mindy::app()->getComponent('signal');
            } else {
                $eventManager = new \Mindy\Event\EventManager();
            }
        }
        return $eventManager;
    }

    /**
     * Create not privileged user
     * @param $username
     * @param $password
     * @param $email
     * @param array $extra
     * @param bool $notify
     * @return array|\Mindy\Orm\Model Errors or created model
     */
    public function createUser($username, $password, $email = null, array $extra = [], $notify = true)
    {
        $model = $this->getModel();
        $model->setAttributes(array_merge([
            'username' => $username,
            'email' => $email,
            'password' => Password::hashPassword($password),
            'activation_key' => $this->generateActivationKey()
        ], $extra));

        if ($model->isValid() && $model->save()) {
            $groups = Group::objects()->filter(['is_default' => true])->all();
            foreach ($groups as $group) {
                $model->groups->link($group);
            }

            $permission = Permission::objects()->filter(['is_default' => true])->all();
            foreach ($permission as $perm) {
                $model->permissions->link($perm);
            }

            if ($notify) {
                $this->getEventManager()->send($model, 'createRawUser', $model, $password);
            }
        }

        return $model;
    }

    /**
     * @param $password
     * @param null $email
     * @param array $extra
     * @param bool $notify
     * @return array|\Mindy\Orm\Model
     */
    public function createRandomUser($password, $email = null, array $extra = [], $notify = true)
    {
        $username = 'user_' . substr($this->generateActivationKey(), 0, 6);
        return $this->createUser($username, $password, $email, $extra, $notify);
    }

    /**
     * @param $password
     * @return bool
     */
    public function setPassword($password)
    {
        return $this->getModel()->setAttributes([
            'password' => Password::hashPassword($password)
        ])->save(['password']);
    }

    /**
     * @param $username
     * @param $password
     * @param null $email
     * @param array $extra
     * @param bool $notify
     * @return array|\Mindy\Orm\Model
     */
    public function createSuperUser($username, $password, $email = null, array $extra = [], $notify = true)
    {
        return $this->createUser($username, $password, $email, array_merge($extra, [
            'is_superuser' => true,
            'is_active' => true,
            'is_staff' => true
        ]), $notify);
    }

    /**
     * @param $username
     * @param $password
     * @param null $email
     * @param array $extra
     * @param bool $notify
     * @return array|\Mindy\Orm\Model
     */
    public function createStaffUser($username, $password, $email = null, array $extra = [], $notify = true)
    {
        return $this->createUser($username, $password, $email, array_merge($extra, [
            'is_staff' => true
        ]), $notify);
    }

    /**
     * @return string
     */
    public function generateActivationKey()
    {
        return substr(md5(Password::generateSalt()), 0, 10);
    }

    /**
     * @return bool
     */
    public function changeActivationKey()
    {
        return $this->getModel()->setAttributes([
            'activation_key' => $this->generateActivationKey()
        ])->save(['activation_key']);
    }

    /**
     * @return Manager
     */
    public function active()
    {
        return $this->filter(['is_active' => true]);
    }
}
