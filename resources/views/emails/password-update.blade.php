@component('mail::message')
# hi, {{  $user->firstname }} <br />

<p>You've done a good job? You have successfully reset your password, you'll need to login again with your new password.</p>

@component('mail::button', ['url' => route('sign-in')])
    Login to your account
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
