<?php
declare(strict_types=1);

namespace App\Service;

use App\Dto\SupplyChainUser\SupplyChainUserDto;
use App\Models\Role;
use App\Models\SupplyChainUser;
use App\Models\User;
use App\Repository\SupplyChainUserRepository;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;

class SupplyChainUserService
{
    private SupplyChainUserRepository $supplyChainUserRepository;

    public function __construct(SupplyChainUserRepository $supplyChainUserRepository)
    {
        $this->supplyChainUserRepository = $supplyChainUserRepository;
    }

    public function importCsv(UploadedFile $file, User $user): array
    {
        $inputFileType = 'Csv';
        $inputFileName = $file->getRealPath();

        /**  Create a new Reader of the type defined in $inputFileType  **/
        $reader = IOFactory::createReader($inputFileType);

        /**  Load $inputFileName to a Spreadsheet Object  **/
        $spreadsheet = $reader->load($inputFileName);
        $worksheet = $spreadsheet->getActiveSheet();

        $result = [
            'total' => 0,
            'errors' => []
        ];

        $rows = $worksheet->toArray(null, true, true, false);

        foreach ($rows as $index => $row) {
            if ($index === 0) {
                continue;
            }

            // check if email is already in db
            $emailExists = $this->emailExists($row[4]);

            if ($emailExists) {
                $result['errors'][] = "User with email $row[4] already exists";
                continue;
            }

            $userArr = [
                'role_id'       => $this->getRoleId($row[0]),
                'first_name'    => $row[1],
                'last_name'     => $row[2],
                'business_name' => $row[3],
                'email'         => $row[4],
                'phone'         => $row[5],
                'addr_line_1'   => $row[8],
                'addr_line_2'   => $row[9],
                'city'          => $row[7],
                'postcode'      => $row[6]
            ];

            $dto = SupplyChainUserDto::createFromArray($userArr);
            $this->supplyChainUserRepository->store($dto, $user);
            $result['total']++;
        }

        return $result;
    }

    private function getRoleId(?string $roleName): ?int
    {
        if (empty($roleName)) {
            return null;
        }

        $roleName = strtolower($roleName);
        $query = Role::query()->select('id')->whereRaw("lower(name) = '$roleName'");

        return $query->pluck('id')->first() ?? null;
    }

    public function emailExists(string $email): bool
    {
        //$query = SupplyChainUser::query()->selectRaw('count(id) AS total')->whereRaw("email = '$email'");

        $total = SupplyChainUser::where(['email' => $email])->count();

        return $total > 0;
    }
}
