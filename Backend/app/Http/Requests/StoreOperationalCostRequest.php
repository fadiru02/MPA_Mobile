<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOperationalCostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tanggal' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'kategori' => ['required', 'string', 'max:50'],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'nominal' => ['required', 'integer', 'min:1', 'max:1000000000'],
        ];
    }
}
