<?php
declare(strict_types=1);

namespace App\Repository;

use App\Dto\Questionnaire\TemplateDto;
use App\Dto\Questionnaire\TemplateItemDto;
use App\Dto\SearchParamsDto;
use App\Exceptions\NotFoundException;
use App\Models\Questionnaire\Questionnaire;
use App\Models\Questionnaire\Template;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class QuestionnaireTemplateRepository
{
    public function find(SearchParamsDto $searchParamsDto, int $authorId): LengthAwarePaginator
    {
        $query = Template::query();
        $query->where([
            'user_id' => $authorId,
        ]);

        $query = $query->orderBy(
            $searchParamsDto->getOrderBy(),
            $searchParamsDto->getOrderSortDesc() ? 'desc' : 'asc'
        );

        $itemsPerPage = $searchParamsDto->getItemsPerPage() ?? 20;
        if (!$searchParamsDto->isPaginationEnabled()) {
            $itemsPerPage = PHP_INT_MAX;
        }

        return $query->paginate($itemsPerPage);
    }

    /**
     * @throws NotFoundException
     */
    public function delete(int $id): bool
    {
        $template = $this->get($id);
        if ($template === null) {
            throw new NotFoundException();
        }

        return (bool)$template->delete();
    }

    public function get(int $id): ?Template
    {
        return Template::where(['id' => $id])->first();
    }

    /**
     * @throws NotFoundException
     */
    public function update(int $id, TemplateDto $dto, int $userId): Template
    {
        $template = Template::where(['id' => $id])->first();
        if (!$template) {
            throw new NotFoundException();
        }

        $template->questions = json_encode(array_map(function (TemplateItemDto $item) {
            return $item->toTemplateArray();
        }, $dto->getQuestions()));
        $template->user_id = $userId;
        $template->save();

        return $template;
    }

    public function newTemplate(string $name, TemplateDto $dto, int $userId): Template
    {
        $template = new Template();
        $template->name = $name;

        $template->questions = json_encode(array_map(function (TemplateItemDto $item) {
            return $item->toTemplateArray();
        }, $dto->getQuestions()));
        $template->user_id = $userId;
        $template->save();

        return $template;
    }

    public function getOptions(string $search, User $user): Collection
    {
        $query = Template::where(['user_id' => $user->getId()]);
        if (!empty($search)) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        return $query->get();
    }

    /**
     * @throws NotFoundException
     */
    public function storeInWorksPackage(int $templateId, int $projectId, int $userId, int $worksPackageId): void
    {
        /** @var Template $template */
        $template = Template::where(['id' => $templateId])->first();
        if (!$template) {
            throw new NotFoundException('Template not found');
        }

        $storedQuestions = json_decode($template->questions, true);
        array_map(function ($question) use ($projectId, $userId, $worksPackageId) {
            return Questionnaire::create(array_merge($question, [
                'user_id' => $userId,
                'works_package_id' => $worksPackageId,
            ]));
        }, $storedQuestions);
    }

    /**
     * @param string $name
     * @param Collection $items
     * @param int $userId
     * @return Template
     */
    public function create(string $name, Collection $items, int $userId): Template
    {
        return Template::create([
            'name' => $name,
            'user_id' => $userId,
            'questions' => json_encode($items->map(function (Questionnaire $item) {
                return $item->toTemplateArray();
            })),
        ]);
    }
}
