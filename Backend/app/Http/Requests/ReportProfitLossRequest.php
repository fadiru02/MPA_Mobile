<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReportProfitLossRequest extends FormRequest
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
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'group_by' => ['nullable', Rule::in(['day', 'month'])],
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

                $start = CarbonImmutable::parse((string) $this->input('start_date'))->startOfDay();
                $end = CarbonImmutable::parse((string) $this->input('end_date'))->startOfDay();

                if ($start->diffInDays($end) > 366) {
                    $validator->errors()->add(
                        'end_date',
                        'Rentang laporan maksimal 366 hari.',
                    );
                }
            },
        ];
    }
}
