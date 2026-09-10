<div style="text-transform: uppercase;">
    @if ($user->isBillingUser())
        This is your billing account (management account and not your user account for the platform). You will receive the user account credentials separately.
    @else
        This is your user account to use the platform.
    @endif
</div>

<br /><br />
Hello {{ $user->first_name }},
<br /><br />
Thank you for using The Build Chain.<br /><br />
Your login details are below:
<br /><br />
Username: {{ $user->username }}<br />
Password: {{ $password }}
<br /><br />
<a href="https://live.thebuildchain.co.uk">Click here</a> to head over to your personal dashboard, in your dashboard you can submit new enquiries, managing existing enquiries, view supplier quotes and live chat with suppliers before confirming your hire.
<br /><br />
Many thanks,<br />
<a href="https://www.thebuildchain.co.uk">The Build Chain</a>
