<?php

namespace Modules\User\Forms;

use Mindy\Base\Mindy;
use Mindy\Form\Fields\CharField;
use Mindy\Form\Form;
use Mindy\Helper\Params;
use Modules\User\Models\User;
use Modules\User\UserModule;

class RecoverForm extends Form
{
    public function getFields()
    {
        return [
            'username_or_email' => [
                'class' => CharField::className(),
                'label' => UserModule::t('Username or email')
            ]
        ];
    }

    public function send()
    {
        $model = User::objects()->filter(
            [strpos($this->username_or_email, "@") ? 'email' : 'username' => $this->username_or_email]
        )->get();

        if ($model && $model->objects()->changeActivationKey()) {
            return Mindy::app()->mail->fromCode('user.recover', $model->email, [
                'username' => $model->username,
                'sitename' => Params::get('core.sitename'),
                'activation_url' => Mindy::app()->urlManager->reverse('user.recover', ['key' => $model->activation_key]),
            ]);
        }

        return false;
    }
}
