<?php
declare(strict_types=1);

namespace App\Repository;

use App\Dto\Answer\AnswerDto;
use App\Dto\Answer\SearchParamsDto;
use App\Models\Answer;
use App\Models\Message;
use App\Models\Question;
use App\Models\Role;
use App\Models\User;
use App\Service\UserService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Output\NullOutput;

class AnswerRepository
{
    public const string DEFAULT_ORDER_FIELD_NAME = 'id';
    public const int ITEMS_PER_PAGE = 20;

    private \Redis $redis;
    private UserRepository $userRepository;
    private UserService $userService;

    public function __construct(
        \Redis $redis,
        UserRepository $userRepository,
        UserService $userService
    ) {
        $this->redis = $redis;
        $this->userRepository = $userRepository;
        $this->userService = $userService;
    }

    public function find(SearchParamsDto $searchParamsDto, User $user, bool $isBranchUser, int $questionId): LengthAwarePaginator
    {
        $companyUserIds = $user->getCompanyUsers();

        $query = Answer::query()
            ->select([
                'answers.id',
                'answers.price',
                'answers.local_material_spend',
                'answers.comment',
                'answers.offers',
                'answers.quote_accepted_at',
                'answers.checked_at',
                'answers.type',
                'answers.viewed_at',
                'answers.supplier_invoice_no',
                'answers.user_id',
                'answers.has_substitution',
                'users.first_name',
                'users.last_name'
            ])
            ->selectRaw('(select (count(*)>0) from users_preferred_suppliers where supplier_id=answers.user_id and (user_id=questions.user_id or user_id = (select billing_user_id from users where id = questions.user_id))) is_preferred_supplier')
            ->selectRaw('DATE_FORMAT(answers.`created_at`, "%Y-%m-%dT%H:%i:%S.000000Z") created_at')
            ->where([
                'answers.question_id' => $questionId,
            ]);

        $query->join('questions', 'questions.id', '=', 'answers.question_id');
        $query->join('users', 'users.id', '=', 'answers.user_id');

        $question = Question::where(['id' => $questionId])->first();
        $query->selectRadius($question);

        if (!empty($searchParamsDto->getRadius())) {
            $query->where(static function ($query) use ($searchParamsDto, $question) {
                return $query->inRadius($question->getLat() ?? 0, $question->getLong() ?? 0, $searchParamsDto->getRadius());
            });
        }

        if ($user->hasRole(Role::ROLE_COMPANY_SLUG)) {
            $query->where(function ($query) use ($user, $companyUserIds) {
                $query->whereIn('answers.user_id', $companyUserIds)
                    ->orWhereRaw(
                        '(select qau.id from questions_assigned_users qau
                        join users u1 on u1.id=qau.user_id
                        where qau.user_id=? and qau.question_id=questions.id and u1.billing_user_id=(select u2.billing_user_id from users u2 where u2.id=answers.user_id limit 1))',
                        [$user->getId()]
                    );
            });
        } else if (!$user->hasRole(Role::ROLE_ADMIN_SLUG)) {
            $query->where(function ($query) use ($user, $companyUserIds) {
                $query->whereIn('answers.user_id', $companyUserIds)
                ->orWhereIn('questions.user_id', $companyUserIds);
            });
        }

        $orderBy = (!$searchParamsDto->getOrderBy() || $searchParamsDto->getOrderBy() === 'id') ? self::DEFAULT_ORDER_FIELD_NAME : $searchParamsDto->getOrderBy();
        $sortDir = $searchParamsDto->getOrderSortDesc() ? 'desc' : 'asc';

        //$query = $query->orderBy($orderBy, $sortDir);

        $itemsPerPage = $searchParamsDto->getItemsPerPage() ?? self::ITEMS_PER_PAGE;
        if (!$searchParamsDto->isPaginationEnabled()) {
            $itemsPerPage = PHP_INT_MAX;
        }

        $newAnswerIndicatorKey = sprintf(Answer::NEW_ANSWER_TO_ENQUIRY_INDICATOR_CACHE, $questionId, $user->getId());
        $this->redis->set($newAnswerIndicatorKey, Carbon::now()->format('Y-m-d H:i:s'));

        $answers = $query->get();
        $answers = $this->populateWithIndicator($answers, $user, $question);
        $answers = $this->calculateQuoteScores($answers);

        $total = count($answers);
        $currentPage = $searchParamsDto->getPage() ?? 1;

        usort($answers, function ($a, $b) use ($orderBy, $sortDir) {
            return $sortDir === 'asc' ? $a[$orderBy] > $b[$orderBy] : $a[$orderBy] < $b[$orderBy];
        });

        $currentItems = array_slice($answers, $itemsPerPage * ($currentPage - 1), $itemsPerPage);

