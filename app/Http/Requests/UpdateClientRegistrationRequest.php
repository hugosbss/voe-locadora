<?php

namespace App\Http\Requests;

use App\Models\ClientRegistration;
use Illuminate\Foundation\Http\FormRequest;

class UpdateClientRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $registration = $this->route('registration');

        return $registration instanceof ClientRegistration
            && $this->user()?->can('update', $registration) === true;
    }

    /**
     * Only vehicle information can be changed from the administrative form.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'vehicle_observation' => ['nullable', 'string', 'max:5000'],
            'vehicle_pickup_photo' => $this->photoRules(),
            'vehicle_delivery_photo' => $this->photoRules(),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function photoRules(): array
    {
        return [
            'nullable',
            'image',
            'mimes:jpg,jpeg,png,webp',
            'max:5120',
            'dimensions:min_width=200,min_height=200,max_width=8000,max_height=8000',
        ];
    }
}
