<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'surname',
        'floor',
        'room',
        'phone',
        'email',
        'checkedIn',
    ];

    protected $attributes = [
        'checkedIn' => false,
    ];

    public static function validate($data, $ignoreId = null)
    {
        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                // Latvian letters, spaces, hyphens, apostrophes
                'regex:/^[A-Za-zĀāČčĒēĢģĪīĶķĻļŅņŠšŪūŽž\s\'\-]+$/u'
            ],
            'surname' => [
                'required',
                'string',
                'max:255',
                'regex:/^[A-Za-zĀāČčĒēĢģĪīĶķĻļŅņŠšŪūŽž\s\'\-]+$/u'
            ],
            'room' => [
                'required',
                'integer',
            ],
            'floor' => [
                'required',
                'integer',
                'between:1,5'
            ],
            'phone' => [
                'required',
                'regex:/^[0-9]{8}$/'
            ],
            'email' => [
                'required',
                'string',
                'email',
                'regex:/@rvt\.lv$/',
                'max:255',
                'unique:students,email' . ($ignoreId ? ',' . $ignoreId : ''),
            ],
            'checkedIn' => 'boolean',
        ];

        $validator = Validator::make($data, $rules);

        $validator->after(function ($validator) use ($data) {
            if (!isset($data['room'], $data['floor']) || !self::isValidRoomForFloor($data['room'], $data['floor'])) {
                $validator->errors()->add('room', 'The room number is not valid for the specified floor.');
            }
        });

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    public static function isValidRoomForFloor($room, $floor)
    {
        $roomRanges = [
            1 => [101, 128],
            2 => [201, 228],
            3 => [301, 328],
            4 => [401, 428],
            5 => [501, 528],
        ];
        if (!isset($roomRanges[$floor])) return false;
        [$start, $end] = $roomRanges[$floor];
        return $room >= $start && $room <= $end;
    }
}
