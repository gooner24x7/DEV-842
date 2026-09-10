Hello {{ $user->first_name }} - {{ $user->username }},
<br/><br/>
Your quote has been accepted. (ID: {{ $quote->id }})
<br/>
<a href="{{ $url }}">View quote</a>
<br/><br/>

Many thanks,
<br/>
<a href="https://www.thebuildchain.co.uk">The Build Chain</a>