        return new LengthAwarePaginator($currentItems, $total, $itemsPerPage, $currentPage);
    }

    private function populateWithIndicator(Collection $answers, User $user, Question $question): Collection
    {
        $userRepository = $this->userRepository;

        $answers->transform(function (Answer $answer) use ($user, $userRepository, $question) {
            $newMsgIndicatorKey = sprintf(Message::NEW_MESSAGE_INDICATOR_CACHE, $answer->getId(), $user->getId());

            $qty = Message::query()
                ->where('answer_id', '=', $answer->getId())
                ->where('user_id', '<>', $user->getId())
                ->where('created_at', '>', $this->redis->get($newMsgIndicatorKey))
                ->count('id');

            $answer->setNewMsgQty($qty);

            $time_diff = $question->created_at->diffInMinutes($answer->created_at);
            $answer->setTimeDifference($time_diff);

            $user = $userRepository->getUserById($answer->getUserId());
            if ($user && $user->getBillingUserId()) {
                $billingUser = $userRepository->getUserById($user->getBillingUserId());

                if ($billingUser->credit_application_form_url) {
                    $answer->setCreditApplicationFormUrl($billingUser->credit_application_form_url);
                }
            }

            $currentUser = $this->userService->getCurrentUser();

            if ($question->user_id === $currentUser->id) {
                $answer->viewed_at = Carbon::now();
                $answer->save();
            }

            return $answer;
        });

        return $answers;
    }

    private function calculateQuoteScores(Collection $answers) : array
    {
        $answersList = $answers->toArray();

        $answersListFull = [];
        $answersListPart = [];
        $userIds = [];

        foreach ($answersList as $answer) {
            if ($answer['type'] === 'full' && !in_array($answer['user_id'], $userIds)) {
                $answersListFull[] = $answer;
                $userIds[] = $answer['user_id'];
            } else {
                $answersListPart[] = $answer;
            }
        }

        $answersTotal = count($answersListFull);
        $loopCount = $answersTotal < 3 ? $answersTotal : 3;

        $price_scores = [22, 12, 7];
        $esg_scores = [20, 10, 5];

        // sort by price asc
        usort($answersListFull, function ($a, $b) {
            return $a['price'] > $b['price'];
        });

        // set price scores
        for ($i = 0; $i < $loopCount; $i++) {
            $answersListFull[$i]['price_score'] = $price_scores[$i];
        }

        // sort by esg perc desc
        usort($answersListFull, function ($a, $b) {
            return ($a['esgPerc'] ?? 0) < ($b['esgPerc'] ?? 0);
        });

        // set price scores
        for ($i = 0; $i < $loopCount; $i++) {
            $answersListFull[$i]['esg_score'] = $esg_scores[$i];
        }

        // sort by time difference
        usort($answersListFull, function ($a, $b) {
            return $a['time_difference'] > $b['time_difference'];
        });

        // set time scores
        for ($i = 0; $i < $loopCount; $i++) {
            $answersListFull[$i]['time_score'] = $esg_scores[$i];
        }

        $answersList = array_merge($answersListFull, $answersListPart);

        // set total score
        foreach ($answersList as $key => $value) {
            $value['total_score'] = $value['price_score'] + $value['esg_score'] + $value['time_score'];
            $answersList[$key] = $value;
        }

        return $answersList;
    }

    public function store(AnswerDto $answerDto, Question $question, User $user): ?Answer
    {
        $answer = Answer::create([
            'price' => $answerDto->getPrice(),
            'comment' => $answerDto->getComment(),
            'offers' => $answerDto->getOffers(),
            'user_id' => $user->getId(),
            'question_id' => $question->getId(),
            'type' => $answerDto->getType(),
            'has_substitution' => $answerDto->getHasSubstitution(),
        ]);

        if ($answerDto->getUploadedFile()) {
            foreach ($answerDto->getUploadedFile() as $attachment) {
                $answer->attach($attachment);
            }

            try {
                Artisan::call('import:pdf', ['quotes' => [(string)$answer->id]], new NullOutput());
            } catch (\Exception $e) {
                Log::error($e->getMessage());
            }
        }

        return $answer;
    }

    public function update(AnswerDto $answerDto, int $id): ?Answer
    {
        $answer = $this->getById($id);
        if (!$answer) {
            return null;
        }

        $answer->setPrice($answerDto->getPrice());
        $answer->setComment($answerDto->getComment());
        $answer->setOffers($answerDto->getOffers());
        $answer->setType($answerDto->getType());
        $answer->setHasSubstitution($answerDto->getHasSubstitution());
        $answer->save();

        if ($answerDto->getUploadedFile()) {
            foreach ($answerDto->getUploadedFile() as $attachment) {
                $answer->attach($attachment);
            }

            try {
                Artisan::call('import:pdf', ['quotes' => [(string)$answer->id]], new NullOutput());
            } catch (\Exception $e) {
                Log::error($e->getMessage());
            }
        }

        return $answer;
    }

    public function getById(int $id): ?Answer
    {
        return Answer::where(['id' => $id])->first();
    }

    public function accept(int $id): bool
    {
        $answer = $this->getById($id);
        if (!$answer) {
            return false;
        }
        $answer->quote_accepted_at = new Carbon();

        return $answer->save();
    }

    public function unaccept(int $id): bool
    {
        $answer = $this->getById($id);
        if (!$answer) {
            return false;
        }
        $answer->quote_accepted_at = null;

        return $answer->save();
    }

    public function setCheckedAt(Answer $answer, bool $checked): bool
    {
        if ($checked) {
            $answer->checked_at = new Carbon();
        } else {
            $answer->checked_at = null;
        }

        return $answer->save();
    }

    public function delete(int $id): bool
    {
        $answer = $this->getById($id);
        if (!$answer) {
            return false;
        }

        return $answer->delete();
    }

    public function storeSupplierInvoiceNo(string $supplierInvoiceNo, int $answerId): ?Answer
    {
        $answer = $this->getById($answerId);
        if (!$answer) {
            return null;
        }
        $answer->setSupplierInvoiceNo($supplierInvoiceNo);

        if ($answer->save()) {
            return $answer;
        }

        return null;
    }

    public function storeLocalMaterialSpend(?float $localMaterialSpend, int $answerId): ?Answer
    {
        $answer = $this->getById($answerId);
        if (!$answer) {
            return null;
        }
        $answer->setLocalMaterialSpend($localMaterialSpend);

        if ($answer->save()) {
            return $answer;
        }

        return null;
    }

    public function isLastAnswerOlderThanADay(int $questionId, int $excludeId): bool
    {
        $lastAnswer = Answer::where(['question_id' => $questionId])->where('id', '<>', $excludeId)->orderBy('id', 'desc')->first();
        if ($lastAnswer && $lastAnswer->created_at->diffInSeconds(Carbon::now()) < 3600 * 24) {
            return false;
        }

        return true;
    }

    public function getQuotesTotalForMerchant(User $user = null): int
    {
        $query = Answer::join('questions', 'questions.id', '=', 'answers.question_id')
            ->join('products', 'products.id', '=', 'questions.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->join('users', 'users.id', '=', 'questions.user_id')
            ->where('questions.id', '>', 25)
            ->where('users.is_test_account', '=', 0);

        if ($user) {
            $query->where('answers.user_id', '=', $user->getId());
        }

        return $query->count();
    }

    public function getQuotesTotalForContractor(User $user = null): int
    {
        $query = Answer::join('questions', 'questions.id', '=', 'answers.question_id')
            ->join('products', 'products.id', '=', 'questions.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->join('users', 'users.id', '=', 'questions.user_id')
            ->where('questions.id', '>', 25)
            ->where('users.is_test_account', '=', 0);

        if ($user) {
            $query->where(['answers.user_id' => $user->getId()]);
        }

        return $query->count();
    }

    public function getQuotesAcceptedForMerchant(User $user = null): int
    {
        $query = Answer::join('questions', 'questions.id', '=', 'answers.question_id')
            ->join('users', 'users.id', '=', 'questions.user_id')
            ->where('questions.id', '>', 25)
            ->where('users.is_test_account', '=', 0)
            ->where('answers.quote_accepted_at', '!=', null);

        if ($user) {
            $query->where(['answers.user_id' => $user->getId()]);
        }

        return $query->count();
    }

    public function getQuotesAcceptedForContractor(User $user = null): int
    {
        $query = Answer::join('questions', 'questions.id', '=', 'answers.question_id')
            ->join('users', 'users.id', '=', 'questions.user_id')
            ->where('questions.id', '>', 25)
            ->where('users.is_test_account', '=', 0)
            ->where('answers.quote_accepted_at', '!=', null);

        if ($user) {
            $query->where(['questions.user_id' => $user->getId()]);
        }

        return $query->count();
    }

    public function getQuotesForInquiryCount(int $questionId): int
    {
        return Answer::where(['question_id' => $questionId])->count();
    }

    public function getQuotesToProcessActivityTracker($limit = 10)
    {
        return Answer::leftJoin(
            DB::raw('(select id as processed_id, quote_id from activity_tracker_processed_quotes
where activity_tracker_processed_quotes.inquiry_type=\'' . addslashes(Answer::class) . '\') as a'),
            'a.quote_id', '=', 'answers.id'
        )
            ->whereRaw('(select coalesce(activity_tracker_mapping, \'\') from users where id=answers.user_id limit 1) != \'\'')
            ->whereRaw('(select attachments.id from attachments where model_id=answers.id and model_type=? limit 1) is not null', [Answer::class])
            ->whereNull('a.processed_id')
            ->limit($limit)->get();
    }

    public function getQuotesByIds(array $ids)
    {
        return Answer::whereIn('id', $ids)->get();
    }
}
