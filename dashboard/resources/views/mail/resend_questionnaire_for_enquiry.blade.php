Hello {{ $user->first_name }} - {{ $user->username }},
<br /><br />
You have a questionnaire for the enquiry # {{ $enquiry->id }}.<br /><br />
Overview of enquiry comments:<br />
{{ $enquiry->comment }}
<br /><br />

<a href="{{ config('app.url', '') }}/form/{{ $questionnaire->hash }}">Questionnaire link</a><br><br>

Many thanks,<br />
<a href="https://www.thebuildchain.co.uk">The Build Chain</a>
<br />
<br />
PLEASE NOTE - This is a no reply email address, if you need to send us an email please send to support@thebuildchain.co.uk<br />
<br />

Many thanks,
<br />
<a href="https://www.thebuildchain.co.uk">The Build Chain</a>