<?php


namespace App\Service;

use AylesSoftware\ZohoDesk\ZohoOAuth;

class ZohoOauthCustom extends ZohoOAuth
{
    public function flow()
    {
        if ($this->request->has('code')) {
            $this->generate('authorization_code', ['code' => $this->request->input('code')]);

            return redirect(config('zoho-desk.redirect_after_authorization_url'));
        }

        return redirect(
            $this->provider->getAuthorizationUrl([
                'scope' => [
                    'Desk.tickets.ALL,ZohoCRM.settings.fields.ALL,ZohoCRM.settings.fields.ALL,ZohoCRM.modules.ALL,ZohoCRM.users.ALL,ZohoCRM.org.ALL,ZohoCRM.coql.READ,ZohoCRM.Files.CREATE',
                ],
            ])
        );
    }

    public function getToken(): string
    {
        return $this->credentials;
    }
}
