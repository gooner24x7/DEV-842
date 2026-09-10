<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataProvider\AnswerDataProvider;
use App\DataProvider\LogisticsEnquiryDataProvider;
use App\DataProvider\LogisticsQuoteDataProvider;
use App\DataProvider\MessageDataProvider;
use App\DataProvider\QuestionDataProvider;
use App\DataProvider\SupplyFitEnquiryDataProvider;
use App\DataProvider\SupplyFitEnquiryQuoteDataProvider;
use App\DataProvider\VirtualExpoDataProvider;
use App\DataProvider\VirtualExpoSharedContactDataProvider;
use App\Dto\Message\MessageDto;
use App\Dto\Message\SearchParamsDto;
use App\Exceptions\InvalidRequestException;
use App\Mail\NewMessage;
use App\Service\UserService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;

class MessagesController extends Controller
{
    const string MESSAGE_TYPE_ANSWER = 'answers';
    const string MESSAGE_TYPE_VIRTUAL_EXPO_SHARED_CONTACT = 'virtual-expo-contacts';
    const string MESSAGE_TYPE_SUPPLY_FIT_ENQUIRY_QUOTE = 'supply-fit-enquiry-quotes';
    const string MESSAGE_TYPE_QUESTION = 'questions';
    const string MESSAGE_TYPE_SUPPLY_FIT_ENQUIRY = 'supply-fit-enquiries';
    const string MESSAGE_TYPE_LOGISTICS_ENQUIRY = 'logistics-enquiries';
    const string MESSAGE_TYPE_LOGISTICS_QUOTE = 'logistics-quotes';

    private MessageDataProvider $messageDataProvider;
    private UserService $userService;
    private AnswerDataProvider $answerDataProvider;
    private QuestionDataProvider $questionDataProvider;
    private SupplyFitEnquiryDataProvider $supplyFitEnquiryDataProvider;
    private SupplyFitEnquiryQuoteDataProvider $supplyFitEnquiryQuoteDataProvider;
    private VirtualExpoSharedContactDataProvider $virtualExpoSharedContactDataProvider;
    private VirtualExpoDataProvider $virtualExpoDataProvider;
    private LogisticsEnquiryDataProvider $logisticsEnquiryDataProvider;
    private LogisticsQuoteDataProvider $logisticsQuoteDataProvider;

    public function __construct(
        MessageDataProvider                  $messageDataProvider,
        UserService                          $userService,
        AnswerDataProvider                   $answerDataProvider,
        QuestionDataProvider                 $questionDataProvider,
        SupplyFitEnquiryDataProvider         $supplyFitEnquiryDataProvider,
        SupplyFitEnquiryQuoteDataProvider    $supplyFitEnquiryQuoteDataProvider,
        VirtualExpoSharedContactDataProvider $virtualExpoSharedContactDataProvider,
        VirtualExpoDataProvider              $virtualExpoDataProvider,
        LogisticsEnquiryDataProvider         $logisticsEnquiryDataProvider,
        LogisticsQuoteDataProvider           $logisticsQuoteDataProvider
    )
    {
        $this->messageDataProvider = $messageDataProvider;
        $this->userService = $userService;
        $this->answerDataProvider = $answerDataProvider;
        $this->questionDataProvider = $questionDataProvider;
        $this->supplyFitEnquiryDataProvider = $supplyFitEnquiryDataProvider;
        $this->supplyFitEnquiryQuoteDataProvider = $supplyFitEnquiryQuoteDataProvider;
        $this->virtualExpoDataProvider = $virtualExpoDataProvider;
        $this->virtualExpoSharedContactDataProvider = $virtualExpoSharedContactDataProvider;
        $this->logisticsEnquiryDataProvider = $logisticsEnquiryDataProvider;
        $this->logisticsQuoteDataProvider = $logisticsQuoteDataProvider;
    }

    public function chatList(Request $request, string $type = self::MESSAGE_TYPE_ANSWER, int $id = 0): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $requestDto = SearchParamsDto::createFromRequest($request);
        $requestDto->setUserId($user->getId());

        switch ($type) {
            case self::MESSAGE_TYPE_QUESTION:
                $requestDto->setQuestionId($id);
                break;
            case self::MESSAGE_TYPE_SUPPLY_FIT_ENQUIRY:
                $requestDto->setSupplyFitEnquiryId($id);
                break;
            case self::MESSAGE_TYPE_ANSWER:
                $requestDto->setAid($id);
                break;
            case self::MESSAGE_TYPE_VIRTUAL_EXPO_SHARED_CONTACT:
                $requestDto->setVirtualExpoSharedContactId($id);
                break;
            case self::MESSAGE_TYPE_SUPPLY_FIT_ENQUIRY_QUOTE:
                $requestDto->setSupplyFitEnquiryQuote($id);
                break;
            case self::MESSAGE_TYPE_LOGISTICS_ENQUIRY:
                $requestDto->setLogisticsEnquiryId($id);
                break;
            case self::MESSAGE_TYPE_LOGISTICS_QUOTE:
                $requestDto->setLogisticsQuoteId($id);
        }

