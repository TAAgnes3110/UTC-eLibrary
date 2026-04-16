<?php

namespace App\Http\Requests;

class DestroyNotificationRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'notification_id' => (int) $this->route('notificationId', 0),
        ]);
    }

    /**
     * @return array<string, list<string|\Illuminate\Validation\Rule>>
     */
    public function rules(): array
    {
        return [
            'notification_id' => ['required', 'integer', 'min:1'],
        ];
    }
}
