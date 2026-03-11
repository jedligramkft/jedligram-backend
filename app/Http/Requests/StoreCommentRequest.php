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

    protected function prepareForValidation(){
        $this->merge([
            'user_id' => $this->user()->id,
            'post_id' => $this->route('post')->id
        ]);
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
            'post_id' => "required|exists:posts,id",
            'parent_id' => "nullable|exists:comments,id",
            'user_id' => "nullable|exists:users,id"
        ];
    }

    public function after(){
        return[
            function(Validator $validator){
                if($this->filled('parent_id')){
                    $parentComment = Comment::find($this->input('parent_id'));
                    if($parentComment->post_id != $this->input('post_id')){
                        $validator->errors()->add('parent_id', 'The parent comment must belong to the same post.');
                    }
                }
            }
        ];
    }
}
