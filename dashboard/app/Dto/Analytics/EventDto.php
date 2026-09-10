<?php
declare(strict_types=1);

namespace App\Dto\Analytics;

use Illuminate\Http\Request;

class EventDto
{
    private int $objectId;
    private string $objectType;
    private string $action;
    private string $location;
    private int $userId;
    private string $url;
    private string $target;

    public function __construct(int $objectId, string $objectType, string $action, string $location, string $url, string $target)
    {
        $this->objectId = $objectId;
        $this->objectType = $objectType;
        $this->action = $action;
        $this->location = $location;
        $this->url = $url;
        $this->target = $target;
    }

    public static function createFromRequest(Request $request): self
    {
        $data = $request->validate([
            'object_id' => 'required',
            'object_type' => 'required',
            'action' => 'required',
            'location' => 'required',
            'url' => '',
        ]);

        return new self(
            $data['object_id'] ?? 0,
            $data['object_type'] ?? '',
            $data['action'] ?? '',
            $data['location'] ?? '',
            $data['url'] ?? '',
            $data['target'] ?? '',
        );
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getObjectId(): int
    {
        return $this->objectId;
    }

    public function getObjectType(): string
    {
        return $this->objectType;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getTarget(): string
    {
        return $this->target;
    }
}
