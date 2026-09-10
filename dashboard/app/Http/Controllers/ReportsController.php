<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataProvider\UserDataProvider;
use App\Dto\SearchParamsDto;
use App\Models\Answer;
use App\Models\PermissionsReference;
use App\Models\Questionnaire\Project;
use App\Models\SupplyFitEnquiry;
use App\Models\SupplyFitEnquiryQuote;
use App\Models\User;
use App\Repository\ProjectTimeTrackingRepository;
use App\Service\OpenAIService;
use App\Service\ReportsService;
use App\Service\UserService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportsController extends Controller
{
    private ReportsService $reportsService;
    private UserService $userService;
    private UserDataProvider $userDataProvider;
    private ProjectTimeTrackingRepository $projectTimeTrackingRepository;

    public function __construct(
        ReportsService $service,
        UserService $userService,
        UserDataProvider $userDataProvider,
        ProjectTimeTrackingRepository $projectTimeTrackingRepository
    ) {
        $this->reportsService = $service;
        $this->userService = $userService;
        $this->userDataProvider = $userDataProvider;
        $this->projectTimeTrackingRepository = $projectTimeTrackingRepository;
    }

    public function supplierReport(int $id): JsonResponse
    {
        $currentUser = $this->userService->getCurrentUser();
        if (!$currentUser->isAdmin() && !$currentUser->isBillingUser() && !$currentUser->isSupplier()) {
            return new JsonResponse('Unknown user', Response::HTTP_FORBIDDEN);
        }

        $user = $this->userService->getById($id);
        if (!$user || !($user->can(PermissionsReference::answerQuestion) || $user->isBranchManager() || $user->isBillingUser())) {
            return new JsonResponse('Wrong request', Response::HTTP_BAD_REQUEST);
        }

        $result = $this->reportsService->getSupplierReport($user);

        return new JsonResponse($result);
    }

    public function logisticsReport(): JsonResponse
    {
        $user = $this->userService->getCurrentUser();

        if (!$user->isAdmin() && !$user->isLogistics()) {
            return new JsonResponse('Unknown user', Response::HTTP_FORBIDDEN);
        }

        $result = $this->reportsService->getLogisticsReport($user);

        return new JsonResponse($result);
    }

    public function totalEnquiries(): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Unknown user', Response::HTTP_FORBIDDEN);
        }

        if (!$user->getBranchId()) {
            return new JsonResponse('Unknown branch', Response::HTTP_BAD_REQUEST);
        }

        $result = $this->reportsService->getTotalEnquiries($user->getBranchId());

        return new JsonResponse($result);
    }

    public function enquiriesToTimeContractor(): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Unknown user', Response::HTTP_FORBIDDEN);
        }

        $result = $this->reportsService->getEnquiriesToTimeContractor(!$user->hasRole(\App\Models\Role::ROLE_ADMIN_SLUG) ? $user : null);

        return new JsonResponse($result);
    }

    public function enquiriesToTimeMerchant(): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Unknown user', Response::HTTP_FORBIDDEN);
        }

        $result = $this->reportsService->getEnquiriesToTimeMerchant($user);

        return new JsonResponse($result);
    }

    public function totalsContractorOnEnquiries(): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Unknown user', Response::HTTP_FORBIDDEN);
        }

        $result = $this->reportsService->getTotalContractorOnEnquiries($user);

        return new JsonResponse($result);
    }

    public function totalsSupplierOnEnquiries(): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Unknown user', Response::HTTP_FORBIDDEN);
        }

        $result = $this->reportsService->getTotalSupplierOnEnquiries($user);

        return new JsonResponse($result);
    }

    public function categoriesPercentageSelectedForMerchant(): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Unknown user', Response::HTTP_FORBIDDEN);
        }

        $result = $this->reportsService->categoriesPercentageSelectedForMerchant($user);

        return new JsonResponse($result);
    }

    public function categoriesPercentageSelectedForContractor(): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Unknown user', Response::HTTP_FORBIDDEN);
        }

        $result = $this->reportsService->categoriesPercentageSelectedForContractor($user);

        return new JsonResponse($result);
    }

    public function totalQuotes(): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Unknown user', Response::HTTP_FORBIDDEN);
        }

        if (!$user->getBranchId()) {
            return new JsonResponse('Unknown branch', Response::HTTP_BAD_REQUEST);
        }

        $result = $this->reportsService->getTotalQuotes($user->getBranchId());

        return new JsonResponse($result);
    }

    public function branchPerformance(): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Unknown user', Response::HTTP_FORBIDDEN);
        }

        if (!$user->getBranchId()) {
            return new JsonResponse('Unknown branch', Response::HTTP_BAD_REQUEST);
        }

        $result = $this->reportsService->getBranchPerformance($user->getBranchId());

        return new JsonResponse($result);
    }

    public function virtualExportReportForAll(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Unknown user', Response::HTTP_FORBIDDEN);
        }

        /**
         * TODO: check permissions
         */
        $dateFrom = ($request->query->get('dateFrom')) ? Carbon::createFromFormat('d-m-Y', $request->query->get('dateFrom')) : null;
        $dateTo = ($request->query->get('dateTo')) ? Carbon::createFromFormat('d-m-Y', $request->query->get('dateTo')) : null;
        $manufacturers = [];
        if (!$user->isAdmin()) {
            $manufacturers[] = $user->getId();
        } else {
            $manufacturers = $request->query->get('selectedManufacturers') ?? [];
        }

        $result = $this->reportsService->getVirtualExpoReportAll($user->getId(), $manufacturers, $dateFrom, $dateTo);

        return new JsonResponse($result);
    }

    public function virtualExportReport(int $id, Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Unknown user', Response::HTTP_FORBIDDEN);
        }

        /**
         * TODO: check permissions
         */
        $dateFrom = ($request->query->get('dateFrom')) ? Carbon::createFromFormat('d-m-Y', $request->query->get('dateFrom')) : null;
        $dateTo = ($request->query->get('dateTo')) ? Carbon::createFromFormat('d-m-Y', $request->query->get('dateTo')) : null;

        $result = $this->reportsService->getVirtualExpoReport($user->getId(), $id, $dateFrom, $dateTo);

        return new JsonResponse($result);
    }

    public function calledUsers(Request $request): JsonResponse
    {
        $merchantId = $request->query->get('merchantId');
        $searchParams = SearchParamsDto::createFromRequest($request);

        $response = $this->userDataProvider->getCalledUsers($searchParams, ($merchantId ? (int)$merchantId : null));

        return new JsonResponse($response);
    }

    public function buyerReportPdf(Request $request) : JsonResponse
    {
        $projectId = (int) $request->get('projectId', 0);
        $worksPackageId = (int) $request->get('worksPackageId', 0);

        $user = $this->userService->getCurrentUser();

        if (!$user) {
            return new JsonResponse('Unknown user', Response::HTTP_FORBIDDEN);
        }

        $data = $this->reportsService->getBuyerReport($user, $projectId, $worksPackageId);

        if (empty($data)) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        $prompt = "Please create an in depth summary to add value to sub contractors regarding a project report based on their material procurement options and what actually happened. This needs to add value for the sub contractor in understanding price, efficient and data insights. Remember we are also trying to showcase results based on the digital approach compared to the manual approach they currently use.";

        try {
            $openai = new OpenAIService();
            $response = $openai->getDataSummary($data, $prompt);
            $data['summary'] = !empty($response['choices'][0]['message']['content']) ? $response['choices'][0]['message']['content'] : '';

            $pdf = Pdf::loadView('reports/buyer_report', $data);
            $timestamp = Carbon::now()->timestamp;
            $filename = "buyer_report_{$timestamp}.pdf";
            $path = "/storage/reports/{$filename}";
            $url = Config::get('app.url') . $path;

            // for testing on local
            //$url = "http://localhost:8096/storage/reports/{$filename}";

            // store pdf on server and return a url
            Storage::disk('public')->put('reports/' . $filename, $pdf->output());
        } catch (\Exception $exception) {
            return new JsonResponse($exception->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // download the pdf (requires responseType: 'arraybuffer')
        //return $pdf->download('buyer_report.pdf');

        // return the html view
        //return view('reports/buyer_report', $data);

        return new JsonResponse(['url' => $url]);
    }

    public function buyerReportOptions(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();

        if (!$user) {
            return new JsonResponse('Unknown user', Response::HTTP_FORBIDDEN);
        }

        $quotes = Answer::query()->select('projects.id', 'projects.name')
            ->join('questions', 'questions.id', '=', 'answers.question_id')
            ->join('projects', 'projects.id', '=', 'questions.project_id')
            ->where([
                ['answers.user_id', '=', $user->getId()],
                ['answers.quote_accepted_at', '<>', null],
                ['projects.name', '<>', null],
                ['projects.name', '<>', '']
            ])->get();

        $quotes_sf = SupplyFitEnquiryQuote::query()->select('projects.id', 'projects.name')
            ->join('supply_fit_enquiries', 'supply_fit_enquiries.id', '=', 'supply_fit_enquiry_quotes.enquiry_id')
            ->join('projects', 'projects.id', '=', 'supply_fit_enquiries.project_id')
            ->where([
                ['supply_fit_enquiry_quotes.user_id', '=', $user->getId()],
                ['supply_fit_enquiry_quotes.quote_accepted_at', '<>', null],
                ['projects.name', '<>', null],
                ['projects.name', '<>', '']
            ])->get();

        $companyUsers = $user->getCompanyUsers();

        $projects = Project::query()->select('id', 'name')
            ->whereNull('archived_at')
            ->whereIn('user_id', $companyUsers)
            ->get();

        $options = $quotes->merge($quotes_sf)->merge($projects);

        return new JsonResponse($options);
    }

    public function contractorReport(Request $request): JsonResponse
    {
        $projectId = (int) $request->get('projectId', 0);
        $worksPackageId = (int) $request->get('worksPackageId', 0);

        $user = $this->userService->getCurrentUser();

        if (!$user) {
            return new JsonResponse('Unknown user', Response::HTTP_FORBIDDEN);
        }

        $data = $this->reportsService->getContractorReport($user, $projectId, $worksPackageId);

        if (empty($data)) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($data);
    }

    public function contractorReportPdf(Request $request): JsonResponse
    {
        $projectId = (int) $request->get('projectId', 0);
        $worksPackageId = (int) $request->get('worksPackageId', 0);

        $user = $this->userService->getCurrentUser();

        if (!$user) {
            return new JsonResponse('Unknown user', Response::HTTP_FORBIDDEN);
        }

        $data = $this->reportsService->getContractorReport($user, $projectId, $worksPackageId);

        if (empty($data)) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        try {
            $pdf = Pdf::loadView('reports/contractor_report', $data);
            $timestamp = Carbon::now()->timestamp;
            $filename = "contractor_report_{$timestamp}.pdf";
            $path = "/storage/reports/pdf/{$filename}";
            $url = Config::get('app.url') . $path;

            // for testing on local
            //$url = "http://localhost:8096/storage/reports/pdf/{$filename}";

            // store pdf on server and return a url
            Storage::disk('public')->put('reports/pdf/' . $filename, $pdf->output());
        } catch (\Exception $exception) {
            return new JsonResponse($exception->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['url' => $url]);
    }

    public function contractorReportWdPdf(Request $request): JsonResponse
    {
        $projectId = (int) $request->get('projectId', 0);
        $worksPackageId = (int) $request->get('worksPackageId', 0);
        $quotesMapImg = $request->get('quotesMapImg');
        $postcodeMapImg = $request->get('postcodeMapImg');

        $user = $this->userService->getCurrentUser();

        if (!$user) {
            return new JsonResponse('Unknown user', Response::HTTP_FORBIDDEN);
        }

        $data = $this->reportsService->getContractorReport($user, $projectId, $worksPackageId);
        $data['quotesMapImg'] = $quotesMapImg;
        $data['postcodeMapImg'] = $postcodeMapImg;

        if (empty($data)) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        try {
            $pdf = Pdf::loadView('reports/contractor_report_wd', $data);
            $timestamp = Carbon::now()->timestamp;
            $filename = "contractor_report_wd_{$timestamp}.pdf";
            $path = "/storage/reports/pdf/{$filename}";
            $url = Config::get('app.url') . $path;

            // for testing on local
            //$url = "http://localhost:8096/storage/reports/pdf/{$filename}";

            // store pdf on server and return a url
            Storage::disk('public')->put('reports/pdf/' . $filename, $pdf->output());
        } catch (\Exception $exception) {
            return new JsonResponse($exception->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['url' => $url]);
    }

    public function contractorReportCsv(Request $request) : Response
    {
        $projectId = (int) $request->get('projectId', 0);
        $worksPackageId = (int) $request->get('worksPackageId', 0);

        $user = $this->userService->getCurrentUser();

        if (!$user) {
            return new JsonResponse('Unknown user', Response::HTTP_FORBIDDEN);
        }

        $data = $this->reportsService->getContractorReport($user, $projectId, $worksPackageId);

        if (empty($data)) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        $searchParamsDto = new SearchParamsDto('id', true, false);
        $timeTracking = $this->projectTimeTrackingRepository->find($searchParamsDto, $projectId);

        try {
            if ($user->hasRole('contractor', 'framework', 'client' )) {
                unset($data['totals']['materials_spent']);
            } else if ($user->hasRole('user')) {
                unset($data['totals']['trades_spent']);
                unset($data['totals']['trades_esg']);
            }

            $totalsHeaders = array_keys($data['totals']);
            $totalsValues = array_values($data['totals']);

            array_push($totalsHeaders, 'min_quote_time', 'materials_average_esg', 'trades_average_esg', 'percent_used_live_chat');
            array_push($totalsValues, $data['min_quote_time'], $data['materials_average_esg'], $data['trades_average_esg'], $data['percent_used_live_chat']);

            $rows = [
                [
                    'title','company_name','project_name','works_package'
                ],
                [
                    $data['title'], $data['company_name'], $data['project_name'], $data['works_package']
                ],
                [],
                ['Totals'],
                $totalsHeaders,
                $totalsValues,
                [],
                ['Materials Quotes'],
            ];

            foreach($data['enquiries']['materials'] as $id => $quotes) {
                $rows[] = ['Enquiry ID: ' . $id];
                if ($user->hasRole('framework', 'client')) {
                    $rows[] = ['id', 'user_id', 'created_at', 'quote_accepted_at', 'distance', 'esgPerc', 'local_material_spend', 'category', 'description_of_goods'];
                    foreach($quotes as $quote) {
                        $rows[] = [
                            $quote['id'],
                            $quote['user_id'],
                            $quote['created_at'],
                            $quote['quote_accepted_at'],
                            number_format($quote['distance'], 2),
                            number_format($quote['esgPerc'], 2),
                            $quote['local_material_spend'],
                            $quote['product_name'],
                            $quote['description']
                        ];
                    }
                } else if ($user->hasRole('contractor')) {
                    $rows[] = ['id', 'user_id', 'created_at', 'quote_accepted_at', 'first_name', 'distance', 'esgPerc', 'local_material_spend', 'category', 'description_of_goods'];
                    foreach($quotes as $quote) {
                        $rows[] = [
                            $quote['id'],
                            $quote['user_id'],
                            $quote['created_at'],
                            $quote['quote_accepted_at'],
                            $quote['first_name'],
                            number_format($quote['distance'], 2),
                            number_format($quote['esgPerc'], 2),
                            $quote['local_material_spend'],
                            $quote['product_name'],
                            $quote['description']
                        ];
                    }
                } else {
                    $rows[] = ['id', 'user_id', 'price', 'created_at', 'quote_accepted_at', 'first_name', 'distance', 'esgPerc', 'local_material_spend', 'category', 'description_of_goods'];
                    foreach($quotes as $quote) {
                        $rows[] = [
                            $quote['id'],
                            $quote['user_id'],
                            $quote['price'],
                            $quote['created_at'],
                            $quote['quote_accepted_at'],
                            $quote['first_name'],
                            number_format($quote['distance'], 2),
                            number_format($quote['esgPerc'], 2),
                            $quote['local_material_spend'],
                            $quote['product_name'],
                            $quote['description']
                        ];
                    }
                }
            }

            if (!$user->hasRole('user')) {
                $rows[] = [];
                $rows[] = ['Trades Quotes'];

                foreach($data['enquiries']['trades'] as $id => $quotes) {
                    $rows[] = ['Enquiry ID: ' . $id];
                    $rows[] = ['id', 'user_id', 'price', 'created_at', 'quote_accepted_at', 'first_name', 'distance', 'esgPerc', 'category', 'description_of_goods'];
                    foreach($quotes as $quote) {
                        $rows[] = [
                            $quote['id'],
                            $quote['user_id'],
                            $quote['price'],
                            $quote['created_at'],
                            $quote['quote_accepted_at'],
                            $quote['first_name'],
                            number_format($quote['distance'], 2),
                            number_format($quote['esgPerc'], 2),
                            $quote['product_name'],
                            $quote['description']
                        ];
                    }
                }
            }

            $rows[] = [];
            $rows[] = ['Social E/Apprenticeships'];

            $rows[] = ['target_ap', 'target_se', 'total_ap', 'total_se'];
            $rows[] = [
                $data['project_targets']['target_hours_ap'],
                $data['project_targets']['target_hours_se'],
                $timeTracking['totals']['total_hours_ap'],
                $timeTracking['totals']['total_hours_se']
            ];

            $rows[] = [];
            $rows[] = ['id', 'hours_ap', 'hours_se', 'created_by', 'created_at'];

            foreach($timeTracking['items'] as $item) {
                $rows[] = [$item['id'], $item['hours_ap'], $item['hours_se'], $item['first_name'], $item['created_at']];
            }

            $timestamp = Carbon::now()->timestamp;
            $filename = "contractor_report_{$timestamp}.csv";
            $dir = storage_path() . "/app/public/reports/csv/";
            $path = $dir . $filename;

            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }

            $csv = fopen($path, 'w');

            foreach ($rows as $row) {
                fputcsv($csv, $row);
            }

            fclose($csv);
        } catch (\Exception $exception) {
            return new JsonResponse($exception->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $headers = [
            'Content-Type' => 'text/csv',
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        return response()->download($path, 'file.csv', $headers);
    }

    public function contractorMapData(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();

        if (!$user) {
            return new JsonResponse('Unknown user', Response::HTTP_FORBIDDEN);
        }

        $radius = (int) $request->get('radius', 0);
        $type = $request->get('type', []);
        $projectId = (int) $request->get('projectId', 0);
        $worksPackageIds = $request->get('worksPackageIds', []);

        if (empty($projectId)) {
            return new JsonResponse('Bad Request: Please select a project', Response::HTTP_BAD_REQUEST);
        }

        $data = $this->reportsService->getContractorMapData($radius, $type, $projectId, $worksPackageIds, $user);

        return new JsonResponse($data);
    }

    public function getSubcontractorReport(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();

        if (!$user) {
            return new JsonResponse('Not authorised', Response::HTTP_FORBIDDEN);
        }

        $subcontractorIds = $request->get('user_ids', []);

        $data = $this->reportsService->getSubcontractorReport($user, $subcontractorIds);

        return new JsonResponse($data);
    }

    public function getUserSummary(Request $request): JsonResponse
    {
        $currentUser = $this->userService->getCurrentUser();

        if (!$currentUser) {
            return new JsonResponse('Not authorised', Response::HTTP_FORBIDDEN);
        }

        $id = $request->get('id');
        $user = $this->userService->getById((int) $id);

        if (!$user) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        $data = $this->reportsService->getUserSummary($user);

        return new JsonResponse($data);
    }
}
