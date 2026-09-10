<?php
declare(strict_types=1);

namespace App\Kafka;

use App\DataProvider\UserRoleDataProvider;
use App\Dto\User\UserDto;
use App\Kafka\Dto\OrganisationDto;
use App\Models\Role;
use App\Service\UserService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RdKafka\Consumer;
use RdKafka\TopicConf;

class CrmContactsConsumer
{
    private UserService $userService;
    private UserRoleDataProvider $userRoleDataProvider;

    public function __construct(UserService $userService, UserRoleDataProvider $userRoleDataProvider)
    {
        $this->userService = $userService;
        $this->userRoleDataProvider = $userRoleDataProvider;
    }

    public function consume(Consumer $consumer): void
    {
        $consumer1 = $consumer;

        $topicConf = new TopicConf();
        $topicConf->set('offset.store.method', 'broker');

        $topic = $consumer1->newTopic(Reference::TASKS_TOPIC, $topicConf);
        $topic->consumeStart(0, RD_KAFKA_OFFSET_STORED);

        while (true) {
            // The first argument is the partition (again).
            // The second argument is the timeout.
            $msg = $topic->consume(0, 1000);
            if (null === $msg || $msg->err === RD_KAFKA_RESP_ERR__PARTITION_EOF) {
                // Constant check required by librdkafka 0.11.6. Newer librdkafka versions will return NULL instead.
                Log::debug('null msg, resp err partition eof', []);
            } elseif ($msg->err) {
                Log::debug('error reading task', [
                    'error' => $msg->errstr(),
                ]);

                echo $msg->errstr(), "\n";
                break;
            } else {
                echo $msg->payload, "\n";

                $dto = OrganisationDto::createFromArray(json_decode($msg->payload, true));

                $billingUserRole = $this->userRoleDataProvider->getBySlug(Role::ROLE_BILLING_USER_SLUG);
                $companyUserRole = $this->userRoleDataProvider->getBySlug(Role::ROLE_COMPANY_SLUG);

                if (!$billingUserRole || !$companyUserRole) {
                    die('failed to find user roles');
                }

                $passwordBillingUser = Hash::make(Str::random(8));
                $userDto = new UserDto(
                    $dto->getName(),
                    $dto->getName(),
                    'bl-' . UserService::slugify($dto->getName()),
                    $dto->getEmail(),
                    $passwordBillingUser,
                    [
                        $billingUserRole->getId(),
                    ],
                    $dto->getPostcode(),
                    $dto->getPhone(),
                    $dto->getCountry(),
                    $dto->getCity(),
                    $dto->getAddressLine1(),
                    $dto->getAddressLine2(),
                    [],
                    null,
                    [],
                    false,
                    null,
                    null
                );

                $billingUser = $this->userService->store($userDto);
                if (!$billingUser) {
                    Log::debug('failed to create billing user', [
                        'email' => $userDto->getEmail(),
                    ]);

                    continue;
                }

                //create companies
                foreach ($dto->getBranches() as $branch) {
                    $passwordCompany = Hash::make(Str::random(8));

                    $userDto = new UserDto(
                        $branch->getFirstName(),
                        $branch->getLastName(),
                        'us-' . UserService::slugify($dto->getName()),
                        $branch->getEmail(),
                        $passwordCompany,
                        [
                            $companyUserRole->getId()
                        ],
                        $dto->getPostcode(),
                        $dto->getPhone(),
                        $dto->getCountry(),
                        $dto->getCity(),
                        $dto->getAddressLine1(),
                        $dto->getAddressLine2(),
                        [],
                        $billingUser->getId(),
                        [],
                        false,
                        null,
                        null
                    );

                    $companyUser = $this->userService->store($userDto);
                    if (!$companyUser) {
                        Log::debug('failed to create company user', [
                            'email' => $userDto->getEmail(),
                        ]);
                    }
                }
            }
        }

        Log::debug('consume stop');

        $topic->consumeStop(0);
    }
}
