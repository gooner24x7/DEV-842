<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $user_id
 * @property ?int $answer_id
 * @property ?int $virtual_expo_shared_contact_id
 * @property ?int $interlocutor_id
 * @property ?int $supply_fit_enquiry_id
 * @property ?int $supply_fit_enquiry_quote_id
 * @property ?int $logistics_enquiry_id
 * @property ?int $logistics_quote_id
 * @property string $message
 * @method static join($a, $b, $c, $d)
 * @method static create(array $params)
 * @method static select($a)
 */
class Message extends Model
{
    const string NEW_MESSAGE_INDICATOR_CACHE = 'new_messages_%d_%d';
    const string NEW_MESSAGE_INQUIRY_INDICATOR_CACHE = 'new_msg_inquiry_%d_%d_%s';
    const string NEW_MESSAGE_INQUIRIES_INDICATOR_CACHE = 'new_msg_inquiries_%d_%s';

    protected $guarded = [];

    public function users(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function getInterlocutorId(): ?int
    {
        return $this->interlocutor_id;
    }

    public function getMessage(): string
    {
        return $this->message ?? '';
    }
}
