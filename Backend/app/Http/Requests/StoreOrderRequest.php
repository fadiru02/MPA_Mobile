<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreOrderRequest extends FormRequest
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
            'nomor_meja' => ['nullable', 'string', 'max:10', 'regex:/^[A-Za-z0-9-]+$/'],
            'client_request_id' => ['nullable', 'uuid'],
            'catatan' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                // Orders coming from the public QR catalog must identify the
                // table. A cashier creating a manual order may leave it empty.
                if ($this->user('sanctum') === null && blank($this->input('nomor_meja'))) {
                    $validator->errors()->add(
                        'nomor_meja',
                        'Nomor meja wajib diisi untuk pesanan dari katalog.',
                    );
                }
            },
        ];
    }
}
