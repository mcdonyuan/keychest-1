@extends('layouts.landing')

@section('header-title')
    Revoke API key
@endsection

@section('content-body')

    @if(!$confirm && $res)
        <div class="alert alert-info">
            <strong><i class="fa fa-question-circle"></i> You are about to confirm the API Key</strong>
        </div>

        <p>
            If you want to <strong>revoke</strong> access for the API key: {{ $res->api_key }} follow the link:
            <a href="{{ url('revokeApiKey/' . $apiKeyToken . '/?confirm=1') }}" rel="nofollow"
                    >{{ url('revokeApiKey/' . $apiKeyToken . '/?confirm=1') }}</a>.<br/>
        </p>

        <p>
            If you want to <strong>confirm</strong> access for the API key: {{ $res->api_key }} follow the link:
            <a href="{{ url('confirmApiKey/' . $apiKeyToken . '/?confirm=1') }}" rel="nofollow"
                    >{{ url('confirmApiKey/' . $apiKeyToken . '/?confirm=1') }}</a>.<br/>
        </p>

    @elseif(!empty($res))
        <div class="alert alert-success">
            <strong><i class="fa fa-check-circle"></i> We have successfully completed your request</strong>
        </div>

        <p>
            We have successfully revoked the API key <em>{{ $apiKey->api_key }}</em> from use.
        </p>

    @else
        <div class="alert alert-warning">
            <strong><i class="fa fa-exclamation-circle"></i>We can't complete your request</strong>
        </div>

        <p>
            The request you submitted contained an expired or invalid authorization token.
        </p>

        <p>
            @include('account.partials.problem_footer')
        </p>
    @endif
@endsection

