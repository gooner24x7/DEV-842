<?php
declare(strict_types=1);

namespace App\Repository;

use App\Dto\Reports\TotalEnquiryItemDto;
use App\Models\Answer;
use App\Models\Certificate;
use App\Models\LogisticsEnquiry;
use App\Models\LogisticsQuote;
use App\Models\PermissionsReference;
use App\Models\Question;
use App\Models\Reviews\Review;
use App\Models\Role;
use App\Models\SupplyFitEnquiry;
use App\Models\SupplyFitEnquiryQuote;
use App\Models\Questionnaire\Project;
use App\Models\Questionnaire\WorksPackage;
use App\Models\User;
use App\Models\UserCreditsafe;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
class ReportRepository
{
    private MessageRepository $messageRepository;

    public function __construct(MessageRepository $messageRepository)
    {
        $this->messageRepository = $messageRepository;
    }

    /**
     * @return array []TotalEnquiryItemDto
     */
    public function getTotalEnquiries(int $branchId): array
    {
        $user = User::where(['id' => $branchId])->first();
        if (!$user) {
            return [];
        }

        $query = Question::selectRaw(
            "DATE_FORMAT(questions.created_at, '%m\/%Y') month, count(questions.id) total"
        )->whereRaw('questions.created_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)')
            ->groupBy(DB::raw("DATE_FORMAT(questions.created_at, '%m\/%Y')"))
            ->orderBy(DB::raw("DATE_FORMAT(questions.created_at, '%m\/%Y')"), 'asc');
        $query->whereNotNull('questions.lat');

        if (!$user->is_global) {
            $query = $query->inRadius($user->lat ?? 0, $user->long ?? 0, QuestionRepository::SEARCH_RADIUS, false);
        }

        $query->whereIn('questions.product_id', $user->product_ids);

        $rows = collect($query->get()->toArray())->all();

        $result = [];
        foreach ($rows as $row) {
            $result[] = TotalEnquiryItemDto::createFromArray($row);
        }

        return $result;
    }

    /**
     * @return array []TotalEnquiryItemDto
     */
    public function getTotalQuotes(int $branchId): array
    {
        $user = User::where(['id' => $branchId])->first();
        if (!$user) {
            return [];
        }

        $query = Answer::selectRaw(
            "DATE_FORMAT(answers.created_at, '%m\/%Y') month, count(answers.id) total"
        )->whereRaw('answers.created_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)')
            ->where(['user_id' => $user->id])
            ->groupBy(DB::raw("DATE_FORMAT(answers.created_at, '%m\/%Y')"))
            ->orderBy(DB::raw("DATE_FORMAT(answers.created_at, '%m\/%Y')"), 'asc');

        $rows = collect($query->get()->toArray())->all();

        $result = [];
        foreach ($rows as $row) {
            $result[] = TotalEnquiryItemDto::createFromArray($row);
        }

        return $result;
    }

    public function getEnquiriesToTimeContract(User $user = null): array
    {
        if (!$user) {
            return DB::select('select a.qty answersQty, count(a.id) questionsQty from
(select questions.id, count(answers.id) qty
from questions
join products on products.id=questions.product_id
join categories on categories.id=products.category_id
left join answers on questions.id = answers.question_id
group by questions.id) a
group by a.qty
order by a.qty asc');
        }

        return DB::select('select a.qty answersQty, count(a.id) questionsQty from
(select questions.id, count(answers.id) qty
from questions
join products on products.id=questions.product_id
join categories on categories.id=products.category_id
left join answers on questions.id = answers.question_id
where questions.user_id=:id
group by questions.id) a
group by a.qty
order by a.qty asc', [
            'id' => $user->getId()
        ]);
    }

