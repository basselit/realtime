<?php

namespace Codatsoft\Realtime\Voice\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HTReqVoiceCallInvite extends FormRequest
{
    /** The bigint users.id of the person being called. */
    public int $peerUserId;

    public function rules(): array
    {
        return [
            'peerUserId' => ['required', 'integer', Rule::exists((string) config('realtime.users_table', 'users'), 'id')],
        ];
    }

    protected function passedValidation(): void
    {
        $this->peerUserId = (int) $this->validated()['peerUserId'];
    }

}
