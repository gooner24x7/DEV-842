<?php
declare(strict_types=1);

namespace App\Repository;

use App\Dto\Message\MessageDto;
use App\Dto\Message\SearchParamsDto;
use App\Http\Controllers\MessagesController;
use App\Models\Message;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Redis;

class MessageRepository
{
    const int ITEMS_PER_PAGE = 20;
    const string DEFAULT_ORDER_FIELD_NAME = 'messages.id';

    private Redis $redis;

    public function __construct(Redis $redis)
    {
        $this->redis = $redis;
    }

    /**
     * @throws \RedisException
     */
    public function find(SearchParamsDto $dto): Collection
    {
        $query = Message::join('users', 'users.id', '=', 'messages.user_id')
            ->select(['messages.id', 'messages.user_id', 'messages.answer_id', 'messages.message', 'messages.created_at',
                'messages.viewed_at', 'messages.virtual_expo_shared_contact_id',
                'users.first_name', 'users.last_name']);

        if ($dto->getAid()) {
            $query = $query->where([
                'messages.answer_id' => $dto->getAid(),
            ]);

            $this->redis->set(sprintf(Message::NEW_MESSAGE_INDICATOR_CACHE, $dto->getAid(), $dto->getUserId()), Carbon::now()->format('Y-m-d H:i:s'));
        }

        if ($dto->getVirtualExpoSharedContactId()) {
            $query = $query->where([
                'messages.virtual_expo_shared_contact_id' => $dto->getVirtualExpoSharedContactId(),
            ]);
        }

        if ($dto->getQuestionId()) {
            if ($dto->getRequestUserId()) {
                $query = $query->where([
                    'messages.question_id' => $dto->getQuestionId(),
                ]);

                $query = $query->where(function (Builder $query) use ($dto) {
                    $query->where(function (Builder $query) use ($dto) {
                        $query->where('messages.user_id', '=', $dto->getUserId());
                        $query->where('messages.interlocutor_id', '=', $dto->getRequestUserId());
                    })->orWhere(function (Builder $query) use ($dto) {
                        $query->where('messages.user_id', '=', $dto->getRequestUserId());
                        $query->where('messages.interlocutor_id', '=', $dto->getUserId());
                    });
                });
            }

            $this->redis->set(sprintf(Message::NEW_MESSAGE_INQUIRY_INDICATOR_CACHE, $dto->getQuestionId(), $dto->getUserId(), MessagesController::MESSAGE_TYPE_QUESTION), Carbon::now()->format('Y-m-d H:i:s'));
        }

        if ($dto->getSupplyFitEnquiryId()) {
            if ($dto->getRequestUserId()) {
                $query = $query->where([
                    'messages.supply_fit_enquiry_id' => $dto->getSupplyFitEnquiryId(),
                ]);

                $query = $query->where(function (Builder $query) use ($dto) {
                    $query->where(function (Builder $query) use ($dto) {
                        $query->where('messages.user_id', '=', $dto->getUserId());
                        $query->where('messages.interlocutor_id', '=', $dto->getRequestUserId());
                    })->orWhere(function (Builder $query) use ($dto) {
                        $query->where('messages.user_id', '=', $dto->getRequestUserId());
                        $query->where('messages.interlocutor_id', '=', $dto->getUserId());
                    });
                });
            }

            $this->redis->set(sprintf(Message::NEW_MESSAGE_INQUIRY_INDICATOR_CACHE, $dto->getSupplyFitEnquiryId(), $dto->getUserId(), MessagesController::MESSAGE_TYPE_SUPPLY_FIT_ENQUIRY), Carbon::now()->format('Y-m-d H:i:s'));
        }

        if ($dto->getSupplyFitEnquiryQuoteId()) {
            $query = $query->where([
                'supply_fit_enquiry_quote_id' => $dto->getSupplyFitEnquiryQuoteId(),
            ]);
        }

        if ($dto->getLogisticsEnquiryId()) {
            if ($dto->getRequestUserId()) {
                $query = $query->where([
                    'messages.logistics_enquiry_id' => $dto->getLogisticsEnquiryId(),
                ]);

                $query = $query->where(function (Builder $query) use ($dto) {
                    $query->where(function (Builder $query) use ($dto) {
                        $query->where('messages.user_id', '=', $dto->getUserId());
                        $query->where('messages.interlocutor_id', '=', $dto->getRequestUserId());
                    })->orWhere(function (Builder $query) use ($dto) {
                        $query->where('messages.user_id', '=', $dto->getRequestUserId());
                        $query->where('messages.interlocutor_id', '=', $dto->getUserId());
                    });
                });
            }

            $this->redis->set(sprintf(Message::NEW_MESSAGE_INQUIRY_INDICATOR_CACHE, $dto->getLogisticsEnquiryId(), $dto->getUserId(), MessagesController::MESSAGE_TYPE_LOGISTICS_ENQUIRY), Carbon::now()->format('Y-m-d H:i:s'));
        }

        if ($dto->getLogisticsQuoteId()) {
            $query = $query->where([
                'messages.logistics_quote_id' => $dto->getLogisticsQuoteId(),
            ]);
        }

        $query = $query->orderBy(
            $dto->getOrderBy() ?? self::DEFAULT_ORDER_FIELD_NAME,
            $dto->getOrderSortDesc() ? 'desc' : 'asc'
        );

        return $query->get();
    }

