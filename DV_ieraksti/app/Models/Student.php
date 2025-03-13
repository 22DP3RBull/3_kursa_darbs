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
        'room',
        'floor',
        'phone',
        'email',
        'check_in_status',
    ];

    public static function validate($data)
    {
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'room' => 'required|integer|between:101,528',
            'floor' => 'required|integer|between:1,5',
            'phone' => 'required|string|size:8',
            'email' => 'required|string|email|unique:students,email|regex:/@rvt\.lv$/',
            'check_in_status' => 'boolean',
        ]);

        $validator->after(function ($validator) use ($data) {
            if (!self::isValidRoomForFloor($data['room'], $data['floor'])) {
                $validator->errors()->add('room', 'The room number is not valid for the specified floor.');
            }
        });

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    private static function isValidRoomForFloor($room, $floor)
    {
        $roomFloor = (int) substr($room, 0, 1);
        return $roomFloor === $floor;
    }
}
