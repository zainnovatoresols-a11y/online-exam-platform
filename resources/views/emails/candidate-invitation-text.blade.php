Online Quiz Platform

Assessment invitation

Hello{{ $candidateName ? ' '.$candidateName : '' }},

You have been invited to complete this quiz:

{{ $testTitle }}

From: {{ $owner }}

@if ($startsAt)
Starts: {{ $startsAt }}
@endif
@if ($expiresAt)
Expires: {{ $expiresAt }}
@endif

Please read and accept the quiz policy before entering your candidate details.

Open Quiz Link:
{{ $url }}

Do not share this invitation link with anyone else.
