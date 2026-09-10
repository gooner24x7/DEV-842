<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::group([
    'prefix' => 'auth',
    'cors',
], function () {
    Route::post('password-reset', 'AuthController@resetPassword');
    Route::post('login', 'AuthController@login');
    Route::post('refresh', 'AuthController@refresh');
    Route::post('logout', 'AuthController@logout');
    Route::post('me', 'AuthController@me');
    Route::post('register', 'AuthController@register');
});

Route::middleware(['auth:api', 'cors'])->post('/loginas', 'AuthController@loginAs');

Route::middleware(['auth:api', 'cors'])->group(function () {
    Route::post('/profile', 'AuthController@saveProfile');
    Route::get('/roles', 'RolesController@index');
    Route::post('/roles', 'RolesController@store');
    Route::post('/roles/{roleId}', 'RolesController@update');
    Route::get('/permissions', 'RolesController@getPermissions');
    Route::post('/permissions/{roleId}', 'RolesController@updateRolePermissions');

    Route::get('/billing_users/{id}/branches', 'UsersController@billingUserBranches');
    Route::get('/users', 'UsersController@index');
    Route::post('/users', 'UsersController@store');
    Route::put('/users/{id}', 'UsersController@update');
    Route::put('/users/{id}/updateCsp', 'UsersController@updateCsp');
    Route::delete('/users/{id}', 'UsersController@delete');
    Route::get('/users/role/{roleSlug}', 'UsersController@searchByRole');
    Route::get('/users/{id}', 'UsersController@get');
    Route::get('/creditsafe', 'UsersController@getCreditsafeInfo');
    Route::get('/updateQuestionnaireSessions', 'UsersController@updateQuestionnaireSessions');

    Route::get('/certificates', 'CertificatesController@get');
    Route::get('/certificates/types', 'CertificatesController@getTypes');
    Route::get('/certificates/stats/dashboard', 'CertificatesController@getDashboardStats');
    Route::get('/certificates/stats', 'CertificatesController@getStats');

    Route::get('/manufacturers/{id}/products', 'UsersController@getManufacturerProducts');
    Route::get('/manufacturers/byProductId/{id}', 'UsersController@getManufacturerByProductId');
    Route::get('/user/{id}/supplierReport', 'ReportsController@supplierReport');
    Route::get('/user/logisticsReport', 'ReportsController@logisticsReport');

    Route::get('/products', 'ProductsController@index');
    Route::post('/products', 'ProductsController@store');
    Route::put('/products/{id}', 'ProductsController@update');
    Route::delete('/products/{id}', 'ProductsController@delete');

    Route::get('/questions/works-package-options', 'QuestionsController@worksPackageOptions');
    Route::get('/questions/project-options', 'QuestionsController@projectOptions');

    Route::get('/inquiries/fav', 'QuestionsController@favouriteInquiries');
    Route::get('/questions', 'QuestionsController@index');
    Route::get('/questions/{id}', 'QuestionsController@get');
    Route::post('/questions', 'QuestionsController@store');
    Route::put('/questions/{id}', 'QuestionsController@update');
    Route::delete('/questions/{id}', 'QuestionsController@delete');
    Route::post('/questions/batch', 'QuestionsController@storeBatch');
    Route::post('/questions/storeByHouseName', 'QuestionsController@storeByHouseName');
    Route::post('/questions/{id}/fav', 'QuestionsController@addToFav');
    Route::delete('/questions/{id}/fav', 'QuestionsController@removeFav');
    Route::post('/questions/{id}/archive', 'QuestionsController@archive');
    Route::post('/questions/{id}/restore', 'QuestionsController@restore');
    Route::post('/questions/{id}/duplicate', 'QuestionsController@duplicate');
    Route::post('/questions/{id}/toggle-assign', 'QuestionsController@toggleAssign');
    Route::post('/questions/{id}/toggle-ignore', 'QuestionsController@toggleIgnore');
    Route::post('/questions/{id}/nudgeSales', 'QuestionsController@nudgeSales');

    Route::get('/questions/{qid}/seen-by-merchants', 'QuestionsController@seenByMerchants');
    Route::get('/questions/{qid}/merchants-preferred', 'QuestionsController@getPreferredSuppliers');

    Route::post('/questions/{qid}/called', 'QuestionsController@setCalled');
    Route::post('/users/{uid}/called', 'UsersController@called');
    Route::post('/users/{uid}/updateOnboardingChecklist', 'UsersController@updateOnboardingChecklist');
    Route::get('/users/{uid}/getOnboardingChecklist', 'UsersController@getOnboardingChecklist');

    Route::get('/onboarding/users', 'ContractorOnboardingController@getUsers');
    Route::post('/onboarding/users', 'ContractorOnboardingController@createUser');
    Route::put('/onboarding/users/{id}', 'ContractorOnboardingController@updateUser');
    Route::delete('/onboarding/users/{id}', 'ContractorOnboardingController@deleteUser');
    Route::get('/onboarding/get', 'ContractorOnboardingController@get');
    Route::get('/onboarding', 'ContractorOnboardingController@index');
    Route::post('/onboarding', 'ContractorOnboardingController@store');

    Route::get('/questions/{qid}/answers', 'AnswersController@index');
    Route::post('/questions/{qid}/answers', 'AnswersController@store');
    Route::get('/questions/{qid}/answers/export', 'AnswersController@export');
    Route::get('/questions/{qid}/answers/export-comparison', 'AnswersController@exportComparisonActivityTracker');
    Route::get('/answers/export-comparison', 'AnswersController@exportComparison');

    Route::get('/supply-fit-enquiries/project-options', 'SupplyFitEnquiriesController@projectOptions');
    Route::get('/supply-fit-enquiries/project-options-archived', 'SupplyFitEnquiriesController@projectOptionsArchived');
    Route::get('/supply-fit-enquiries/works-package-options', 'SupplyFitEnquiriesController@worksPackageOptions');

    Route::get('/supply-fit-enquiries', 'SupplyFitEnquiriesController@index');
    Route::get('/supply-fit-enquiries/{id}', 'SupplyFitEnquiriesController@get')->where('id', '[0-9]+');
    Route::post('/supply-fit-enquiries', 'SupplyFitEnquiriesController@store');
    Route::post('/supply-fit-enquiries/{id}', 'SupplyFitEnquiriesController@update');
    Route::delete('/supply-fit-enquiries/{id}', 'SupplyFitEnquiriesController@delete');
    Route::post('/supply-fit-enquiries/{id}/archive', 'SupplyFitEnquiriesController@archive');
    Route::post('/supply-fit-enquiries/{id}/restore', 'SupplyFitEnquiriesController@restore');
    Route::post('/supply-fit-enquiries/{id}/duplicate', 'SupplyFitEnquiriesController@duplicate');

    Route::get('/supply-fit-enquiries/{enquiryId}/quotes', 'SupplyFitEnquiryQuotesController@index');
    Route::post('/supply-fit-enquiries/{enquiryId}/quotes', 'SupplyFitEnquiryQuotesController@store');
    Route::put('/supply-fit-enquiry-quotes/{id}', 'SupplyFitEnquiryQuotesController@update');
    Route::delete('/supply-fit-enquiry-quotes/{id}', 'SupplyFitEnquiryQuotesController@delete');
    Route::post('/supply-fit-enquiry-quotes/{id}/accept', 'SupplyFitEnquiryQuotesController@accept');
    Route::post('/supply-fit-enquiry-quotes/{id}/unaccept', 'SupplyFitEnquiryQuotesController@unaccept');
    Route::patch('/supply-fit-enquiry-quotes/{id}', 'SupplyFitEnquiryQuotesController@partialUpdate');
    Route::post('/supply-fit-enquiry-quotes/{id}/resend-questionnaire', 'SupplyFitEnquiryQuotesController@resendQuestionnaire');

    Route::put('/answers/{id}', 'AnswersController@update');
    Route::delete('/answers/{id}', 'AnswersController@delete');
    Route::post('/answers/{id}/accept', 'AnswersController@accept');
    Route::post('/answers/{id}/unaccept', 'AnswersController@unaccept');
    Route::post('/answers/{id}/checked', 'AnswersController@setCheckedAt');
    Route::patch('/answers/{id}', 'AnswersController@partialUpdate');

    Route::get('/partners', 'PartnersController@index');

    Route::get('/stripe/plans', 'SubscriptionsController@stripePlansAvailable');
    Route::get('/stripe/user/{id}', 'SubscriptionsController@userStripePlan');

    Route::get('/user/{userId}/notes', 'UserNotesController@index');
    Route::post('/user/{userId}/notes', 'UserNotesController@store');

    Route::post('/users/preferred-suppliers/{userId}', 'PreferredSuppliersController@store');
    Route::get('/users/preferred-suppliers/options', 'PreferredSuppliersController@options');
    Route::get('/users/preferred-suppliers/{userId}', 'PreferredSuppliersController@index');
    Route::delete('/users/preferred-suppliers/{userId}/{supplierId}', 'PreferredSuppliersController@delete');

    Route::post('/users/preferred-subcontractors/{userId}', 'PreferredSubcontractorsController@store');
    Route::get('/users/preferred-subcontractors/options', 'PreferredSubcontractorsController@options');
    Route::get('/users/preferred-subcontractors/{userId}', 'PreferredSubcontractorsController@index');
    Route::delete('/users/preferred-subcontractors/{userId}/{subcontractorId}', 'PreferredSubcontractorsController@delete');

    Route::get('/reports/total_enquiries', 'ReportsController@totalEnquiries');
    Route::get('/reports/total_quotes', 'ReportsController@totalQuotes');
    Route::get('/reports/branch_performance', 'ReportsController@branchPerformance');
    Route::get('/reports/totals_contractor_on_enquiries', 'ReportsController@totalsContractorOnEnquiries');
    Route::get('/reports/totals_supplier_on_enquiries', 'ReportsController@totalsSupplierOnEnquiries');
    Route::get('/reports/categories_percentage_selected_for_contractor', 'ReportsController@categoriesPercentageSelectedForContractor');
    Route::get('/reports/categories_percentage_selected_for_merchant', 'ReportsController@categoriesPercentageSelectedForMerchant');
    Route::get('/reports/enquiries_to_time_merchant', 'ReportsController@enquiriesToTimeMerchant');
    Route::get('/reports/enquiries_to_time_contractor', 'ReportsController@enquiriesToTimeContractor');
    Route::get('/reports/called_users', 'ReportsController@calledUsers');
    Route::get('/reports/buyer_report', 'ReportsController@buyerReport');
    Route::get('/reports/buyer_report/options', 'ReportsController@buyerReportOptions');
    Route::get('/reports/contractor_report', 'ReportsController@contractorReport');
    Route::get('/reports/contractor_report_pdf', 'ReportsController@contractorReportPdf');
    Route::post('/reports/contractor_report_wd_pdf', 'ReportsController@contractorReportWdPdf');
    Route::get('/reports/contractor_map_data', 'ReportsController@contractorMapData');
    Route::get('/reports/subcontractor_report', 'ReportsController@getSubcontractorReport');
    Route::get('/reports/user_summary', 'ReportsController@getUserSummary');

    Route::get('/categories/options', 'CategoriesController@options');
    Route::get('/categories', 'CategoriesController@index');
    Route::post('/categories', 'CategoriesController@store');
    Route::put('/categories/{id}', 'CategoriesController@update');
    Route::delete('/categories/{id}', 'CategoriesController@delete');

    Route::post('/tickets', 'ZohoDeskController@create');
    Route::get('/tickets/categories', 'ZohoDeskController@categoriesOptions');
    Route::get('/zoho-report-settings', 'ZohoCrmController@getDefaultValues');
    Route::get('/zoho-deals', 'ZohoCrmController@getDeals');
    Route::post('/zoho-deals', 'ZohoCrmController@updateDeal');

    Route::get('/tutorials', 'TutorialsController@index');
    Route::post('/tutorials', 'TutorialsController@store');
    Route::put('/tutorials/{id}', 'TutorialsController@update');
    Route::delete('/tutorials/{id}', 'TutorialsController@delete');

    Route::get('/virtual-expo', 'VirtualExpoController@index');
    Route::post('/virtual-expo', 'VirtualExpoController@store');
    Route::get('/virtual-expo/{id}', 'VirtualExpoController@get');
    Route::post('/virtual-expo/{id}', 'VirtualExpoController@update');
    Route::post('/virtual-expo/{id}/updateCheckbox', 'VirtualExpoController@updateCheckbox');
    Route::delete('/virtual-expo/{id}', 'VirtualExpoController@delete');
    Route::get('/virtual-expo-random', 'VirtualExpoController@getRandom');
    Route::get('/virtual-expo-list', 'VirtualExpoController@getList');
    Route::get('/virtual-expo/{id}/videos', 'VirtualExpoVideoController@index');

    Route::get('/virtual-expo-contacts', 'VirtualExpoSharedContactController@index');
    Route::post('/virtual-expo-contacts', 'VirtualExpoSharedContactController@store');
    Route::delete('/virtual-expo-contacts/{id}', 'VirtualExpoSharedContactController@delete');

    Route::get('/{type}/{id}/chats', 'MessagesController@chatList');
    Route::get('/{type}/{id}/messages', 'MessagesController@index');
    Route::post('/{type}/{id}/messages', 'MessagesController@store');

    Route::post('/settings', 'SettingsController@store');

    Route::post('/users/{id}/import', 'UsersController@importCsvForBillingUser');

    Route::post('/analytics/events', 'AnalyticsController@newEvent');

    Route::get('/supply-fit-enquiries/{id}/overview', 'SupplyFitEnquiryQuotesController@overview');

    Route::get('/questionnaire-items/{id}', 'QuestionnaireItemsController@get');
    Route::post('/questionnaire-items', 'QuestionnaireItemsController@store');
    Route::put('/questionnaire-items/{id}', 'QuestionnaireItemsController@update');
    Route::delete('/questionnaire-items/{id}', 'QuestionnaireItemsController@delete');
    Route::get('/questionnaire/hash', 'QuestionnaireSessionsController@getHash');

    Route::get('/company_questionnaires', 'QuestionnaireSessionsController@index');
    Route::post('/company_questionnaires', 'QuestionnaireSessionsController@store');
    Route::put('/company_questionnaires/{id}', 'QuestionnaireSessionsController@update');
    Route::delete('/company_questionnaires/{id}', 'QuestionnaireSessionsController@delete');
    Route::get('/company_questionnaires/{id}', 'QuestionnaireSessionsController@get');

    Route::post('/company_questionnaires/scores', 'QuestionnaireSessionsController@getScores');
    Route::post('/company_questionnaires/answersWithScores', 'QuestionnaireSessionsController@getAnswersWithScores');

    Route::post('/company_questionnaires/{id}/accept', 'QuestionnaireSessionsController@accept');
    Route::post('/company_questionnaires/{id}/decline', 'QuestionnaireSessionsController@decline');

    Route::post('/answers/{id}/score', 'QuestionnaireSessionsController@setScore');

    Route::get('/projects', 'ProjectsController@index');
    Route::post('/projects', 'ProjectsController@store');
    Route::post('/projects/{id}/archive', 'ProjectsController@archive');
    Route::post('/projects/{id}/restore', 'ProjectsController@restore');
    Route::post('/projects/{id}/updateStage', 'ProjectsController@updateStage');
    Route::post('/projects/{id}/updateType', 'ProjectsController@updateType');
    Route::post('/projects/{id}/sendToMerchants', 'ProjectsController@send');
    Route::post('/projects/{id}/works-packages', 'WorksPackagesController@create');
    Route::get('/projects/{id}/works-packages', 'WorksPackagesController@index');
    Route::post('/projects/{id}/works-packages/{worksPackageId}/sendToMerchants', 'WorksPackagesController@send');
    Route::post('/projects/{id}/works-packages/{worksPackageId}/questionnaire-templates', 'TemplatesController@create');
    Route::post('/projects/{id}/works-packages/{worksPackageId}/applyTemplate', 'TemplatesController@applyTemplate');
    Route::get('/projects/{id}/report', 'ProjectsController@getReport');

    Route::get('/projects/regions', 'ProjectsController@getRegions');
    Route::get('/projects/options', 'ProjectsController@options');
    Route::get('/works-packages/options', 'WorksPackagesController@options');
    Route::post('/works-packages/{id}', 'WorksPackagesController@update');
    Route::delete('/works-packages/{id}', 'WorksPackagesController@delete');
    Route::get('/works-packages/{id}/assigned-users', 'WorksPackagesController@getAssignedUsers');
    Route::post('/works-packages/{id}/assigned-users', 'WorksPackagesController@storeAssignedUsers');

    Route::get('/works-packages/questions', 'WorksPackageQuestionsController@index');
    Route::post('/works-packages/{id}/questions', 'WorksPackageQuestionsController@store');

    Route::get('/projects/{id}/getProjectUsers', 'ProjectsController@getProjectUsers');
    Route::get('/projects/getUsers', 'ProjectsController@getUsers');
    Route::post('/projects/{id}/userAccess', 'ProjectsController@createUserAccess');
    Route::delete('projects/{id}/userAccess/{userId}', 'ProjectsController@deleteUserAccess');
    Route::get('/projects/{id}/supply-fit-quotes', 'ProjectsController@getSupplyFitQuotes');

    Route::get('/projects/documents/categories', 'ProjectDocumentsController@getCategories');
    Route::get('/projects/{id}/documents', 'ProjectDocumentsController@index');
    Route::post('/projects/{id}/documents', 'ProjectDocumentsController@create');
    Route::delete('/projects/{id}/documents', 'ProjectDocumentsController@delete');
    Route::post('/projects/upload-boq-file', 'ProjectsController@uploadBoqFile');
    Route::post('/projects/create-works-packages', 'ProjectsController@createWorksPackages');

    Route::get('/projects/{id}/time-tracking', 'ProjectTimeTrackingController@index');
    Route::post('/projects/{id}/time-tracking', 'ProjectTimeTrackingController@store');

    Route::get('/virtual_expos/{id}/report', 'ReportsController@virtualExportReport');
    Route::get('/virtual_expos/report', 'ReportsController@virtualExportReportForAll');

    Route::get('/stats/contractor', 'UsersController@contractorsStats');
    Route::get('/stats/merchant', 'UsersController@merchantsStats');
    Route::get('/stats/manufacturer', 'UsersController@manufacturersStats');
    Route::get('/questionnaire-templates', 'TemplatesController@options');

    Route::get('/templates', 'TemplatesController@index');
    Route::delete('/templates/{id}', 'TemplatesController@delete');
    Route::post('/templates/{id}', 'TemplatesController@update');

    Route::post('/templates', 'TemplatesController@newTemplate');
    Route::get('/other-merchants/findPlace', 'OtherMerchantsController@findPlace');
    Route::post('/other-merchants/update', 'OtherMerchantsController@update');

    Route::get('/logistics-enquiries/project-options', 'LogisticsEnquiriesController@projectOptions');
    Route::get('/logistics-enquiries/works-package-options', 'LogisticsEnquiriesController@worksPackageOptions');

    Route::get('/logistics-enquiries', 'LogisticsEnquiriesController@index');
    Route::get('/logistics-enquiries/{id}', 'LogisticsEnquiriesController@get');
    Route::post('/logistics-enquiries', 'LogisticsEnquiriesController@store');
    Route::post('/logistics-enquiries/{id}', 'LogisticsEnquiriesController@update');
    Route::delete('/logistics-enquiries/{id}', 'LogisticsEnquiriesController@delete');
    Route::post('/logistics-enquiries/{id}/archive', 'LogisticsEnquiriesController@archive');
    Route::post('/logistics-enquiries/{id}/restore', 'LogisticsEnquiriesController@restore');
    Route::post('/logistics-enquiries/{id}/toggle-ignore', 'LogisticsEnquiriesController@toggleIgnore');

    Route::get('/logistics-enquiries/{id}/quotes', 'LogisticsQuotesController@index');
    Route::post('/logistics-enquiries/{id}/quotes', 'LogisticsQuotesController@store');
    Route::post('/logistics-quotes/{id}', 'LogisticsQuotesController@update');
    Route::delete('/logistics-quotes/{id}', 'LogisticsQuotesController@delete');
    Route::post('/logistics-quotes/{id}/accept', 'LogisticsQuotesController@accept');
    Route::post('/logistics-quotes/{id}/unaccept', 'LogisticsQuotesController@unaccept');

    Route::get('/quote-progress/{quoteId}/{type}', 'QuoteProgressController@index');
    Route::post('/quote-progress', 'QuoteProgressController@store');
    Route::post('/quote-progress/{id}/complete', 'QuoteProgressController@complete');
    Route::post('/quote-progress/{id}/uncomplete', 'QuoteProgressController@uncomplete');

    Route::get('/notes/{type}/{parentId}', 'NotesController@index');
    Route::post('/notes', 'NotesController@store');
    Route::patch('/notes', 'NotesController@update');

    Route::get('/housebuilding', 'HousebuildingController@index');
    Route::get('/housebuilding/houses', 'HousebuildingController@getHouseNames');
    Route::get('/housebuilding/categories', 'HousebuildingController@getCategories');
    Route::get('/housebuilding/totals', 'HousebuildingController@getTotals');
    Route::post('/housebuilding/budgets', 'HousebuildingController@updateBudget');

    Route::get('/supply-chain-users', 'SupplyChainUsersController@index');
    Route::post('/supply-chain-users', 'SupplyChainUsersController@store');
    Route::post('/supply-chain-users/{id}/update', 'SupplyChainUsersController@update');
    Route::post('/supply-chain-users/importCsv', 'SupplyChainUsersController@importCsv');
    Route::delete('/supply-chain-users/{id}', 'SupplyChainUsersController@delete');
    Route::get('/supply-chain-users/companies', 'SupplyChainUsersController@getCompanyNames');
    Route::post('/supply-chain-users/convert', 'SupplyChainUsersController@convert');
    Route::get('/supply-chain-users/subcontractors', 'SupplyChainUsersController@getSubcontractors');

    Route::get('/tender-notices', 'TenderNoticesController@index');
    Route::get('/tender-notices/{id}', 'TenderNoticesController@get');
    Route::post('/tender-notices/convert', 'TenderNoticesController@convert');

    Route::get('/attachments', 'AttachmentsController@index');
    Route::post('/attachments', 'AttachmentsController@store');
    Route::delete('/attachments/{id}', 'AttachmentsController@delete');

    Route::get('/reviews/templates/options', 'ReviewTemplatesController@options');
    Route::get('/reviews/templates', 'ReviewTemplatesController@index');
    Route::get('/reviews/templates/{id}', 'ReviewTemplatesController@get');
    Route::post('/reviews/templates', 'ReviewTemplatesController@store');
    Route::delete('/reviews/templates/{id}', 'ReviewTemplatesController@delete');

    Route::get('/reviews', 'ReviewsController@index');
    Route::get('/reviews/{id}', 'ReviewsController@get');
    Route::post('/reviews', 'ReviewsController@store');
    Route::delete('/reviews/{id}', 'ReviewsController@delete');
});

