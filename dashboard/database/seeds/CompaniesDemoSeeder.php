<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompaniesDemoSeeder extends Seeder
{

    /**
     * @throws Exception
     */
    public function run()
    {
        $billingUserRole = Role::where(['slug' => Role::ROLE_BILLING_USER_SLUG])->first();
        if (!$billingUserRole) {
            throw new Exception("No role");
        }

        $userRole = Role::where(['slug' => Role::ROLE_COMPANY_SLUG])->first();
        if (!$userRole) {
            throw new Exception("No role");
        }

        $i = 1;
        foreach (DB::table('Sheet1')->cursor() as $newUser) {
            if (empty($newUser->CompanyName)) {
                continue;
            }

            $billingUsername = $this->slugify($newUser->CompanyName);

            $billingUser = User::where(['username' => $billingUsername])->first();
            if (!$billingUser) {
                $billingUser = User::create([
                    'password'   => $newUser->password,
                    'first_name' => $newUser->CompanyName,
                    'last_name'  => 'N/A',
                    'lat'        => 0,
                    'long'       => 0,
                    'postcode'   => '',
                    'username'   => $billingUsername,
                    'email'      => $newUser->{'Test Email'} ?? "",
                ]);
                $billingUser->save();
                $billingUser->roles()->sync($billingUserRole->id);

                echo "created a billing user $billingUsername\n";
            }

            $user = User::create([
                'password'        => $newUser->password,
                'first_name'      => $newUser->CompanyName,
                'last_name'       => $newUser->Address1 ?? "",
                'lat'             => (float)$newUser->Latitude ?? 0,
                'long'            => (float)$newUser->Longitude ?? 0,
                'postcode'        => $newUser->Postcode ?? "",
                'username'        => $newUser->Username ?? "",
                'email'           => $newUser->{'Test Email'} ?? "",
                'addr_line_1'     => trim($newUser->{'Address 2'} . " " . $newUser->{'Address 3'}),
                'addr_line_2'     => trim($newUser->{'Address 4'} . " " . $newUser->{'Address 5'} . " " . $newUser->{'Address 6'}),
                'phone'           => $newUser->Phone ?? '',
                'billing_user_id' => $billingUser->id,
            ]);

            $user->save();

            $user->roles()->sync($userRole->id);

            $productIds = array_filter(array_map(function ($item) {
                return (int)$item;
            }, explode(",", $newUser->supplier_prod_numbers)));

            if (!empty($productIds)) {
                $user->products()->sync($productIds);
            }

            $i++;
        }
    }

    private function slugify(string $text): string
    {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }
}
