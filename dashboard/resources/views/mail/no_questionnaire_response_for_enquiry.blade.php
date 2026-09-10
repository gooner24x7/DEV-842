Hello {{ $user->first_name }} - {{ $user->username }},
<br/><br/>
We noticed that you submitted your quote to {{ $company->first_name }},
but haven’t yet completed the required questionnaire.
To proceed, please take a moment to fill it out using the following link:
<a href="{{ config('app.url', '') }}/form/{{ $hash }}">Questionnaire link</a><br><br>
<br/><br/>

Many thanks,<br/>
<a href="https://www.thebuildchain.co.uk">The Build Chain</a>
