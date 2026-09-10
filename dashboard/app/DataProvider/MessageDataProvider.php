<?php

declare(strict_types=1);

namespace App\DataProvider;

use App\Dto\Message\MessageDto;
use App\Dto\Message\SearchParamsDto;
use App\Models\Message;
use App\Repository\MessageRepository;
use Illuminate\Database\Eloquent\Collection;
use Redis;
use RedisException;

class MessageDataProvider extends BaseDataProvider
{
    const string MESSAGES_FIND_CACHE = 'message_find_%d_%d_%s';
    const int MESSAGES_FIND_CACHE_TIMEOUT = 3600 * 24;

    private MessageRepository $messageRepository;

    public function __construct(MessageRepository $messageRepository, Redis $redis)
    {
        parent::__construct($redis);

        $this->messageRepository = $messageRepository;
    }

    public function chatList(SearchParamsDto $dto, string $type): Collection
    {
        return $this->messageRepository->chatList($dto, $type);
    }

    /**
     * @throws RedisException
     */
    public function find(SearchParamsDto $dto, $useCache = true): Collection
    {
        $key = sprintf(self::MESSAGES_FIND_CACHE, $dto->getAid(), $dto->getVirtualExpoSharedContactId(), $dto->getHash());

        $result = $this->redis->get($key);
        if ($useCache && $result) {
            return unserialize($result);
        }

        $response = $this->messageRepository->find($dto);

        $this->redis->set($key, serialize($response), self::MESSAGES_FIND_CACHE_TIMEOUT);

        return $response;
    }

    /**
     * @throws RedisException
     */
    public function store(MessageDto $dto): ?Message
    {
        $message = $this->messageRepository->store($dto);
        if ($message !== null) {
            $this->resetFindCache($dto);
        }

        return $message;
    }

    /**
     * @throws RedisException
     */
    private function resetFindCache(MessageDto $dto): void
    {
        $this->resetCachePatterns([
            sprintf(self::MESSAGES_FIND_CACHE, $dto->getAid(), $dto->getVirtualExpoSharedContactId(), '*')
        ]);
    }
}
