<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataProvider\AnswerDataProvider;
use App\DataProvider\QuestionDataProvider;
use App\Models\Answer;
use App\Models\QuestionAccessToken;
use App\Models\QuestionActionLog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExternalActionController extends Controller
{
    private QuestionDataProvider $questionDataProvider;
    private AnswerDataProvider $answerDataProvider;

    public function __construct(QuestionDataProvider $questionDataProvider, AnswerDataProvider $answerDataProvider)
    {
        $this->questionDataProvider = $questionDataProvider;
        $this->answerDataProvider = $answerDataProvider;
    }

    public function getEnquiryData(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => 'required',
            'token' => 'required',
        ]);

        $id = (int) $data['id'] ?? null;

        $token = QuestionAccessToken::where(['question_id' => $id, 'token' => $data['token'], 'deleted_at' => null])->first();

        if (empty($token) || !$token->isValid()) {
            return new JsonResponse('Invalid Token', Response::HTTP_UNAUTHORIZED);
        }

        $question = $this->questionDataProvider->get($id);

        if (!$question) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        $answers = Answer::query()
            ->select([
                'answers.id', 'answers.price', 'answers.comment', 'answers.offers', 'answers.quote_accepted_at', 'answers.type',
                'answers.viewed_at', 'answers.supplier_invoice_no', 'answers.user_id', 'users.first_name', 'users.last_name',
            ])
            ->selectRaw('(select (count(*)>0) from users_preferred_suppliers where supplier_id=answers.user_id and (user_id=questions.user_id or user_id = (select billing_user_id from users where id = questions.user_id))) is_preferred_supplier')
            ->selectRaw('DATE_FORMAT(answers.`created_at`, "%Y-%m-%dT%H:%i:%S.000000Z") created_at')
            ->join('questions', 'questions.id', '=', 'answers.question_id')
            ->leftJoin('users', 'users.id', '=', 'answers.user_id')
            ->where(['answers.question_id' => $id])
            ->get()
            ->toArray();

        $response = [
            'question' => $question,
            'answers' => $answers,
        ];

        return new JsonResponse($response);
    }

    /**
     * @throws \RedisException
     */
    public function updateEnquiry(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => 'required',
            'token' => 'required',
            'dueDate' => 'required',
            'archive' => 'required'
        ]);

        $id = (int) $data['id'] ?? null;

        $token = QuestionAccessToken::where(['question_id' => $id, 'token' => $data['token'], 'deleted_at' => null])->first();

        if (empty($token) || !$token->isValid()) {
            return new JsonResponse('Invalid Token', Response::HTTP_UNAUTHORIZED);
        }

        $question = $this->questionDataProvider->get($id);

        if (!$question) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        if ($data['archive']) {
            $this->questionDataProvider->setArchived($question);

            $log = QuestionActionLog::create([
                'question_id' => $id,
                'user_id' => $question->getUserId(),
                'action' => 'enquiry_archived',
                'comment' => 'customer archived the enquiry'
            ]);
        }

        $question->days = $data['dueDate'];
        $question->save();

        // log action
        $log = QuestionActionLog::create([
            'question_id' => $id,
            'user_id' => $question->getUserId(),
            'action' => 'enquiry_updated',
            'comment' => 'customer updated the enquiry due date'
        ]);

        // delete token
        $token = QuestionAccessToken::where(['question_id' => $id, 'token' => $data['token']])->first();
        $token->deleted_at = new Carbon();
        $token->save();

        return new JsonResponse(['message' => 'Enquiry updated']);
    }

    /**
     * @throws \RedisException
     */
    public function acceptQuote(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => 'required',
            'token' => 'required',
            'quoteId' => 'required'
        ]);

        $id = (int) $data['id'] ?? null;
        $quoteId = (int) $data['quoteId'] ?? null;

        $token = QuestionAccessToken::where(['question_id' => $id, 'token' => $data['token'], 'deleted_at' => null])->first();

        if (empty($token) || !$token->isValid()) {
            return new JsonResponse('Invalid Token', Response::HTTP_UNAUTHORIZED);
        }

        $question = $this->questionDataProvider->get($id);
        $answer = $this->answerDataProvider->getById($quoteId);

        if (!$question || !$answer) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        $accepted = $this->answerDataProvider->accept($quoteId);

        if (!$accepted) {
            return new JsonResponse('Error accepting quote', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // log action
        $log = QuestionActionLog::create([
            'question_id' => $id,
            'user_id' => $question->getUserId(),
            'action' => 'quote_accepted',
            'comment' => 'customer accepted quote id: ' . $data['quoteId']
        ]);

        // delete token
        $token = QuestionAccessToken::where(['question_id' => $id, 'token' => $data['token']])->first();
        $token->deleted_at = new Carbon();
        $token->save();

        return new JsonResponse(['message' => 'Quote accepted']);
    }
	public function customerResponse(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => 'required',
            'token' => 'required',
        ]);

        $id = (int) $data['id'] ?? null;
        $token = QuestionAccessToken::where(['question_id' => $id, 'token' => $data['token'], 'deleted_at' => null])->first();

        if (empty($token) || !$token->isValid()) {
            return new JsonResponse('Invalid Token', Response::HTTP_UNAUTHORIZED);
        }

        $question = $this->questionDataProvider->get($id);

        if (!$question) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        // log action
        $log = QuestionActionLog::create([
            'question_id' => $id,
            'user_id' => $question->getUserId(),
            'action' => 'customer_response',
            'comment' => 'customer purchased outside platform'
        ]);

        // delete token
        $token = QuestionAccessToken::where(['question_id' => $id, 'token' => $data['token']])->first();
        $token->deleted_at = new Carbon();
        $token->save();

        return new JsonResponse(['message' => 'Thankyou for your letting us know']);
    }
}