Route::get('/credit-safe-report/{id}', 'CreditSafeController@getReportForCompany');

Route::get('/categories/tree', 'CategoriesController@tree');
Route::get('/downloadFile', 'UploadsController@download');
Route::post('/v1/crm/publish-organisation', 'CrmController@publishOrganisation');
Route::get('/product-options', 'ProductsController@options');
Route::get('/nationals-options', 'QuestionsController@getNationalsOptions');

Route::get('/questionnaire', 'QuestionnaireItemsController@index');
Route::get('/questionnaire-items', 'QuestionnaireItemsController@index');

Route::get('/companyByHash/{hash}', 'QuestionnaireSessionsController@byHash')->where('hash', '(.+)');
Route::post('/companyByHash/{hash}/answers', 'QuestionnaireSessionsController@byHashSubmit')->where('hash', '(.+)');

Route::get('/externalAction/getEnquiryData', 'ExternalActionController@getEnquiryData');
Route::get('/externalAction/customerResponse', 'ExternalActionController@customerResponse');
Route::post('/externalAction/updateEnquiry', 'ExternalActionController@updateEnquiry');
Route::post('/externalAction/acceptQuote', 'ExternalActionController@acceptQuote');

Route::get('/reports/contractor_report_csv', 'ReportsController@contractorReportCsv');
