<?php

declare(strict_types=1);

namespace App\Dto\Questionnaire;

use Illuminate\Http\Request;

class SessionDto
{
    private int $userId;
    private ?int $inquiryId;
    private ?int $projectId;
    private ?int $worksPackageId;

    public function __construct(
        int     $userId,
        ?int    $inquiryId,
        ?int    $projectId,
        ?int    $worksPackageId
    )
    {
        $this->userId = $userId;
        $this->inquiryId = $inquiryId;
        $this->projectId = $projectId;
        $this->worksPackageId = $worksPackageId;
    }

    public static function createFromRequest(Request $request, int $userId = null): self
    {
        $data = $request->validate([
            'inquiryId' => 'required',
            'projectId' => '',
            'worksPackageId' => '',
        ]);

        return new self(
            $userId,
            $data['inquiryId'] ? (int)$data['inquiryId'] : null,
            $data['projectId'] ? (int)$data['projectId'] : null,
            $data['worksPackageId'] ? (int)$data['worksPackageId'] : null,
        );
    }

    public function getProjectId(): int
    {
        return $this->projectId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getInquiryId(): ?int
    {
        return $this->inquiryId;
    }

    public function getWorksPackageId(): ?int
    {
        return $this->worksPackageId;
    }
}
