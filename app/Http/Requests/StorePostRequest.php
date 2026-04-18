<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // الـ Authorization في الـ Controller
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:5', 'max:255'],
            'body' => ['required', 'string', 'min:20'],
            'published' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'العنوان مطلوب',
            'title.min' => 'العنوان لازم يكون 5 حروف على الأقل',
            'body.required' => 'محتوى المنشور مطلوب',
            'body.min' => 'المحتوى لازم يكون 20 حرف على الأقل',
        ];
    }
}