        return new JsonResponse($this->messageDataProvider->chatList($requestDto, $type));
    }

    /**
     * @throws \RedisException
     */
    public function index(Request $request, string $type = self::MESSAGE_TYPE_ANSWER, int $id = 0): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $requestDto = SearchParamsDto::createFromRequest($request);
        $requestDto->setUserId($user->getId());

        switch ($type) {
            case self::MESSAGE_TYPE_QUESTION:
                $requestDto->setQuestionId($id);
                break;
            case self::MESSAGE_TYPE_SUPPLY_FIT_ENQUIRY:
                $requestDto->setSupplyFitEnquiryId($id);
                break;
            case self::MESSAGE_TYPE_ANSWER:
                $requestDto->setAid($id);
                break;
            case self::MESSAGE_TYPE_VIRTUAL_EXPO_SHARED_CONTACT:
                $requestDto->setVirtualExpoSharedContactId($id);
                break;
            case self::MESSAGE_TYPE_SUPPLY_FIT_ENQUIRY_QUOTE:
                $requestDto->setSupplyFitEnquiryQuote($id);
                break;
            case self::MESSAGE_TYPE_LOGISTICS_ENQUIRY:
                $requestDto->setLogisticsEnquiryId($id);
                break;
            case self::MESSAGE_TYPE_LOGISTICS_QUOTE:
                $requestDto->setLogisticsQuoteId($id);
        }

        $messages = $this->messageDataProvider->find($requestDto, false);

        $currentUser = $this->userService->getCurrentUser();

        foreach ($messages as $message) {
            if ($message->user_id !== $currentUser->id && $message->viewed_at === null) {
                $message->viewed_at = Carbon::now();
                $message->save();
            }
        }

        return new JsonResponse($messages);
    }

    public function store(Request $request, string $type = self::MESSAGE_TYPE_ANSWER, int $id = 0): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        try {
            $dto = MessageDto::createFromRequest($request);
            $dto->setUserId($user->getId());

            if ($request->get('interlocutorId')) {
                $dto->setInterlocutorId($request->get('interlocutorId'));
            }

            switch ($type) {
                case self::MESSAGE_TYPE_QUESTION:
                    $dto->setQuestionId($id);
                    break;
                case self::MESSAGE_TYPE_SUPPLY_FIT_ENQUIRY:
                    $dto->setSupplyFitEnquiryId($id);
                    break;
                case self::MESSAGE_TYPE_ANSWER:
                    $answer = $this->answerDataProvider->getById($id);
                    $question = $this->questionDataProvider->get($answer->question_id);

                    $dto->setInterlocutorId($user->getId() === $answer->getUserId() ? $question->getUserId() : $answer->getUserId());
                    $dto->setAid($id);
                    break;
                case self::MESSAGE_TYPE_VIRTUAL_EXPO_SHARED_CONTACT:
                    $sharedContact = $this->virtualExpoSharedContactDataProvider->getById($id);
                    $expo = $this->virtualExpoDataProvider->getById($sharedContact->getVirtualExpoId());

                    $dto->setInterlocutorId($user->getId() == $expo->user_id ? $sharedContact->getUserId() : $expo->user_id);

                    $dto->setVirtualExpoSharedContactId($id);
                    break;
                case self::MESSAGE_TYPE_SUPPLY_FIT_ENQUIRY_QUOTE:
                    $answer = $this->supplyFitEnquiryQuoteDataProvider->getById($id);
                    $question = $this->supplyFitEnquiryDataProvider->get($answer->enquiry_id);

                    $dto->setInterlocutorId($user->getId() === $answer->getUserId() ? $question->getUserId() : $answer->getUserId());

                    $dto->setSupplyFitEnquiryQuote($id);
                    break;
                case self::MESSAGE_TYPE_LOGISTICS_ENQUIRY:
                    $dto->setLogisticsEnquiryId($id);
                    break;
                case self::MESSAGE_TYPE_LOGISTICS_QUOTE:
                    $quote = $this->logisticsQuoteDataProvider->getById($id);
                    $enquiry = $this->logisticsEnquiryDataProvider->get($quote->enquiry_id);

                    $dto->setInterlocutorId($user->getId() === $quote->getUserId() ? $enquiry->getUserId() : $quote->getUserId());

                    $dto->setLogisticsQuoteId($id);
            }

            $message = $this->messageDataProvider->store($dto);
            if ($message->getInterlocutorId() && $user = $this->userService->getById($message->getInterlocutorId())) {
                Mail::to($user->getEmail())->send(new NewMessage($user, $message));
            }

            return new JsonResponse($message);
        } catch (InvalidRequestException $e) {
            return new JsonResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (\RedisException $e) {
            return new JsonResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
