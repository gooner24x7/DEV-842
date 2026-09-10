<?php
declare(strict_types=1);

namespace App\Repository;

use App\Dto\Review\ReviewDto;
use App\Dto\Review\SearchParamsDto;
use App\Models\Reviews\Review;
use App\Models\Reviews\ReviewAnswer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReviewRepository
{
    const int ITEMS_PER_PAGE = 10;
    const string DEFAULT_ORDER_FIELD_NAME = 'id';

    public function __construct()
    {

    }

    public function find(SearchParamsDto $searchParamsDto, User $user): Collection
    {
        $companyUserIds = $user->getCompanyUsers();

        $query = Review::query()
            ->whereIn('user_id', $companyUserIds)
            ->with('template.sections.questions.options');

        $query->orderBy(
            ($searchParamsDto->getOrderBy() ?? self::DEFAULT_ORDER_FIELD_NAME),
            $searchParamsDto->getOrderSortDesc() ? 'desc' : 'asc'
        );

        return $query->get();
    }

    public function get(int $id, User $user) :?Review
    {
        $companyUserIds = $user->getCompanyUsers();

        $review = Review::query()
            ->select([
                'reviews.*',
                'users.first_name as user_name',
                'projects.name as project_name',
                'works_packages.name as works_package_name',
                'u2.first_name as quote_user_name',
            ])
            ->join('users', 'users.id', '=', 'reviews.user_id')
            ->join('supply_fit_enquiry_quotes', 'supply_fit_enquiry_quotes.id', '=', 'reviews.quote_id')
            ->join('supply_fit_enquiries', 'supply_fit_enquiries.id', '=', 'supply_fit_enquiry_quotes.enquiry_id')
            ->join('projects', 'projects.id', '=', 'supply_fit_enquiries.project_id')
            ->join('works_packages', 'works_packages.id', '=', 'supply_fit_enquiries.works_package_id')
            ->join('users as u2', 'u2.id', '=', 'supply_fit_enquiry_quotes.user_id')
            ->where('reviews.id', '=', $id)
            ->whereIn('reviews.user_id', $companyUserIds)
            ->first();

        if (!$review) {
            return null;
        }

        return $review->load(['template.sections.questions.options', 'answers']);
    }

    /**
     * @throws \Throwable
     */
    public function create(ReviewDto $dto, User $user): ?Review
    {
        $review = Review::create([
            'user_id' => $user->getId(),
            'quote_id' => $dto->getQuoteId(),
            'template_id' => $dto->getTemplateId(),
            'comment' => $dto->getComment(),
            'status' => 1,
        ]);

        return $review->load('template.sections.questions.options');
    }

    public function update(ReviewDto $dto, int $id, User $user): ?Review
    {
        /** @var Review $review */
        $review = Review::find($id);

        if (!$review) {
            return null;
        }

        $review->user_id = $user->getId();
        $review->status = 2;
        $review->comment = $dto->getComment();
        $review->completed_at = Carbon::now()->format('Y-m-d H:i:s');

        $review->save();

        foreach($dto->getSections() as $section) {
            foreach ($section['questions'] as $question) {
                ReviewAnswer::create([
                    'review_id' => $review->getId(),
                    'question_id' => $question['id'],
                    'option_id' => $question['selected_option_id'],
                    'comment' => $question['comment'] ?? null
                ]);
            }
        }

        return $review;
    }

    public function delete(int $id): ?bool
    {
        /** @var Review $review */
        $review = Review::find($id);

        if (!$review) {
            return null;
        }

        return $review->delete();
    }
}
