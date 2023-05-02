@component('mail::message')
 # Hi {{ $user->firstname }}

<p>We received a request to reset your password, please enter the token below:</p>

@component('mail::button', ['url' => '#' ])
    {{$token}}
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