    public function getEnquiriesToTimeMerchant(User $user = null): array
    {
        if (!$user) {
            return DB::select('select a.time_diff, count(a.time_diff) qty from (
select MINUTE(TIMEDIFF(questions.created_at, answers.created_at)) as time_diff
from questions
join products on products.id=questions.product_id
join categories on categories.id=products.category_id
left join answers on questions.id = answers.question_id
where answers.quote_accepted_at is not null) a
group by a.time_diff order by a.time_diff asc');
        }

        return DB::select('select a.time_diff, count(a.time_diff) qty from (
select MINUTE(TIMEDIFF(questions.created_at, answers.created_at)) as time_diff
from questions
join products on products.id=questions.product_id
join categories on categories.id=products.category_id
left join answers on questions.id = answers.question_id
where answers.user_id=:id and answers.quote_accepted_at is not null) a
group by a.time_diff order by a.time_diff asc', [
            'id' => $user->getId()
        ]);
    }

    public function getSupplierReport(User $user): array
    {
        if (!$user->isBillingUser()) {
            return $this->getSupplierReportForUser($user);
        }

        $result = $this->getSupplierReportForUser($user);

        $users = User::where(['billing_user_id' => $user->getId()])->get();
        /** @var User $item */
        foreach ($users as $item) {
            if ($item->can(PermissionsReference::answerQuestion)) {
                $response = $this->getSupplierReportForUser($item);
                $result['items'] = array_merge($result['items'], $response['items']);
            }
        }

        return $result;
    }

    public function getSupplierReportForUser(User $user): array
    {
        $query = Question::query()
            ->join('products', 'products.id', '=', 'questions.product_id')
            ->join('users', 'users.id', '=', 'questions.user_id')
            ->select(['questions.id', 'questions.user_id', 'questions.created_at', 'questions.days', 'questions.postcode', 'products.name as product_name', 'users.first_name as company_name']);

        if (!$user->isBillingUser()) {
            $query->whereIn('questions.product_id', $user->getProductIdsAttribute());
            if (!$user->getIsGlobal()) {
                $query->inRadius($user->getLat() ?? 0, $user->getLong() ?? 0, QuestionRepository::SEARCH_RADIUS);
            }
        }

        $query->selectRaw('(select a.id from answers a where a.user_id=? and a.question_id=questions.id limit 1) as quoteId', [
            $user->getId()
        ]);

        if ($user->isBillingUser()) {
            $query->whereRaw('(select a.id from answers a where a.user_id=? and a.question_id=questions.id limit 1) is not null', [
                $user->getId()
            ]);
        }

        $query->where('questions.id', '>', 25);
        $query->where('users.is_test_account', '=', 0);
        $query->orderBy('id', 'asc');

        $items = $query->get()->toArray();
        $first = 0;

        return [
            'items' => array_map(function ($item) use ($user, &$first) {
                /** @var Answer|null $answer */
                $answer = null;
                $quoteCarbon = null;
                if ($item['quoteId'] ?? false) {
                    $answer = Answer::where(['id' => $item['quoteId']])->first();
                    $quoteCarbon = new Carbon($answer->created_at);
                }
                $enquiryCarbon = new Carbon($item['created_at']);

                $lastChatDate = $this->messageRepository->lastChatDate((int)$item['id'], (int)$user->id, (int)$item['user_id'], $item['quoteId']);

                try {
                    if (is_numeric($item['days'])) {
                        $dueDateCarbon = new Carbon($item['created_at']);
                        $dueDateCarbon->addDays($item['days']);
                    } else {
                        $dueDateCarbon = new Carbon($item['days']);
                    }

                    $enquiryDue = $dueDateCarbon->format('Y-m-d');
                    $minUntilDeadline = $dueDateCarbon->diffInMinutes(new Carbon());
                    $timeDifference = $quoteCarbon ? $dueDateCarbon->diffInMinutes($quoteCarbon) : '';
                } catch (\Exception $e) {
                    $enquiryDue = 'invalid date';
                    $minUntilDeadline = 'invalid date';
                    $timeDifference = 'invalid date';
                }

                return [
                    'Account Name' => ($first === 0) ? $user->getFirstName() : '',
                    'Post Code' => ($first++ === 0) ? $user->getPostcode() : '',
                    'Company Name' => $item['company_name'] ?? '',
                    'ID' => $item['id'] ?? '',
                    'Category' => $item['product_name'] ?? '',
                    'Site Post Code' => $item['postcode'] ?? '',
                    'Enquiry Created' => $item['created_at'] ?? '',
                    'Enquiry Due' => $enquiryDue,
                    'Quoted Yes/No' => $item['quoteId'] ? 'Yes' : 'No',
                    'Price quoted' => $answer ? $answer->price : '',
                    'Quote Date' => $answer->created_at ?? '',
                    'Min until Deadline' => $minUntilDeadline,
                    'Min to Quote' => ($quoteCarbon && $enquiryCarbon) ? $quoteCarbon->diffInMinutes($enquiryCarbon) : '',
                    'Time Difference' => $timeDifference,
                    'Last Chat' => $lastChatDate ? $lastChatDate->format('Y-m-d') : '',
                    'Min Price Quoted' => $this->getMinPriceQuoted($item['id']),
                    'Max Price Quoted' => $this->getMaxPriceQuoted($item['id']),
                    'Min Time' => $this->getQuoteMinTime($item['id'], $enquiryCarbon),
                    'Max Time' => $this->getQuoteMaxTime($item['id'], $enquiryCarbon),
                ];
            }, $items)
        ];
    }

    public function getLogisticsReport(User $user): array
    {
        $query = LogisticsEnquiry::query()
            ->select([
                'logistics_enquiries.*',
                'users.first_name as company_name'
            ])
            ->selectRaw('(select q.id from logistics_quotes q where q.user_id=? and q.enquiry_id=logistics_enquiries.id limit 1) as quoteId', [$user->getId()])
            ->join('users', 'users.id', '=', 'logistics_enquiries.user_id');

        $query->orderBy('id', 'asc');

        $items = $query->get()->toArray();
        $first = 0;

        $result = [
            'items' => array_map(function ($item) use ($user, &$first) {
                /** @var LogisticsQuote|null $quote */
                $quote = null;
                $quoteCarbon = null;

                if (!empty($item['quoteId'])) {
                    $quote = LogisticsQuote::where(['id' => $item['quoteId']])->first();
                    $quoteCarbon = new Carbon($quote->created_at);
                }

                $enquiryCarbon = new Carbon($item['created_at']);
                $collectDate = new Carbon($item['collect_date']);
                $deliveryDate = new Carbon($item['delivery_date']);

                $lastChatDate = $this->messageRepository->lastChatDate((int)$item['id'], $user->id, (int)$item['user_id'], $item['quoteId'], 1);

                try {
                    $now = Carbon::now();
                    $minUntilCollect = $collectDate ? $now->diffInMinutes($collectDate, false) : '';
                    $minUntilDelivery = $deliveryDate ? $now->diffInMinutes($deliveryDate, false) : '';
                    $collectTimeDiff = ($quoteCarbon && $collectDate) ? $collectDate->diffInMinutes($quoteCarbon) : '';
                    $deliveryTimeDiff = ($quoteCarbon && $deliveryDate) ? $deliveryDate->diffInMinutes($quoteCarbon) : '';
                } catch (\Exception $e) {
                    $minUntilCollect = 'invalid date';
                    $minUntilDelivery = 'invalid date';
                    $collectTimeDiff = 'invalid date';
                    $deliveryTimeDiff = 'invalid date';
                }

                return [
                    'Account Name' => ($first === 0) ? $user->getFirstName() : '',
                    'Post Code' => ($first++ === 0) ? $user->getPostcode() : '',
                    'Company Name' => $item['company_name'] ?? '',
                    'ID' => $item['id'] ?? '',
                    'Category' => $item['product_name'] ?? '',
                    'Collect Post Code' => $item['contacts'][0]['postcode'] ?? '',
                    'Delivery Post Code' => $item['contacts'][1]['postcode'] ?? '',
                    'Enquiry Created' => $item['created_at'] ?? '',
                    'Collect Date' => $item['collect_date'] ?? '',
                    'Delivery Date' => $item['delivery_date'] ?? '',
                    'Quoted Yes/No' => $item['quoteId'] ? 'Yes' : 'No',
                    'Price quoted' => $quote ? $quote->price : '',
                    'Quote Date' => $quote->created_at ?? '',
                    'Min until Collect' => $minUntilCollect,
                    'Min until Delivery' => $minUntilDelivery,
                    'Min to Quote' => ($quoteCarbon && $enquiryCarbon) ? $quoteCarbon->diffInMinutes($enquiryCarbon) : '',
                    'Collect Time Difference' => $collectTimeDiff,
                    'Delivery Time Difference' => $deliveryTimeDiff,
                    'Last Chat' => $lastChatDate ? $lastChatDate->format('Y-m-d') : '',
                    'Min Price Quoted' => $this->getMinPriceQuoted($item['id'], 1),
                    'Max Price Quoted' => $this->getMaxPriceQuoted($item['id'], 1),
                    'Min Time' => $this->getQuoteMinTime($item['id'], $enquiryCarbon, 1),
                    'Max Time' => $this->getQuoteMaxTime($item['id'], $enquiryCarbon, 1),
                ];
            }, $items)
        ];

        return $result;
    }

    private function getMinPriceQuoted(int $id, int $type = 0)
    {
        $table = 'answers';
        $whereColumn = 'question_id';

        if ($type === 1) {
            $table = 'logistics_quotes';
            $whereColumn = 'enquiry_id';
        }

        $quotes = DB::table($table)
            ->selectRaw('MIN(price) as min_price')
            ->where($whereColumn, '=', $id)
            ->get();

        return $quotes[0]->min_price ?? '';
    }

    private function getMaxPriceQuoted(int $id, int $type = 0)
    {
        $table = 'answers';
        $whereColumn = 'question_id';

        if ($type === 1) {
            $table = 'logistics_quotes';
            $whereColumn = 'enquiry_id';
        }

        $quotes = DB::table($table)
            ->selectRaw('MAX(price) as max_price')
            ->where($whereColumn, '=', $id)
            ->get();

        return $quotes[0]->max_price ?? '';
    }

    private function getQuoteMinTime(int $id, $enquiry_created, int $type = 0): int|string
    {
        $table = 'answers';
        $whereColumn = 'question_id';

        if ($type === 1) {
            $table = 'logistics_quotes';
            $whereColumn = 'enquiry_id';
        }

        $quotes = DB::table($table)
            ->selectRaw('MIN(created_at) as min_created')
            ->where($whereColumn, '=', $id)
            ->get();

        if (empty($quotes[0]->min_created)) {
            return '';
        }

        $quote_created = new Carbon($quotes[0]->min_created);

        return $quote_created->diffInMinutes($enquiry_created);
    }

    private function getQuoteMaxTime(int $id, $enquiry_created, int $type = 0): int|string
    {
        $table = 'answers';
        $whereColumn = 'question_id';

        if ($type === 1) {
            $table = 'logistics_quotes';
            $whereColumn = 'enquiry_id';
        }

        $quotes = DB::table($table)
            ->selectRaw('MAX(created_at) as max_created')
            ->where($whereColumn, '=', $id)
            ->get();

        if (empty($quotes[0]->max_created)) {
            return '';
        }

        $quote_created = new Carbon($quotes[0]->max_created);

        return $quote_created->diffInMinutes($enquiry_created);
    }

    public function getBuyerReport(User $user, int $projectId, int $worksPackageId): array
    {
        $projectName = Project::find($projectId)->getName();
        $worksPackageName = !empty($worksPackageId) ? WorksPackage::find($worksPackageId)->getName() : '';

        $query = Question::query()->select([
            'questions.id as question_id',
            'questions.user_id as question_user_id',
            'questions.created_at as question_created_at',
            'answers.id',
            'answers.user_id',
            'answers.price',
            'answers.created_at',
            'answers.quote_accepted_at',
            'roles.slug as role',
            'users.first_name'
        ]);

        $query->selectRaw('(select (count(*)>0) from users_preferred_suppliers where supplier_id=answers.user_id and (user_id=questions.user_id or user_id = (select billing_user_id from users where id = questions.user_id))) is_preferred_supplier');
        $query->selectRaw('(select count(*) from messages where answer_id = answers.id) messages');

        $query->selectRaw('coalesce((0.62137 * (6371 * acos(cos(radians(`questions`.`lat`)) * cos(radians(`users`.`lat`)) * cos(radians(`users`.`long`) - radians(`questions`.`long`)) + sin(radians(`questions`.`lat`)) * sin(radians(`users`.`lat`))))), 0) distance');
        $query->selectRaw('(select max(coalesce((0.62137 * (6371 * acos(cos(radians(`questions`.`lat`)) * cos(radians(`users`.`lat`)) * cos(radians(`users`.`long`) - radians(`questions`.`long`)) + sin(radians(`questions`.`lat`)) * sin(radians(`users`.`lat`))))), 0)) from answers join users on users.id=answers.user_id and question_id=questions.id and (coalesce((0.62137 * (6371 * acos(cos(radians(`questions`.`lat`)) * cos(radians(`users`.`lat`)) * cos(radians(`users`.`long`) - radians(`questions`.`long`)) + sin(radians(`questions`.`lat`)) * sin(radians(`users`.`lat`))))), 0)<=50 or users.is_global)) max_distance');

        $query->join('answers', 'questions.id', '=', 'answers.question_id');
        $query->join('users', 'users.id', '=', 'answers.user_id');
        $query->join('users_roles', 'users_roles.user_id', '=', 'users.id');
        $query->join('roles', 'roles.id', '=', 'users_roles.role_id');

        $query->where('questions.project_id', '=', $projectId);

        if (!empty($worksPackageId)) {
            $query->where('questions.works_package_id', '=', $worksPackageId);
        }

        $questions = $query->get()->groupBy('question_id')->toArray();

        $totals = [
            'total_quotes' => 0,
            'total_time_diff' => 0,
            'total_price' => 0,
            'total_esg' => 0,
            'total_messages' => 0,
            'merchants_quoted' => 0,
            'new_relationships' => [],
        ];

        $quotes_accepted = [];
        $merchants_quoted = [];

        if (empty($questions)) {
            return [];
        }

        foreach ($questions as $id => $answers) {
            $totals['total_quotes'] += count($answers);

            foreach ($answers as $index => $answer) {
                // modify quote data
                $model = new Answer();
                $model->distance = $answer['distance'];
                $model->max_distance = $answer['max_distance'];
                $answer['esgPerc'] = $model->getEsgPercAttribute();

                $time_diff = Carbon::parse($answer['question_created_at'])->diffInSeconds($answer['created_at']);
                $answer['time_difference'] = $time_diff;

                if (!empty($answer['quote_accepted_at'])) {
                    $accepted_at = Carbon::parse($answer['quote_accepted_at']);
                    $answer['quote_accepted_at'] = $accepted_at->format('d-m-Y H:i');

                    $quotes_accepted[] = $answer;
                }

                $answers[$index] = $answer;

                // update totals
                $totals['total_messages'] += $answer['messages'] ?? 0;

                if (!empty($answer['quote_accepted_at'])) {
                    $totals['total_time_diff'] += $time_diff;
                    $totals['total_price'] += $answer['price'];
                    $totals['total_esg'] += $answer['esgPerc'];
                }

                if ($answer['role'] === 'company') {
                    $merchants_quoted[] = $answer['user_id'];

                    if ($answer['is_preferred_supplier'] === 0) {
                        $totals['new_relationships'][] = $answer['user_id'];
                    }
                }
            }

            $questions[$id] = $answers;
        }

        $merchants_quoted = array_unique($merchants_quoted);
        $totals['merchants_quoted'] = count($merchants_quoted);

        // calculate fastest quote time
        $quote_times = array_column($quotes_accepted, 'time_difference');
        $min_quote_time = !empty($quote_times) ? min($quote_times) : 0;

        // convert time diff from seconds to days/hours/minutes
        $start = new Carbon('@0');
        $end = new Carbon("@$min_quote_time");
        $min_quote_time = $start->diff($end)->format('%a days, %h hours, %i minutes');

        return [
            'title' => 'Buyer Report',
            'company_name' => $user->getFirstName(),
            'questions' => $questions,
            'quotes_accepted' => $quotes_accepted,
            'project_name' => $projectName,
            'works_package' => $worksPackageName,
            'total_enquiries' => count($questions),
            'total_quotes' => $totals['total_quotes'],
            'merchants_quoted' => $totals['merchants_quoted'],
            'new_relationships' => count(array_unique($totals['new_relationships'])),
            'min_quote_time' => $min_quote_time,
            'total_price' => number_format($totals['total_price'], 2),
            'total_esg' => number_format($totals['total_esg'], 1),
            'total_messages' => number_format(($totals['total_messages'] / $totals['total_quotes']) * 100, 1),
            'framework_totals' => [0, 0, 0],
            'client_total' => 0,
        ];
    }

    public function getContractorReport(User $user, int $projectId, int $worksPackageId): array
    {
        $project = Project::find($projectId);

        if (empty($project)) {
            return [];
        }

        $projectName = $project->getName();

        $projectTargets = [
            'target_miles_client' => $project->getTargetMilesClient(),
            'target_miles_framework' => $project->getTargetMilesFramework(),
            'target_hours_ap' => $project->getTargetHoursAp(),
            'target_hours_se' => $project->getTargetHoursSe(),
            'budget_se' => $project->getBudgetSe(),
        ];

        $projectDistrictsStr = $project->getPostcodeDistricts();

        $districts = [];
        $projectDistricts = [];

        if (!empty($projectDistrictsStr)) {
            $districts = explode(',', $projectDistrictsStr);
            $districts = array_map('trim', $districts);

            foreach($districts as $district) {
                $projectDistricts[$district] = [
                    'materials' => 0,
                    'trades' => 0
                ];
            }
        }

        $worksPackageName = !empty($worksPackageId) ? WorksPackage::find($worksPackageId)->getName() : '';

        $query = Answer::query()->select([
            'questions.id as question_id',
            'questions.user_id as question_user_id',
            'questions.created_at as question_created_at',
            'questions.project_id as question_project_id',
            'questions.works_package_id as question_works_package_id',
            'answers.id',
            'answers.user_id',
            'answers.price',
            'answers.local_material_spend',
            'answers.created_at',
            'answers.quote_accepted_at',
            'answers.description',
            'answers.has_substitution',
            'users.first_name',
            'users.postcode',
            'users.is_sme',
            'works_packages.name as works_package_name',
            'products.name as product_name'
        ]);

        $query->selectRaw('(select roles.slug from users_roles join roles on roles.id = users_roles.role_id where users_roles.user_id = users.id limit 1) as role');

        $query->selectRaw('(select (count(*)>0) from users_preferred_suppliers where supplier_id = answers.user_id and (user_id = questions.user_id or user_id = (select billing_user_id from users where id = questions.user_id))) is_preferred_supplier');
        $query->selectRaw('(select count(*) from messages where (messages.answer_id = answers.id OR messages.question_id = questions.id)) messages');

        $query->selectRaw('coalesce((0.62137 * (6371 * acos(cos(radians(`questions`.`lat`)) * cos(radians(`users`.`lat`)) * cos(radians(`users`.`long`) - radians(`questions`.`long`)) + sin(radians(`questions`.`lat`)) * sin(radians(`users`.`lat`))))), 0) distance');
        $query->selectRaw('(select max(coalesce((0.62137 * (6371 * acos(cos(radians(`questions`.`lat`)) * cos(radians(`users`.`lat`)) * cos(radians(`users`.`long`) - radians(`questions`.`long`)) + sin(radians(`questions`.`lat`)) * sin(radians(`users`.`lat`))))), 0)) from answers join users on users.id=answers.user_id and question_id=questions.id and (coalesce((0.62137 * (6371 * acos(cos(radians(`questions`.`lat`)) * cos(radians(`users`.`lat`)) * cos(radians(`users`.`long`) - radians(`questions`.`long`)) + sin(radians(`questions`.`lat`)) * sin(radians(`users`.`lat`))))), 0)<=50 or users.is_global)) max_distance');

        $query->join('questions', 'questions.id', '=', 'answers.question_id');
        $query->join('users', 'users.id', '=', 'answers.user_id');
        $query->join('works_packages', 'works_packages.id', '=', 'questions.works_package_id');
        $query->join('products', 'products.id', '=', 'questions.product_id');

        $query->where('questions.project_id', '=', $projectId);

        if ($user->hasRole(Role::ROLE_USER_SLUG) && !$user->hasRole(Role::ROLE_CONTRACTOR)) {
            $query->where('questions.user_id', '=', $user->id);
        }

        if (!empty($worksPackageId)) {
            $query->where('questions.works_package_id', '=', $worksPackageId);
        }

//        $query->groupBy([
//            'answers.id',
//            'questions.id',
//            'questions.user_id',
//            'questions.created_at',
//            'questions.project_id',
//            'questions.works_package_id',
//            'answers.user_id',
//            'answers.price',
//            'answers.local_material_spend',
//            'answers.created_at',
//            'answers.quote_accepted_at',
//            'answers.description',
//            'users.first_name',
//            'users.postcode',
//            'works_packages.name',
//            'questions.lat',
//            'questions.long',
//            'users.lat',
//            'users.long',
//            'products.name'
//        ]);

        $questions = $query->get()->groupBy('question_id')->toArray();

        $query2 = SupplyFitEnquiry::query()->select([
            'supply_fit_enquiries.id as enquiry_id',
            'supply_fit_enquiries.user_id as enquiry_user_id',
            'supply_fit_enquiries.created_at as enquiry_created_at',
            'supply_fit_enquiry_quotes.id',
            'supply_fit_enquiry_quotes.user_id',
            'supply_fit_enquiry_quotes.price',
            'supply_fit_enquiry_quotes.created_at',
            'supply_fit_enquiry_quotes.quote_accepted_at',
            'supply_fit_enquiry_quotes.description',
            'users.first_name',
            'users.postcode',
            'users.is_sme',
            'works_packages.name as works_package_name',
            'products.name as product_name',
            'reviews.id as review_id',
            'reviews.status as review_status'
        ]);

        $query->selectRaw('(select roles.slug from users_roles join roles on roles.id = users_roles.role_id where users_roles.user_id = users.id limit 1) as role');

        $query2->selectRaw('(select (count(*)>0) from users_preferred_subcontractors where subcontractor_id = supply_fit_enquiry_quotes.user_id and (user_id = supply_fit_enquiries.user_id or user_id = (select billing_user_id from users where id = supply_fit_enquiries.user_id))) is_preferred_subcontractor');
        $query2->selectRaw('(SELECT count(*) FROM messages WHERE (supply_fit_enquiry_quote_id = supply_fit_enquiry_quotes.id OR supply_fit_enquiry_id = enquiry_id)) messages');

        $query2->selectRaw('coalesce((0.62137 * (6371 * acos(cos(radians(`supply_fit_enquiries`.`lat`)) * cos(radians(`users`.`lat`)) * cos(radians(`users`.`long`) - radians(`supply_fit_enquiries`.`long`)) + sin(radians(`supply_fit_enquiries`.`lat`)) * sin(radians(`users`.`lat`))))), 0) distance');
        $query2->selectRaw('(select max(coalesce((0.62137 * (6371 * acos(cos(radians(`supply_fit_enquiries`.`lat`)) * cos(radians(`users`.`lat`)) * cos(radians(`users`.`long`) - radians(`supply_fit_enquiries`.`long`)) + sin(radians(`supply_fit_enquiries`.`lat`)) * sin(radians(`users`.`lat`))))), 0)) from supply_fit_enquiry_quotes join users on users.id=supply_fit_enquiry_quotes.user_id and enquiry_id=supply_fit_enquiries.id and (coalesce((0.62137 * (6371 * acos(cos(radians(`supply_fit_enquiries`.`lat`)) * cos(radians(`users`.`lat`)) * cos(radians(`users`.`long`) - radians(`supply_fit_enquiries`.`long`)) + sin(radians(`supply_fit_enquiries`.`lat`)) * sin(radians(`users`.`lat`))))), 0)<=50 or users.is_global)) max_distance');

        $query2->selectRaw("(select (count(*)>0) from certificates where user_id = users.billing_user_id and type = 'cas') as cas_approved");

        $query2->join('supply_fit_enquiry_quotes', 'supply_fit_enquiries.id', '=', 'supply_fit_enquiry_quotes.enquiry_id');
        $query2->join('users', 'users.id', '=', 'supply_fit_enquiry_quotes.user_id');
        $query2->join('works_packages', 'works_packages.id', '=', 'supply_fit_enquiries.works_package_id');
        $query2->join('products', 'products.id', '=', 'supply_fit_enquiries.product_id');
        $query2->leftJoin('reviews', 'reviews.quote_id', '=', 'supply_fit_enquiry_quotes.id');

        $query2->where('supply_fit_enquiries.project_id', '=', $projectId);

        if ($user->hasRole(Role::ROLE_USER_SLUG) && !$user->hasRole(Role::ROLE_CONTRACTOR)) {
            $query2->where('supply_fit_enquiry_quotes.user_id', '=', $user->id);
        }

        if (!empty($worksPackageId)) {
            $query2->where('supply_fit_enquiries.works_package_id', '=', $worksPackageId);
        }

//        $query2->groupBy([
//            'supply_fit_enquiry_quotes.id',
//            'supply_fit_enquiry_quotes.enquiry_id',
//            'supply_fit_enquiries.id',
//            'supply_fit_enquiries.user_id',
//            'supply_fit_enquiries.created_at',
//            'supply_fit_enquiry_quotes.user_id',
//            'supply_fit_enquiry_quotes.price',
//            'supply_fit_enquiry_quotes.created_at',
//            'supply_fit_enquiry_quotes.quote_accepted_at',
//            'supply_fit_enquiry_quotes.description',
//            'users.first_name',
//            'users.postcode',
//            'works_packages.name',
//            'supply_fit_enquiries.lat',
//            'supply_fit_enquiries.long',
//            'users.lat',
//            'users.long',
//            'products.name'
//        ]);

        $sfEnquiries = $query2->get()->groupBy('enquiry_id')->toArray();

        $totals = [
            'materials_enquiries' => 0,
            'materials_quotes' => 0,
            'materials_wps' => 0,
            'materials_spent' => 0,
            'materials_esg' => 0,
            'materials_nro' => 0,
            'materials_nra' => 0,
            'trades_enquiries' => 0,
            'trades_quotes' => 0,
            'trades_wps' => 0,
            'trades_spent' => 0,
            'trades_esg' => 0,
            'trades_nro' => 0,
            'trades_nra' => 0,
            'merchants_quoted' => 0,
            'used_live_chat' => 0,
            'users_sme' => 0,
        ];

        $materials_quotes_accepted = ['data' => [], 'totals' => [0, 0, 0, 0], 'totals_local' => [0, 0, 0, 0]];
        $trades_quotes_accepted = ['data' => [], 'totals' => [0, 0, 0, 0], 'totals_local' => [0, 0, 0, 0]];
        $materials_quotes_with_subs = [];
        $merchants_quoted = [];
        $materials_wps = [];
        $trades_wps = [];
        $sub_contractors = [];
        $userIds = [];
        $nroUserIds = [];
        $nraUserIds = [];

        if (empty($questions) && empty($sfEnquiries)) {
            return [];
        }

        foreach ($questions as $id => $answers) {
            $totals['materials_quotes'] += count($answers);

            $prices = array_column($answers, 'price');
            $max_price = max($prices);
            $min_price = min($prices);
            $accepted_price = 0.0;

            foreach ($answers as $index => $answer) {
                // modify quote data
                $model = new Answer();
                $model->distance = $answer['distance'];
                $model->max_distance = $answer['max_distance'];
                $answer['esgPerc'] = $model->getEsgPercAttribute();

                if (empty($answer['distance'])) {
                    $answer['distance'] = 0.1;
                }

                $time_diff = Carbon::parse($answer['question_created_at'])->diffInSeconds($answer['created_at']);
                $answer['time_difference'] = $time_diff;
                $materials_wps[] = $answer['works_package_name'];

                // convert time diff from seconds to hours/minutes
                $start = new Carbon('@0');
                $end = new Carbon("@{$answer['time_difference']}");
                $answer['time_diff'] = $start->diff($end)->format('%a days, %h hours, %i minutes');

                // calculate total savings
                $answer['total_savings'] = '';

                // nro = not a preferred supplier
                if ($answer['is_preferred_supplier'] === 0 && !in_array($answer['user_id'], $nroUserIds)) {
                    $totals['materials_nro']++;
                    $nroUserIds[] = $answer['user_id'];
                }

                // format created at
                $created_at = Carbon::parse($answer['created_at']);
                $answer['created_at'] = $created_at->format('d-m-Y H:i');

                // fix contractor name
                $enquiryUser = User::find($answer['question_user_id']);
                $answer['contractor_name'] = $enquiryUser->getFirstName();

                if (!empty($answer['quote_accepted_at'])) {
                    $accepted_at = Carbon::parse($answer['quote_accepted_at']);
                    $answer['quote_accepted_at'] = $accepted_at->format('d-m-Y H:i');

                    $totals['materials_spent'] += $answer['price'];
                    $totals['materials_esg'] += $answer['esgPerc'];

                    if ($answer['is_preferred_supplier'] === 0 && !in_array($answer['user_id'], $nraUserIds)) {
                        $totals['materials_nra']++;
                        $nraUserIds[] = $answer['user_id'];
                    }

                    $answer['total_savings'] = '£ ' . number_format($max_price - $answer['price'], 2);

                    if ($answer['distance'] > 0 && $answer['distance'] <=10) {
                        $answer['distance_within'] = '10 miles';
                        $materials_quotes_accepted['totals'][0] += $answer['price'];
                        $materials_quotes_accepted['totals'][1] += $answer['price'];
                        $materials_quotes_accepted['totals'][2] += $answer['price'];
                        $materials_quotes_accepted['totals'][3] += $answer['price'];
                        $materials_quotes_accepted['totals_local'][0] += $answer['local_material_spend'];
                        $materials_quotes_accepted['totals_local'][1] += $answer['local_material_spend'];
                        $materials_quotes_accepted['totals_local'][2] += $answer['local_material_spend'];
                        $materials_quotes_accepted['totals_local'][3] += $answer['local_material_spend'];
                    } elseif ($answer['distance'] > 10 && $answer['distance'] <= 20) {
                        $answer['distance_within'] = '20 miles';
                        $materials_quotes_accepted['totals'][1] += $answer['price'];
                        $materials_quotes_accepted['totals'][2] += $answer['price'];
                        $materials_quotes_accepted['totals'][3] += $answer['price'];
                        $materials_quotes_accepted['totals_local'][1] += $answer['local_material_spend'];
                        $materials_quotes_accepted['totals_local'][2] += $answer['local_material_spend'];
                        $materials_quotes_accepted['totals_local'][3] += $answer['local_material_spend'];
                    } elseif ($answer['distance'] > 20 && $answer['distance'] <= 40) {
                        $answer['distance_within'] = '40 miles';
                        $materials_quotes_accepted['totals'][2] += $answer['price'];
                        $materials_quotes_accepted['totals'][3] += $answer['price'];
                        $materials_quotes_accepted['totals_local'][2] += $answer['local_material_spend'];
                        $materials_quotes_accepted['totals_local'][3] += $answer['local_material_spend'];
                    } else {
                        $materials_quotes_accepted['totals'][3] += $answer['price'];
                        $materials_quotes_accepted['totals_local'][3] += $answer['local_material_spend'];
                        $answer['distance_within'] = '> 40 miles';
                    }

                    // get the product category from corresponding marketplace enquiry
                    $answer['supply_fit_product_name'] = $this->getSupplyFitProduct(
                        $answer['question_user_id'],
                        $answer['question_project_id'],
                        $answer['question_works_package_id']
                    );

                    // project district total value
                    $answerPostcodeArr = explode(' ', $answer['postcode']);
                    if (!empty($districts) && in_array($answerPostcodeArr[0], $districts)) {
                        $projectDistricts[$answerPostcodeArr[0]]['materials'] += $answer['local_material_spend'];
                    }

                    // quotes with subs
                    if (!empty($answer['has_substitution'])) {
                        $materials_quotes_with_subs[] = $answer;
                    }

                    $materials_quotes_accepted['data'][] = $answer;
                }

                // count sme users
                if (!in_array($answer['user_id'], $userIds)) {
                    $userIds[] = $answer['user_id'];

                    if ($answer['is_sme']) {
                        $totals['users_sme']++;
                    }
                }

                if ($answer['role'] === 'company') {
                    $merchants_quoted[] = $answer['user_id'];
                }

                if ($answer['role'] === 'user' && !empty($answer['messages']) && !in_array($answer['user_id'], $sub_contractors)) {
                    $totals['used_live_chat']++;
                    $sub_contractors[] = $answer['user_id'];
                }

                $answers[$index] = $answer;
            }

            $questions[$id] = $answers;
        }

        $totals['materials_enquiries'] = $this->getTotalEnquiriesByProject($projectId, 'materials');
        $totals['merchants_quoted'] = count(array_unique($merchants_quoted));
        $totals['materials_wps'] = count(array_unique($materials_wps));
        $nroUserIds = [];
        $nraUserIds = [];

        foreach ($sfEnquiries as $id => $quotes) {
            $totals['trades_quotes'] += count($quotes);

            $prices = array_column($quotes, 'price');
            $max_price = max($prices);
            $min_price = min($prices);
            $accepted_price = 0.0;

            foreach ($quotes as $index => $quote) {
                // modify quote data
                $model = new SupplyFitEnquiryQuote();
                $model->distance = $quote['distance'];
                $model->max_distance = $quote['max_distance'];
                $quote['esgPerc'] = $model->getEsgPercAttribute();

                if (empty($quote['distance'])) {
                    $quote['distance'] = 0.1;
                }

                $time_diff = Carbon::parse($quote['enquiry_created_at'])->diffInSeconds($quote['created_at']);
                $quote['time_difference'] = $time_diff;

                $trades_wps[] = $quote['works_package_name'];

                // convert time diff from seconds to hours/minutes
                $start = new Carbon('@0');
                $end = new Carbon("@{$quote['time_difference']}");
                $quote['time_diff'] = $start->diff($end)->format('%a days, %h hours, %i minutes');

                // calculate total savings
                $quote['total_savings'] = '';

                if ($quote['is_preferred_subcontractor'] === 0 && !in_array($quote['user_id'], $nroUserIds)) {
                    $totals['trades_nro']++;
                    $nroUserIds[] = $quote['user_id'];
                }

                // format created date
                $created_at = Carbon::parse($quote['created_at']);
                $quote['created_at'] = $created_at->format('d-m-Y H:i');

                if (!empty($quote['quote_accepted_at'])) {
                    $accepted_at = Carbon::parse($quote['quote_accepted_at']);
                    $quote['quote_accepted_at'] = $accepted_at->format('d-m-Y H:i');

                    $totals['trades_spent'] += $quote['price'];
                    $totals['trades_esg'] += $quote['esgPerc'];

                    if ($quote['is_preferred_subcontractor'] === 0 && !in_array($quote['user_id'], $nraUserIds)) {
                        $totals['trades_nra']++;
                        $nraUserIds[] = $quote['user_id'];
                    }

                    $quote['total_savings'] = '£ ' . number_format($max_price - $quote['price'], 2);

                    if ($quote['distance'] > 0 && $quote['distance'] <=10) {
                        $quote['distance_within'] = '10 miles';
                        $trades_quotes_accepted['totals'][0] += $quote['price'];
                        $trades_quotes_accepted['totals'][1] += $quote['price'];
                        $trades_quotes_accepted['totals'][2] += $quote['price'];
                        $trades_quotes_accepted['totals'][3] += $quote['price'];
                    } elseif ($quote['distance'] > 10 && $quote['distance'] <= 20) {
                        $quote['distance_within'] = '20 miles';
                        $trades_quotes_accepted['totals'][1] += $quote['price'];
                        $trades_quotes_accepted['totals'][2] += $quote['price'];
                        $trades_quotes_accepted['totals'][3] += $quote['price'];
                    } elseif ($quote['distance'] > 20 && $quote['distance'] <= 40) {
                        $quote['distance_within'] = '40 miles';
                        $trades_quotes_accepted['totals'][2] += $quote['price'];
                        $trades_quotes_accepted['totals'][3] += $quote['price'];
                    } else {
                        $quote['distance_within'] = '> 40 miles';
                        $trades_quotes_accepted['totals'][3] += $quote['price'];
                    }

                    // project district total value
                    $quotePostcodeArr = explode(' ', $quote['postcode']);
                    if (!empty($districts) && in_array($quotePostcodeArr[0], $districts)) {
                        $projectDistricts[$quotePostcodeArr[0]]['trades'] += $quote['price'];
                    }

                    $trades_quotes_accepted['data'][] = $quote;
                }

                if (($quote['role'] ?? '') === 'user' && !empty($quote['messages']) && !in_array($quote['user_id'], $sub_contractors)) {
                    $totals['used_live_chat']++;
                    $sub_contractors[] = $quote['user_id'];
                }

                // count sme users
                if (!in_array($quote['user_id'], $userIds)) {
                    $userIds[] = $quote['user_id'];

                    if ($quote['is_sme']) {
                        $totals['users_sme']++;
                    }
                }

                $quotes[$index] = $quote;
            }

            $sfEnquiries[$id] = $quotes;
        }

        $totals['trades_enquiries'] = $this->getTotalEnquiriesByProject($projectId, 'trades');
        $totals['trades_wps'] = count(array_unique($trades_wps));

        // calculate fastest trades quote time
        $quote_times = array_column($trades_quotes_accepted, 'time_difference');
        $min_quote_time = !empty($quote_times) ? min($quote_times) : 0;

        // convert time diff from seconds to days/hours/minutes
        $start = new Carbon('@0');
        $end = new Carbon("@$min_quote_time");
        $min_quote_time = $start->diff($end)->format('%a days, %h hours, %i minutes');

        $materials_average_esg = $totals['materials_esg'] && count($materials_quotes_accepted['data']) ? $totals['materials_esg'] / count($materials_quotes_accepted['data']) : 0;
        $trades_average_esg = $totals['trades_esg'] && count($trades_quotes_accepted['data']) ? $totals['trades_esg'] / count($trades_quotes_accepted['data']) : 0;
        $percent_used_live_chat = $totals['used_live_chat'] && count($sub_contractors) ? ($totals['used_live_chat'] / count($sub_contractors)) * 100 : 0;
        $percent_users_sme = $totals['users_sme'] && count($userIds) ? ($totals['users_sme'] / count($userIds)) * 100 : 0;

        return [
            'title' => 'Main Contractor Report',
            'company_name' => $user->getFirstName(),
            'project_name' => $projectName,
            'project_targets' => $projectTargets,
            'project_districts' => $projectDistricts,
            'works_package' => $worksPackageName,
            'totals' => $totals,
            'enquiries' => ['materials' => $questions, 'trades' => $sfEnquiries],
            'quotes_accepted' => ['materials' => $materials_quotes_accepted, 'trades' => $trades_quotes_accepted],
            'materials_quotes_with_subs' => $materials_quotes_with_subs,
            'min_quote_time' => $min_quote_time,
            'materials_average_esg' => number_format($materials_average_esg, 2),
            'trades_average_esg' => number_format($trades_average_esg, 2),
            'percent_used_live_chat' => number_format($percent_used_live_chat, 2),
            'percent_users_sme' => number_format($percent_users_sme, 2),
        ];
    }

    public function getContractorMapData(?int $radius, array $type, int $projectId, array $worksPackageIds, User $user): array
    {
        $quotes = [];

        if (in_array(1, $type) || in_array(3, $type) || empty($type)) {
            $query = Answer::query()->select([
                    'answers.id',
                    'answers.user_id',
                    'answers.quote_accepted_at',
                    'answers.price',
                    'answers.local_material_spend',
                    'users.first_name',
                    'users.postcode',
                    'users.lat',
                    'users.long'
                ])
                ->join('questions', 'questions.id', '=', 'answers.question_id')
                ->join('users', 'answers.user_id', '=', 'users.id')
                ->where([
                    ['questions.project_id', '=', $projectId],
                    ['users.lat', '!=', 0],
                    ['users.long', '!=', 0]
                ]);

            $query->selectRaw('coalesce((0.62137 * (6371 * acos(cos(radians(`questions`.`lat`)) * cos(radians(`users`.`lat`)) * cos(radians(`users`.`long`) - radians(`questions`.`long`)) + sin(radians(`questions`.`lat`)) * sin(radians(`users`.`lat`))))), 0) distance');

            if (!empty($worksPackageIds)) {
                $query->whereIn('questions.works_package_id', $worksPackageIds);
            }

            if (in_array(3, $type) && !in_array(1, $type)) {
                $query->whereNotNull('answers.quote_accepted_at');
            }

            if ($user->hasRole(Role::ROLE_USER_SLUG) && !$user->hasRole(Role::ROLE_CONTRACTOR)) {
                $query->where(function ($query) use ($user) {
                    $query->where('answers.user_id', '=', $user->id)
                        ->orWhere('questions.user_id', '=', $user->id);
                });
            }

            $quotes_ph = $query->get();

            foreach ($quotes_ph as $quote) {
//                if ($quote['distance'] > $radius) {
//                    continue;
//                }

                if (empty($quote->lat) || empty($quote->long)) {
                    $result = DB::table('postcodes')->where('postcode', $quote->postcode)->first();

                    if (!empty($result)) {
                        DB::table('users')->where('id', $quote->user_id)
                            ->update([
                                'lat' => $result->latitude,
                                'long' => $result->longitude
                            ]);

                        $quote->lat = $result->latitude;
                        $quote->long = $result->longitude;
                    }
                }

                $quotes[] = [
                    'id' => $quote->id,
                    'type' => $quote->quote_accepted_at != null ? 3 : 1,
                    'distance' => $quote->distance,
                    'price' => $quote->price,
                    'local_material_spend' => $quote->local_material_spend,
                    'name' => $quote->first_name,
                    'postcode' => $quote->postcode,
                    'lat' => $quote->lat,
                    'long' => $quote->long
                ];
            }
        }

        if (in_array(2, $type) || in_array(4, $type) || empty($type)) {
            $query = SupplyFitEnquiryQuote::select([
                    'supply_fit_enquiry_quotes.id',
                    'supply_fit_enquiry_quotes.user_id',
                    'supply_fit_enquiry_quotes.quote_accepted_at',
                    'supply_fit_enquiry_quotes.price',
                    'supply_fit_enquiry_quotes.local_material_spend',
                    'users.first_name',
                    'users.postcode',
                    'users.lat',
                    'users.long'
                ])
                ->join('supply_fit_enquiries', 'supply_fit_enquiries.id', '=', 'supply_fit_enquiry_quotes.enquiry_id')
                ->join('users', 'supply_fit_enquiry_quotes.user_id', '=', 'users.id')
                ->where([
                    ['supply_fit_enquiries.project_id', '=', $projectId],
                    ['users.lat', '!=', 0],
                    ['users.long', '!=', 0]
                ]);

            $query->selectRaw('coalesce((0.62137 * (6371 * acos(cos(radians(`supply_fit_enquiries`.`lat`)) * cos(radians(`users`.`lat`)) * cos(radians(`users`.`long`) - radians(`supply_fit_enquiries`.`long`)) + sin(radians(`supply_fit_enquiries`.`lat`)) * sin(radians(`users`.`lat`))))), 0) distance');

            if (!empty($worksPackageIds)) {
                $query->whereIn('supply_fit_enquiries.works_package_id', $worksPackageIds);
            }

            if (in_array(4, $type) && !in_array(2, $type)) {
                $query->whereNotNull('supply_fit_enquiry_quotes.quote_accepted_at');
            }

            if ($user->hasRole(Role::ROLE_USER_SLUG) && !$user->hasRole(Role::ROLE_CONTRACTOR)) {
                $query->where(function ($query) use ($user) {
                    $query->where('supply_fit_enquiries.user_id', '=', $user->id)
                        ->orWhere('supply_fit_enquiry_quotes.user_id', '=', $user->id);
                });
            }

            $quotes_sf = $query->get();

            foreach ($quotes_sf as $quote) {
//                if ($quote['distance'] > $radius) {
//                    continue;
//                }

                if (empty($quote->lat) || empty($quote->long)) {
                    $result = DB::table('postcodes')->where('postcode', $quote->postcode)->first();

                    if (!empty($result)) {
                        DB::table('users')->where('id', $quote->user_id)
                            ->update([
                                'lat' => $result->latitude,
                                'long' => $result->longitude
                            ]);

                        $quote->lat = $result->latitude;
                        $quote->long = $result->longitude;
                    }
                }

                $quotes[] = [
                    'id' => $quote->id,
                    'type' => $quote->quote_accepted_at != null ? 4 : 2,
                    'distance' => $quote->distance,
                    'price' => $quote->price,
                    'local_material_spend' => $quote->local_material_spend,
                    'name' => $quote->first_name,
                    'postcode' => $quote->postcode,
                    'lat' => $quote->lat,
                    'long' => $quote->long
                ];
            }
        }

        // get the project coords
        // if project postcode isset then calculate the lat/long and use these coords for the map centre point
        // else use coords of first supply fit enquiry (highest id/most recent enquiry)
        $project = Project::find($projectId);
        $projectLat = 0;
        $projectLong = 0;

        if (!empty($project->getPostcode())) {
            $postcode = trim(substr(trim($project->getPostcode()), 0, -3));
            $result = DB::table('postcodes')->where('Postcode', $postcode)->first();

            if ($result) {
                $projectLat = (float) $result->latitude;
                $projectLong = (float) $result->longitude;
            }
        }

        if (empty($projectLat) || empty($projectLong)) {
            $projectCoords = DB::table('supply_fit_enquiries')->select('lat', 'long')
                ->where('project_id', $projectId)
                ->whereNotNull(['lat', 'long'])
                ->get()->first();

            $projectLat = $projectCoords->lat;
            $projectLong = $projectCoords->long;
        }

        return [
            'quotes' => $quotes,
            'project_coords' => [
                'lat' => $projectLat,
                'lng' => $projectLong
            ]
        ];
    }

    private function getTotalEnquiriesByProject(int $projectId, string $type): int
    {
        $total = 0;

        if ($type == 'materials') {
            $query = Question::query()->selectRaw('count(*) as total')->where('project_id', $projectId);
            $total = $query->first()->total;
        } else if ($type == 'trades') {
            $query = SupplyFitEnquiry::query()->selectRaw('count(*) as total')->where('project_id', $projectId);
            $total = $query->first()->total;
        }

        return $total;
    }

    private function getSupplyFitProduct(int $userId, int $projectId, int $worksPackageId): ?string
    {
        if (empty($userId)) {
            return null;
        }

        // use answer id to get purchase hire enquiry
        // get the user id who created the ph enquiry
        // get the project id and wp id from the ph enquiry

        // do we match ph user id with sf enquiry user id or sf quote user id????

        $query = SupplyFitEnquiry::query()->select('products.name as product_name')
            ->join('supply_fit_enquiry_quotes', 'supply_fit_enquiries.id', '=', 'supply_fit_enquiry_quotes.enquiry_id')
            ->join('products', 'supply_fit_enquiries.product_id', '=', 'products.id')
            ->where([
                'supply_fit_enquiry_quotes.user_id' => $userId,
                'supply_fit_enquiries.project_id' => $projectId,
                'supply_fit_enquiries.works_package_id' => $worksPackageId
            ]);

        return $query->first()->product_name ?? null;
    }

    private function getTotalProjectsByUser(array $userIds): Collection
    {
        $query = DB::table('projects')->select('projects.id')
            ->join('supply_fit_enquiries', 'supply_fit_enquiries.project_id', '=', 'projects.id')
            ->join('supply_fit_enquiry_quotes', 'supply_fit_enquiry_quotes.enquiry_id', '=', 'supply_fit_enquiries.id')
            ->whereIn('supply_fit_enquiry_quotes.user_id', $userIds)
            ->whereNull('projects.archived_at')
            ->groupBy('projects.id');

        return $query->get();
    }

    public function getSubcontractorReport(User $user, array $subcontractorIds): array
    {
        $data = [];

        $totalProjects = count($this->getTotalProjectsByUser($subcontractorIds));

        // get quotes (last 12 months)
        $quotesQuery = SupplyFitEnquiryQuote::query()
            ->select([
                'supply_fit_enquiry_quotes.id',
                'supply_fit_enquiry_quotes.user_id',
                'supply_fit_enquiry_quotes.enquiry_id',
                'supply_fit_enquiry_quotes.quote_accepted_at',
                'supply_fit_enquiry_quotes.price',
            ])
            ->join('supply_fit_enquiries', 'supply_fit_enquiries.id', '=', 'supply_fit_enquiry_quotes.enquiry_id')
            ->whereIn('supply_fit_enquiry_quotes.user_id', $subcontractorIds)
            ->where([
                ['supply_fit_enquiries.user_id', '=', $user->getId()],
                ['supply_fit_enquiry_quotes.created_at', '>=', Carbon::now()->subMonths(12)->format('Y-m-d H:i:s')]
            ])
            ->groupBy([
                'supply_fit_enquiry_quotes.id',
                'supply_fit_enquiry_quotes.user_id',
                'supply_fit_enquiry_quotes.enquiry_id',
                'supply_fit_enquiry_quotes.quote_accepted_at',
                'supply_fit_enquiry_quotes.price',
            ]);

        $quotes = $quotesQuery->get()->toArray();

        foreach($subcontractorIds as $scid) {
            $scid = (int) $scid;

            $acceptedProjectsQuery = DB::table('projects')->select('projects.id')
                ->join('supply_fit_enquiries', 'supply_fit_enquiries.project_id', '=', 'projects.id')
                ->join('supply_fit_enquiry_quotes', 'supply_fit_enquiry_quotes.enquiry_id', '=', 'supply_fit_enquiries.id')
                ->where('supply_fit_enquiry_quotes.user_id', '=', $scid)
                ->whereNotNull('supply_fit_enquiry_quotes.quote_accepted_at')
                ->whereNull('projects.archived_at')
                ->groupBy('projects.id');

            $acceptedProjects = $acceptedProjectsQuery->get();

            $data[$scid] = [
                'total_projects' => $totalProjects,
                'accepted_projects' => $acceptedProjects->count(),
                'total_quotes' => 0,
                'accepted_quotes' => 0,
                'total_quotes_value' => 0,
                'accepted_quotes_value' => 0,
                'average_quote_value' => 0,
            ];

            foreach($quotes as $quote) {
                if ($quote['user_id'] === $scid) {
                    $data[$scid]['total_quotes']++;
                    $data[$scid]['total_quotes_value'] += $quote['price'];

                    if (!empty($quote['quote_accepted_at'])) {
                        $data[$scid]['accepted_quotes']++;
                        $data[$scid]['accepted_quotes_value'] += $quote['price'];
                    }
                }
            }

            $data[$scid]['average_quote_value'] = ($data[$scid]['accepted_quotes'] > 0) ? ($data[$scid]['accepted_quotes_value'] / $data[$scid]['accepted_quotes']) : 0;
        }

        return $data;
    }

    public function getUserSummary(User $user): array
    {
        $data = [];

        $userQuery = DB::table('users')
            ->select([
                'users.id',
                'users.first_name',
                'users.postcode',
                'users.phone',
                'users.email',
                'user_creditsafe.risk_score'
            ])
            ->selectRaw('ROUND(users.contractor_score, 0) as contractor_score')
            ->selectRaw('(SELECT GROUP_CONCAT(p.name ORDER BY p.name SEPARATOR \',\') FROM products p INNER JOIN product_user pu ON pu.product_id = p.id WHERE pu.user_id = users.id) as products')
            ->selectRaw('(SELECT first_name FROM users u2 WHERE u2.id = users.billing_user_id) as company_name');

        $certTypes = array_column(Certificate::TYPES, 'slug');

        foreach($certTypes as $ct) {
            $userQuery->selectRaw("(SELECT (count(*)>0) FROM certificates WHERE certificates.user_id = users.id AND certificates.type = '{$ct}') as `{$ct}`");
        }

        $userQuery->where('users.id', '=', $user->getId())
            ->leftJoin('user_creditsafe', 'users.id', '=', 'user_creditsafe.user_id');

        $data['user'] = $userQuery->get()->first();

        $reviewsQuery = Review::query()
            ->select([
                'reviews.*',
                'users.first_name as reviewer_name',
                'projects.name as project_name',
                'works_packages.name as works_package_name',
            ])
            ->selectRaw("(SELECT SUM(score) FROM review_template_options rto JOIN review_answers ra ON ra.option_id = rto.id WHERE ra.review_id = reviews.id) as score")
            ->selectRaw("(SELECT SUM(score) FROM review_template_options rto
                JOIN review_template_questions rtq ON rto.question_id = rtq.id
                JOIN review_template_sections rts ON rtq.section_id = rts.id
                WHERE rts.template_id = reviews.template_id) as total")
            ->join('users', 'reviews.user_id', '=', 'users.id')
            ->join('supply_fit_enquiry_quotes', 'supply_fit_enquiry_quotes.id', '=', 'reviews.quote_id')
            ->join('supply_fit_enquiries', 'supply_fit_enquiries.id', '=', 'supply_fit_enquiry_quotes.enquiry_id')
            ->leftJoin('projects', 'supply_fit_enquiries.project_id', '=', 'projects.id')
            ->leftJoin('works_packages', 'works_packages.id', '=', 'supply_fit_enquiries.works_package_id')
            ->where('supply_fit_enquiry_quotes.user_id', '=', $user->getId());

        $data['reviews'] = $reviewsQuery->get();

        $data['total_projects'] = count($this->getTotalProjectsByUser([$user->getId()]));
        $data['total_reviews'] = count($reviewsQuery->get());

        return $data;
    }
}
