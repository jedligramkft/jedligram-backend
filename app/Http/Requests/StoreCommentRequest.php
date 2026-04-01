<?php

namespace App\Http\Requests;

use App\Models\Comment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCommentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'content' => "string|required",
            'parent_id' => "nullable|exists:comments,id",
        ];
    }

    public function after()
    {
        return [
            function (Validator $validator) {
                if (!$this->filled('parent_id')) {
                    return;
                }

                $parentComment = Comment::find($this->input('parent_id'));
                if (!$parentComment) {
                    return;
                }

                $post = $this->route('post');
                if (!$post || $parentComment->post_id !== $post->id) {
                    $validator->errors()->add('parent_id', 'The parent comment must belong to the same post.');
                }
            }
        ];
    }
}
