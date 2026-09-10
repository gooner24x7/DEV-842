<?php
declare(strict_types=1);

namespace App\Service;

use App\Dto\ZohoDesk\CreateTicketRequestDto;
use App\Dto\ZohoDesk\CreateTicketResponseDto;
use AylesSoftware\ZohoDesk\Facades\ZohoDesk as ZohoDeskFacade;
use Exception;

class ZohoDeskService
{
    /**
     * @throws Exception
     */
    public function createTicket(CreateTicketRequestDto $requestDto): ?CreateTicketResponseDto
    {
        $data = ZohoDeskFacade::createTicket($requestDto->toArray());

        if (!empty($data['errorCode'] ?? '')) {
            throw new Exception($data['message'] ?? '');
        }

        return CreateTicketResponseDto::createFromArray($data);
    }
}
