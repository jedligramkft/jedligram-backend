<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Policies\UserPolicy;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        //policy now checks here
        $user = $this->route('user');

        return $this->user()->can('update', $user);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */

    protected function prepareForValidation()
    {
        if($this->has('name') && !$this->has('display_name')){
            $this->merge([
                'display_name' => $this->input('name'),
            ]);
        }
        if($this->has('email') && !$this->has('display_email')){
            $this->merge([
                'display_email' => $this->input('email'),
            ]);
        }
    }

    public function rules(): array
    {
        $userId = $this->route('user')->id;

        $nameEmailPresenceRule = $this->isMethod('put') ? 'required' : 'sometimes';

        return [
            'name'  => [$nameEmailPresenceRule, 'string', 'max:40'],
            'email' => [$nameEmailPresenceRule, 'string', 'email', 'max:60', 'unique:users,email,' . $userId],

            'bio' => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
    }

    protected function failedAuthorization()
    {
        throw new HttpResponseException(response()->json([
            'message' => 'You are not allowed to update this profile.',
            'error' => 'UNAUTHORIZED_ACCESS'
        ], 403));
    }
}
