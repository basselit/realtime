<?php

namespace Codatsoft\Realtime\Voice\Http\Requests;

use Codatsoft\Realtime\Voice\Types\TVoiceRoomKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HTReqVoiceToken extends FormRequest
{
    public TVoiceRoomKind $kind;

    /** The other person in a call. Null for an agent room. */
    public ?int $peerUserId = null;

    public function rules(): array
    {
        return [
            'kind'       => ['required', Rule::enum(TVoiceRoomKind::class)],
            'peerUserId' => ['required_if:kind,' . TVoiceRoomKind::CALL->value, 'integer', 'exists:users,id'],
        ];
    }

    protected function passedValidation(): void
    {
        $v = $this->validated();

        $this->kind = TVoiceRoomKind::from($v['kind']);
        $this->peerUserId = isset($v['peerUserId']) ? (int) $v['peerUserId'] : null;

    }

}
