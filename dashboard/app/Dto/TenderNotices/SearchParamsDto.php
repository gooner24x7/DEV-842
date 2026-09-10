<?php
declare(strict_types=1);

namespace App\Dto\TenderNotices;

use Carbon\Carbon;
use Illuminate\Http\Request;

class SearchParamsDto
{
    private array $types;
    private array $statuses;
    private ?string $keyword;
    private ?string $queryString;
    private ?string $regions;
    private ?string $postcode;
    private ?float $radius;
    private ?float $valueFrom;
    private ?float $valueTo;
    private ?Carbon $publishedFrom;
    private ?Carbon $publishedTo;
    private ?Carbon $deadlineFrom;
    private ?Carbon $deadlineTo;
    private ?Carbon $approachMarketFrom;
    private ?Carbon $approachMarketTo;
    private ?Carbon $awardedFrom;
    private ?Carbon $awardedTo;
    private ?bool $isSubcontract;
    private ?bool $suitableForSme;
    private ?bool $suitableForVco;
    private ?bool $awardedToSme;
    private ?bool $awardedToVcse;
    private array $cpvCodes;

    public function __construct(
        array $types,
        array $statuses,
        ?string $keyword,
        ?string $queryString,
        ?string $regions,
        ?string $postcode,
        ?float $radius,
        ?float $valueFrom,
        ?float $valueTo,
        ?Carbon $publishedFrom,
        ?Carbon $publishedTo,
        ?Carbon $deadlineFrom,
        ?Carbon $deadlineTo,
        ?Carbon $approachMarketFrom,
        ?Carbon $approachMarketTo,
        ?Carbon $awardedFrom,
        ?Carbon $awardedTo,
        ?bool $isSubcontract,
        ?bool $suitableForSme,
        ?bool $suitableForVco,
        ?bool $awardedToSme,
        ?bool $awardedToVcse,
        array $cpvCodes
    ) {
        $this->types = $types;
        $this->statuses = $statuses;
        $this->keyword = $keyword;
        $this->queryString = $queryString;
        $this->regions = $regions;
        $this->postcode = $postcode;
        $this->radius = $radius;
        $this->valueFrom = $valueFrom;
        $this->valueTo = $valueTo;
        $this->publishedFrom = $publishedFrom;
        $this->publishedTo = $publishedTo;
        $this->deadlineFrom = $deadlineFrom;
        $this->deadlineTo = $deadlineTo;
        $this->approachMarketFrom = $approachMarketFrom;
        $this->approachMarketTo = $approachMarketTo;
        $this->awardedFrom = $awardedFrom;
        $this->awardedTo = $awardedTo;
        $this->isSubcontract = $isSubcontract;
        $this->suitableForSme = $suitableForSme;
        $this->suitableForVco = $suitableForVco;
        $this->awardedToSme = $awardedToSme;
        $this->awardedToVcse = $awardedToVcse;
        $this->cpvCodes = $cpvCodes;
    }

    public static function createFromRequest(Request $request): self
    {
        $params = $request->all();

        $types = $params['types'] ?? [];
        $statuses = $params['statuses'] ?? [];
        $keyword = $params['keyword'] ?? null;
        $queryString = $params['queryString'] ?? null;
        $regions = !empty($params['regions']) ? implode(',', $params['regions']): null;
        $postcode = $params['postcode'] ?? null;
        $radius = $params['radius'] ?? null;
        $valueFrom = !empty($params['valueFrom']) ? (float)$params['valueFrom'] : null;
        $valueTo = !empty($params['valueTo']) ? (float)$params['valueTo'] : null;
        $publishedFrom = self::createDateFromFormat($params['publishedFrom'] ?? null);
        $publishedTo = self::createDateFromFormat($params['publishedTo'] ?? null);
        $deadlineFrom = self::createDateFromFormat($params['deadlineFrom'] ?? null);
        $deadlineTo = self::createDateFromFormat($params['deadlineTo'] ?? null);
        $approachMarketFrom = self::createDateFromFormat($params['approachMarketFrom'] ?? null);
        $approachMarketTo = self::createDateFromFormat($params['approachMarketTo'] ?? null);
        $awardedFrom = self::createDateFromFormat($params['awardedFrom'] ?? null);
        $awardedTo = self::createDateFromFormat($params['awardedTo'] ?? null);
        $isSubcontract = !empty($params['isSubcontract']) ? (bool)$params['isSubcontract'] : null;
        $suitableForSme = !empty($params['suitableForSme']) ? (bool)$params['suitableForSme'] : null;
        $suitableForVco = !empty($params['suitableForVco']) ? (bool)$params['suitableForVco'] : null;
        $awardedToSme = !empty($params['awardedToSme']) ? (bool)$params['awardedToSme'] : null;
        $awardedToVcse = !empty($params['awardedToVcse']) ? (bool)$params['awardedToVcse'] : null;
        $cpvCodes = $params['cpvCodes'] ?? [];

        return new self(
            $types,
            $statuses,
            $keyword,
            $queryString,
            $regions,
            $postcode,
            $radius,
            $valueFrom,
            $valueTo,
            $publishedFrom,
            $publishedTo,
            $deadlineFrom,
            $deadlineTo,
            $approachMarketFrom,
            $approachMarketTo,
            $awardedFrom,
            $awardedTo,
            $isSubcontract,
            $suitableForSme,
            $suitableForVco,
            $awardedToSme,
            $awardedToVcse,
            $cpvCodes
        );
    }

