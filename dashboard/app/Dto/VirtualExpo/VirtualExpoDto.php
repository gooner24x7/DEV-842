<?php
declare(strict_types=1);

namespace App\Dto\VirtualExpo;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class VirtualExpoDto
{
    private string $name;
    private string $description;
    private Carbon $dateStart;
    private Carbon $dateEnd;
    private int $userId;
    private array $videos;
    private ?UploadedFile $bgImage;
    private ?UploadedFile $bgImageLeft;
    private bool $isActive;
    private array $roles;
    /** @var UploadedFile[] $attachments */
    private array $attachments;
    /** @var UploadedFile[] $attachmentsEpd */
    private array $attachmentsEpd;
    private array $removeAttachments;

    public function __construct(
        string        $name,
        string        $description,
        Carbon        $dateStart,
        Carbon        $dateEnd,
        int           $userId,
        array         $videos,
        ?UploadedFile $bgImage,
        ?UploadedFile $bgImageLeft,
        bool          $isActive,
        array         $roles,
        array         $attachments = [],
        array         $attachmentsEpd = [],
        array         $removeAttachments = []
    )
    {
        $this->name = $name;
        $this->description = $description;
        $this->dateStart = $dateStart;
        $this->dateEnd = $dateEnd;
        $this->userId = $userId;
        $this->videos = $videos;
        $this->bgImage = $bgImage;
        $this->bgImageLeft = $bgImageLeft;
        $this->isActive = $isActive;
        $this->roles = $roles;
        $this->attachments = $attachments;
        $this->attachmentsEpd = $attachmentsEpd;
        $this->removeAttachments = $removeAttachments;
    }

    public static function createFromRequest(Request $request, User $user): self
    {
        $data = $request->validate([
            'company_name' => 'required',
            'description' => '',
            'date_start' => 'required',
            'date_end' => 'required',
            'videos' => 'required',
            'attachments' => '',
            'attachments_epd' => '',
            'remove_attachments' => '',
            'roles' => 'required',
            'banner_image' => '',
            'banner_image_left' => '',
            'is_active' => '',
        ]);

        $dataStart = Carbon::createFromFormat('Y-m-d', current(explode(' ', $data['date_start'])) ?? '');
        $dataEnd = Carbon::createFromFormat('Y-m-d', current(explode(' ', $data['date_end'])) ?? '');
        $bannerImage = $request->file('banner_image');
        $bannerImageLeft = $request->file('banner_image_left');

        return new self(
            $data['company_name'] ?? '',
            $data['description'] ?? '',
            $dataStart,
            $dataEnd,
            $user->getId(),
            $data['videos'] ?? '',
            $bannerImage,
            $bannerImageLeft,
            (bool)($data['is_active'] ?? false),
            $data['roles'] ?? [],
            $request->file('attachments') ?? [],
            $request->file('attachments_epd') ?? [],
            $data['remove_attachments'] ?? []
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getDateStart(): Carbon
    {
        return $this->dateStart;
    }

    public function getDateEnd(): Carbon
    {
        return $this->dateEnd;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getVideos(): array
    {
        return $this->videos;
    }

    public function getBgImage(): ?UploadedFile
    {
        return $this->bgImage;
    }

    public function getBgImageLeft(): ?UploadedFile
    {
        return $this->bgImageLeft;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getAttachments(): array
    {
        return $this->attachments;
    }

    public function getAttachmentsEpd(): array
    {
        return $this->attachmentsEpd;
    }

    public function getRemoveAttachments(): array
    {
        return $this->removeAttachments;
    }
}
