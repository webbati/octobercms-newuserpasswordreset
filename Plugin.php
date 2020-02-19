<?php namespace StudioBosco\NewUserPasswordReset;

use Mail;
use Backend;
use Lang;
use Backend\Models\User;
use System\Classes\PluginBase;
use Backend\Controllers\Users;

/**
 * NewUserPasswordReset Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * Boot method, called right before the request route.
     *
     * @return array
     */
    public function boot()
    {
        Users::extendFormFields(function ($form, $model, $context) {
            if (!($model instanceOf User && $context === 'create')) {
                return;
            }

            $form->addTabFields([
                'generate_password' => [
                    'label' => null,
                    'type' => 'partial',
                    'path' => '$/studiobosco/newuserpasswordreset/partials/generate_password_button.htm',
                ],
            ]);
        });

        User::extend(function ($user) {
            $user->bindEvent('model.afterCreate', function () use ($user) {
                $code = $user->getResetPasswordCode();
                $link = Backend::url('backend/auth/reset/' . $user->id . '/' . $code);

                $data = [
                    'name' => $user->full_name,
                    'link' => $link,
                ];

                Mail::send('backend::mail.restore', $data, function ($message) use ($user) {
                    $message->to($user->email, $user->full_name)->subject(trans('backend::lang.account.password_reset'));
                });
            });
        });
    }
}
