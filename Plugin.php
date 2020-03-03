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

            $fields = $form->getFields();

            $fields['send_invite']->value = false;
            $fields['send_invite']->defaults = false;
            $fields['send_invite']->hidden = true;

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
                    'login' => $user->login,
                    'link' => $link,
                ];

                Mail::send('studiobosco.newuserpasswordreset::mail.invite_with_restore', $data, function ($message) use ($user) {
                    $message->to($user->email, $user->full_name)->subject(trans('studiobosco.newuserpasswordreset::lang.subject_invite_with_reset'));
                });
            });
        });
    }

    public function registerMailTemplates()
    {
        return [
            'studiobosco.newuserpasswordreset::mail.invite_with_restore',
        ];
    }
}
