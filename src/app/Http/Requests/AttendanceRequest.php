<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'break_start_time.*' => 'nullable|date_format:H:i',
            'break_end_time.*' => 'nullable|date_format:H:i',
            'reason' => 'required|string',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $start = $this->input('start_time');
            $end = $this->input('end_time');

            $breakStarts = $this->input('break_start_time') ?? [];
            $breakEnds = $this->input('break_end_time') ?? [];

            // 出勤と退勤の整合性
            if ($start && $end && strtotime($start) >= strtotime($end)) {
                $validator->errors()->add('start_time', '出勤時間もしくは退勤時間が不適切な値です');
            }

            // 各休憩の整合性
            foreach ($breakStarts as $index => $breakStart) {
                $breakEnd = $breakEnds[$index] ?? null;

                // 空文字を除外して比較
                if ($start && !empty($breakStart) && strtotime($breakStart) < strtotime($start)) {
                    $validator->errors()->add('break_start_time.' . $index, '休憩開始時間が勤務時間外です');
                }

                if ($end && !empty($breakEnd) && strtotime($breakEnd) > strtotime($end)) {
                    $validator->errors()->add('break_end_time.' . $index, '休憩終了時間が勤務時間外です');
                }

                if (!empty($breakStart) && !empty($breakEnd) && strtotime($breakStart) >= strtotime($breakEnd)) {
                    $validator->errors()->add('break_start_time.' . $index, '休憩開始が勤務終了より後になっています');
                }
            }
        });
    }

    public function messages()
    {
        return [
            'start_time.date_format' => '出勤時間の形式が正しくありません',
            'end_time.date' => '退勤時間の形式が正しくありません',
            'break_start_time.date' => '休憩開始時間の形式が正しくありません',
            'break_end_time.date' => '休憩終了時間の形式が正しくありません',
            'reason.required' => '備考を記入してください',
        ];
    }
}