    public function toArray(): array
    {
        return [
            'types' => $this->getTypes(),
            'statuses' => $this->getStatuses(),
            'keyword' => $this->getKeyword(),
            'queryString' => $this->getQueryString(),
            'regions' => $this->getRegions(),
            'postcode' => $this->getPostcode(),
            'radius' => $this->getRadius(),
            'valueFrom' => $this->getValueFrom(),
            'valueTo' => $this->getValueTo(),
            'publishedFrom' => $this->getPublishedFrom()?->format(DATE_ATOM),
            'publishedTo' => $this->getPublishedTo()?->format(DATE_ATOM),
            'deadlineFrom' => $this->getDeadlineFrom()?->format(DATE_ATOM),
            'deadlineTo' => $this->getDeadlineTo()?->format(DATE_ATOM),
            'approachMarketFrom' => $this->getApproachMarketFrom()?->format(DATE_ATOM),
            'approachMarketTo' => $this->getApproachMarketTo()?->format(DATE_ATOM),
            'awardedFrom' => $this->getAwardedFrom()?->format(DATE_ATOM),
            'awardedTo' => $this->getAwardedTo()?->format(DATE_ATOM),
            'isSubcontract' => $this->getIsSubcontract(),
            'suitableForSme' => $this->getSuitableForSme(),
            'suitableForVco' => $this->getSuitableForVco(),
            'awardedToSme' => $this->getAwardedToSme(),
            'awardedToVcse' => $this->getAwardedToVcse(),
            'cpvCodes' => $this->getCpvCodes(),
        ];
    }

    public function getTypes(): array
    {
        return $this->types;
    }

    public function getStatuses(): array
    {
        return $this->statuses;
    }

    public function getKeyword(): ?string
    {
        return $this->keyword;
    }

    public function getQueryString(): ?string
    {
        return $this->queryString;
    }

    public function getRegions(): ?string
    {
        return $this->regions;
    }

    public function getPostcode(): ?string
    {
        return $this->postcode;
    }

    public function getRadius(): ?float
    {
        return $this->radius;
    }

    public function getValueFrom(): ?float
    {
        return $this->valueFrom;
    }

    public function getValueTo(): ?float
    {
        return $this->valueTo;
    }

    public function getPublishedFrom(): ?Carbon
    {
        return $this->publishedFrom;
    }

    public function getPublishedTo(): ?Carbon
    {
        return $this->publishedTo;
    }

    public function getDeadlineFrom(): ?Carbon
    {
        return $this->deadlineFrom;
    }

    public function getDeadlineTo(): ?Carbon
    {
        return $this->deadlineTo;
    }

    public function getApproachMarketFrom(): ?Carbon
    {
        return $this->approachMarketFrom;
    }

    public function getApproachMarketTo(): ?Carbon
    {
        return $this->approachMarketTo;
    }

    public function getAwardedFrom(): ?Carbon
    {
        return $this->awardedFrom;
    }

    public function getAwardedTo(): ?Carbon
    {
        return $this->awardedTo;
    }

    public function getIsSubcontract(): ?bool
    {
        return $this->isSubcontract;
    }

    public function getSuitableForSme(): ?bool
    {
        return $this->suitableForSme;
    }

    public function getSuitableForVco(): ?bool
    {
        return $this->suitableForVco;
    }

    public function getAwardedToSme(): ?bool
    {
        return $this->awardedToSme;
    }

    public function getAwardedToVcse(): ?bool
    {
        return $this->awardedToVcse;
    }

    public function getCpvCodes(): ?array
    {
        return $this->cpvCodes;
    }

    private static function createDateFromFormat(?string $date, string $format = 'd-m-Y'): ?Carbon
    {
        if (empty($date)) {
            return null;
        }

        try {
            return Carbon::createFromFormat($format, $date);
        } catch (\Exception $e) {
            return null;
        }
    }

}
