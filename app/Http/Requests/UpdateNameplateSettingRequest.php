<?php

namespace App\Http\Requests;

use App\Models\NameplateSetting;
use App\Rules\DigitRangeRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNameplateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return session('logged_in') && session('user_role') === 'Administrator';
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('carline')) {
            $merge['carline'] = strtoupper(trim((string) $this->carline));
        }

        if ($this->has('date_format')) {
            $merge['date_format'] = strtoupper(trim((string) $this->date_format));
        }

        $merge['assy_customer'] = $this->boolean('assy_customer');

        if ($this->has('date') && !$this->has('date_field')) {
            $merge['date_field'] = $this->input('date');
        }

        foreach (['assy_no', 'suffix', 'level', 'date_field', 'sequence_label'] as $f) {
            if ($this->has($f)) {
                $raw = $this->input($f);
                if (filled($raw)) {
                    $val = trim((string) $raw);
                    if (preg_match('/^([1-9]\d*)\s*-\s*([1-9]\d*)$/', $val, $m)) {
                        $merge[$f] = "{$m[1]}-{$m[2]}";
                    } else {
                        $merge[$f] = $val;
                    }
                } else {
                    $merge[$f] = null;
                }
            }
        }

        $this->merge($merge);
    }

    public function rules(): array
    {
        $id = $this->route('id') ?? $this->route('nameplate_setting');
        if ($id instanceof NameplateSetting) {
            $id = $id->id;
        }

        return [
            'carline'        => ['required', 'string', 'max:50', Rule::unique('nameplate_settings', 'carline')->ignore($id)],
            'assy_customer'  => ['boolean'],
            'name'           => ['required', 'string', 'max:100'],
            'set'            => ['required', 'string', Rule::in(NameplateSetting::SET_OPTIONS)],
            'date_format'    => ['required', 'string', 'max:20', 'regex:/^[YMD]+$/i'],
            'assy_no'        => ['nullable', 'string', 'max:100', new DigitRangeRule()],
            'suffix'         => ['nullable', 'string', 'max:100', new DigitRangeRule()],
            'level'          => ['nullable', 'string', 'max:100', new DigitRangeRule()],
            'date_field'     => ['nullable', 'string', 'max:100', new DigitRangeRule()],
            'sequence_label' => ['nullable', 'string', 'max:100', new DigitRangeRule()],
        ];
    }

    public function messages(): array
    {
        return [
            'carline.required'    => 'Kolom Carline Code wajib diisi.',
            'carline.string'      => 'Kolom Carline Code harus berupa teks.',
            'carline.max'         => 'Kolom Carline Code maksimal 50 karakter.',
            'carline.unique'      => 'Carline Code sudah terdaftar.',
            'name.required'       => 'Kolom Name wajib diisi.',
            'name.string'         => 'Kolom Name harus berupa teks.',
            'name.max'            => 'Kolom Name maksimal 100 karakter.',
            'set.required'        => 'Kolom Set wajib diisi.',
            'set.in'              => 'Pilihan Set tidak valid.',
            'date_format.required'=> 'Kolom Date Format wajib diisi.',
            'date_format.regex'   => 'Format Date Format hanya boleh berisi kombinasi karakter Y, M, dan D (contoh: YYMMDD).',
            'date_format.max'     => 'Kolom Date Format maksimal 20 karakter.',
        ];
    }
}
