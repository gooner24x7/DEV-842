<?php
declare(strict_types=1);

namespace App\Repository;

use App\Dto\ReviewTemplate\ReviewTemplateDto;
use App\Dto\ReviewTemplate\SearchParamsDto;
use App\Models\Reviews\ReviewTemplate;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReviewTemplateRepository
{
    const int ITEMS_PER_PAGE = 10;
    const string DEFAULT_ORDER_FIELD_NAME = 'id';

    public function __construct()
    {

    }

    public function find(SearchParamsDto $searchParamsDto, User $user): Collection
    {
        $companyUserIds = $user->getCompanyUsers();

        $query = ReviewTemplate::query()
            ->whereIn('user_id', $companyUserIds)
            ->with('sections.questions.options');

        $query->orderBy(
            ($searchParamsDto->getOrderBy() ?? self::DEFAULT_ORDER_FIELD_NAME),
            $searchParamsDto->getOrderSortDesc() ? 'desc' : 'asc'
        );

        return $query->get();
    }

    public function get(int $id, User $user) :?ReviewTemplate
    {
        $companyUserIds = $user->getCompanyUsers();

        $template = ReviewTemplate::query()
            ->where('id', '=', $id)
            ->whereIn('user_id', $companyUserIds)
            ->first();

        if (!$template) {
            return null;
        }

        return $template->load('sections.questions.options');
    }

    /**
     * @throws \Throwable
     */
    public function create(ReviewTemplateDto $dto, User $user): ?ReviewTemplate
    {
        return DB::transaction(function () use ($dto, $user) {
            $template = ReviewTemplate::create([
                'user_id' => $user->getId(),
                'name' => $dto->getName(),
                'description' => $dto->getDescription(),
            ]);

            $this->createSections($template, $dto->getSections());

            return $template->load('sections.questions.options');
        });
    }

    public function createSections(ReviewTemplate $template, array $sections): void
    {
        foreach ($sections as $sectionData) {
            $section = $template->sections()->create([
                'title' => $sectionData['title'],
            ]);

            foreach ($sectionData['questions'] as $questionData) {
                $question = $section->questions()->create([
                    'question' => $questionData['text'],
                ]);

                foreach ($questionData['options'] as $optionData) {
                    $question->options()->create([
                        'text' => $optionData['text'],
                        'score' => $optionData['score'],
                    ]);
                }
            }
        }
    }

    public function update(ReviewTemplateDto $dto, int $id): ?ReviewTemplate
    {
        /** @var ReviewTemplate $template */
        $template = ReviewTemplate::find($id);

        if (!$template) {
            return null;
        }

        $template->name = $dto->getName();
        $template->description = $dto->getDescription();
        $template->save();

        $template->sections()->delete();
        $this->createSections($template, $dto->getSections());

        return $template;
    }

    public function delete(int $id): ?bool
    {
        /** @var ReviewTemplate $template */
        $template = ReviewTemplate::find($id);

        if (!$template) {
            return null;
        }

        return $template->delete();
    }

    public function getOptions(User $user): Collection
    {
        $companyUserIds = $user->getCompanyUsers();

        $query = ReviewTemplate::query();
        $query->select('id', 'name');
        $query->whereIn('user_id', $companyUserIds);

        return $query->get();
    }
}
