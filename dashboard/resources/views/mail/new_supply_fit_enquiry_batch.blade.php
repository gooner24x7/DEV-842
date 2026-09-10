Hello {{ $user->first_name }} - {{ $user->username }},
<br /><br />
You have {{ $total }} new
@if($total === 1)
enquiry,
@else
enquiries,
@endif
please login to your dashboard to submit quotes
<a href="https://live.thebuildchain.co.uk">HERE</a>
<br /><br />

<table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse;">
    <thead>
        <tr>
            <th>ID</th>
            <th>Postcode</th>
            <th>Type</th>
            <th>Works Package</th>
            <th>Trade</th>
            <th>Questionnaire Link</th>
        </tr>
    </thead>
    <tbody>
        @foreach($enquiries as $enquiry)
            <tr>
                <td>{{ $enquiry['data']->id }}</td>
                <td>{{ $enquiry['data']->postcode }}</td>
                <td>{{ $enquiry['data']->type }}</td>
                <td>{{ $enquiry['data']->works_package_name }}</td>
                <td>{{ $enquiry['data']->product_name }}</td>
                <td>
                    @if($enquiry['questionnaire'] !== null)
                        <a href="{{ config('app.url', '') }}/form/{{ $enquiry['questionnaire']->hash }}">Questionnaire link</a>
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

<br /><br />
Many thanks,
<br />
<a href="https://www.thebuildchain.co.uk">The Build Chain</a>
<br />
<br />
PLEASE NOTE - This is a no reply email address, if you need to send us an email please send to support@thebuildchain.co.uk<br />
<br />
