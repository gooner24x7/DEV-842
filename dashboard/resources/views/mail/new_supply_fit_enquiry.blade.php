Hello {{ $user->first_name }} - {{ $user->username }},
<br /><br />
You have a new enquiry, please login to your dashboard to submit a quote
<a href="https://live.thebuildchain.co.uk">HERE</a>.<br /><br />
Overview of enquiry comments:<br />
{{ $enquiry->comment }}
<br /><br />

@isset($questionnaire)
    <a href="{{ config('app.url', '') }}/form/{{ $questionnaire->hash }}">Questionnaire link</a><br><br>
@endisset

Many thanks,<br />
<a href="https://www.thebuildchain.co.uk">The Build Chain</a>
<br />
<br />
PLEASE NOTE - This is a no reply email address, if you need to send us an email please send to support@thebuildchain.co.uk<br />
<br />
