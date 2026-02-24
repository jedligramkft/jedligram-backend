<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

use App\Policies\PostPolicy;

class CreatePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $thread = $this->route('thread');
        return $this->user()->can('userCheck', $thread);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'content' => 'required|string',
            // 'thread_id' => 'required|exists:threads,id',
            // 'user_id' => 'required|exists:users,id'
        ];
    }

    public function failedAuthorization(){
        throw new HttpResponseException(response()->json([
            'message' => 'You are not allowed to create a post in this thread.',
            'error' => 'UNAUTHORIZED_ACCESS'
        ], 403));
    }
}