    public function lastChatDate(int $enquiryId, int $userA, int $userB, int $quoteId = null, int $type = 0): ?Carbon
    {
        $query = Message::select('messages.created_at');
        $quoteIdCol = 'messages.answer_id';
        $enquiryIdCol = 'messages.question_id';

        if ($type === 1) {
            $quoteIdCol = 'messages.logistics_quote_id';
            $enquiryIdCol = 'messages.logistics_enquiry_id';
        }

        if ($quoteId) {
            $query = $query->where([$quoteIdCol => $quoteId]);
        } else {
            $query = $query->where([$enquiryIdCol => $enquiryId]);
        }

        $query->where(function (Builder $query) use ($userA, $userB) {
            $query->where(function (Builder $query) use ($userA, $userB) {
                $query->where('messages.user_id', '=', $userA);
                $query->where('messages.interlocutor_id', '=', $userB);
            })->orWhere(function (Builder $query) use ($userA, $userB) {
                $query->where('messages.user_id', '=', $userB);
                $query->where('messages.interlocutor_id', '=', $userA);
            });
        });

        $item = $query->orderBy('messages.created_at', 'desc')->first();

        return $item ? new Carbon($item['created_at']) : null;
    }

    public function chatList(SearchParamsDto $dto, string $type): Collection
    {
        $redis = $this->redis;

        $enquiryId = match ($type) {
            MessagesController::MESSAGE_TYPE_QUESTION => $dto->getQuestionId(),
            MessagesController::MESSAGE_TYPE_SUPPLY_FIT_ENQUIRY => $dto->getSupplyFitEnquiryId(),
            MessagesController::MESSAGE_TYPE_LOGISTICS_ENQUIRY => $dto->getLogisticsEnquiryId(),
            default => null,
        };

        $newMsgIndicatorKey = sprintf(Message::NEW_MESSAGE_INQUIRY_INDICATOR_CACHE, $enquiryId, $dto->getUserId(), $type);

        $query = Message::join('users', 'users.id', '=', 'messages.user_id')
            ->select(['messages.user_id', 'users.first_name', 'users.last_name', DB::raw('COUNT(IF (messages.created_at > "' . $redis->get($newMsgIndicatorKey) . '", 1, NULL)) as qty')])
            ->groupBy(['messages.user_id', 'users.first_name', 'users.last_name'])
            ->whereNotNull('messages.interlocutor_id')
            ->where('messages.user_id', '!=', $dto->getUserId());

        if ($dto->getQuestionId()) {
            $query = $query->where([
                'messages.question_id' => $dto->getQuestionId(),
            ]);

            $query = $query->where(function (Builder $query) use ($dto) {
                $query->where('messages.user_id', '=', $dto->getUserId());
                $query->orWhere('messages.interlocutor_id', '=', $dto->getUserId());
            });
        }

        if ($dto->getSupplyFitEnquiryId()) {
            $query = $query->where([
                'messages.supply_fit_enquiry_id' => $dto->getSupplyFitEnquiryId(),
            ]);

            $query = $query->where(function (Builder $query) use ($dto) {
                $query->where('messages.user_id', '=', $dto->getUserId());
                $query->orWhere('messages.interlocutor_id', '=', $dto->getUserId());
            });
        }

        if ($dto->getLogisticsEnquiryId()) {
            $query = $query->where([
                'messages.logistics_enquiry_id' => $dto->getLogisticsEnquiryId(),
            ]);

            $query = $query->where(function (Builder $query) use ($dto) {
                $query->where('messages.user_id', '=', $dto->getUserId());
                $query->orWhere('messages.interlocutor_id', '=', $dto->getUserId());
            });
        }

        if ($dto->getAid()) {
            $query = $query->where([
                'messages.answer_id' => $dto->getAid(),
            ]);
        }

        if ($dto->getVirtualExpoSharedContactId()) {
            $query = $query->where([
                'messages.virtual_expo_shared_contact_id' => $dto->getVirtualExpoSharedContactId(),
            ]);
        }

        if ($dto->getSupplyFitEnquiryQuoteId()) {
            $query = $query->where([
                'supply_fit_enquiry_quote_id' => $dto->getSupplyFitEnquiryQuoteId(),
            ]);
        }

        if ($dto->getLogisticsQuoteId()) {
            $query = $query->where([
                'logistics_quote_id' => $dto->getLogisticsQuoteId(),
            ]);
        }

        return $query->get();
    }

    public function store(MessageDto $dto): ?Message
    {
        if ($message = Message::create([
            'message'                       => $dto->getMessage(),
            'user_id'                       => $dto->getUserId(),
            'answer_id'                     => (int) $dto->getAid(),
            'question_id'                   => (int) $dto->getQuestionId(),
            'supply_fit_enquiry_quote_id'   => (int) $dto->getSupplyFitEnquiryQuoteId(),
            'virtual_expo_shared_contact_id' => (int) $dto->getVirtualExpoSharedContactId(),
            'interlocutor_id'               => (int) $dto->getInterlocutorId(),
            'supply_fit_enquiry_id'         => (int) $dto->getSupplyFitEnquiryId(),
            'logistics_enquiry_id'          => (int) $dto->getLogisticsEnquiryId(),
            'logistics_quote_id'            => (int) $dto->getLogisticsQuoteId()
        ])) {
            return $message;
        }

        return null;
    }
}
