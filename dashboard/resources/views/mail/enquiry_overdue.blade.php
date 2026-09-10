Hi {{ $user->first_name }}<br /><br />

We hope this message finds you well.<br />
We noticed that the due date for your enquiry has passed by 7 days,<br />
and we would like to check in with you on the progress.<br /><br />

<b>Enquiry Info</b><br />
Project: {{ $question->project_name }}<br />
Works Package: {{ $question->works_package->name }}<br />
Reference ID: {{ $question->reference_id }}<br />
Postcode: {{ $question->postcode }}<br />
Due Date: {{ $question->days }}<br />
Live/Tender: {{ $question->type }}<br />
Product: {{ $question->product->name ?? '' }}<br />
Product Type: {{ $question->product->category->name ?? '' }}<br />
Comments: {{ $question->comment }}<br />
Total Quotes: {{ $question->total_quotes }}<br />

@if (is_array($question->attachments))
    Documents attached:<br />
    @foreach ($question->attachments as $document)
        {{ $document['name'] }}<br />
    @endforeach
@endif
<br />

Could you please let us know how you've managed with your enquiry<br />
by clicking the link that best applies to your situation below?<br /><br />

<div style="color: #cc1111">WARNING!!! You can only view 1 link as you only have 1 access token</div><br /><br />

<a href="{{ $app_url }}/enquiry-action?id={{ $question->id }}&action=accept_quote&token={{ $token }}">[Purchased through the platform]</a><br /><br />
<a href="{{ $app_url }}/enquiry-action?id={{ $question->id }}&action=customer_response&token={{ $token }}">[Purchased outside the platform]</a><br /><br />
<a href="{{ $app_url }}/enquiry-action?id={{ $question->id }}&action=update_enquiry&token={{ $token }}">[Have yet to make a decision]</a><br /><br /><br />

Thank you for your time, and we look forward to hearing from you.<br /><br />
Best Regards,<br />
<a href="https://www.thebuildchain.co.uk">The Build Chain Ltd.</a>
