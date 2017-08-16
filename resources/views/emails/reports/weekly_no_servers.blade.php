@extends('emails.base')

@section('content')
    <p>Hi,</p>

    <p>
        Please log in to your dashboard and add some of your servers or registered domains to enjoy
        the power and simplicity of KeyChest.

        You will have to visit <a href="{{ url('/login') }}">{{ url('/login') }}</a> and use the recipient
        address of this email.
    </p>

    <p>
        @component('emails.partials.unsubscribe')
        @endcomponent
    </p>

    <p>
    Kind regards <br/>
          <i>{{ config('app.name') }} &amp; Enigma Bridge</i>
    </p>
@endsection
