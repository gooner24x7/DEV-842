Dear {{ $national->contact_name }},
<br><br>
One of your customers, <b>{{ $question->contractor_name }}</b>, has sent an enquiry through
<b>The Build Chain</b>, our supply chain management platform. To respond, simply <b>reply to
this email with your quote</b> - just as you normally would for any other enquiry.
<br><br>
Below are the details of the enquiry:
<br><br>

@if (!empty($national->account_number))
    <b>Account number:</b>
    <br>
    {{ $national->account_number }}
    <br><br>
@endif

<b>Business Name:</b>
<br>
{{ $question->contractor_name }}
<br><br>
<b>Customer Contact:</b>
<br>
{{ $question->users->first_name . ' ' . $question->users->last_name }}<br>
Phone: {{ $question->users->phone }}<br>
Email: {{ $question->users->email }}
<br><br>
<b>Postcode of the Enquiry:</b>
<br>
{{ $question->postcode }}
<br><br>
<b>Delivery Date / Hire Days:</b>
<br>
{{ $question->days }}
<br><br>
<b>Enquiry Type:</b>
<br>
{{ $question->type }}
<br><br>
<b>Product Category:</b>
<br>
{{ $question->product->name }}
<br><br>
<b>Comments:</b>
<br>
{{ $question->comment }}
<br><br>
<b>Attached Files:</b>
<br>

@php
    if (!empty($question->attachments)) {
        foreach($question->attachments as $file) {
            @endphp
            <a href="{{ $file['url'] }}">{{ $file['name'] }}</a><br>
            @php
        }
    }
@endphp

<br>
Please find attached a detailed list of the requested products.<br><br>
Again, to provide your quote, just reply to this email, and your response will go directly to the customer.<br><br>
When you quote you will be automatically added to the platform and will receive enquiries.<br><br>
If you have any questions or need clarification, feel free to reply, and we’ll assist as needed.<br><br>
Thank you for your prompt attention to this enquiry. We look forward to your response.<br><br>
Best regards,<br><br>
The Build Chain Team<br><br>
03300 53 21 93<br><br>
<a href="mailto:sales@thebuildchain.co.uk">[sales@thebuildchain.co.uk]</a>
<br />
<br />
PLEASE NOTE - This is a no reply email address, if you need to send us an email please send to support@thebuildchain.co.uk<br />
<br />

Many thanks,
<br />
<a href="https://www.thebuildchain.co.uk">The Build Chain</a>
