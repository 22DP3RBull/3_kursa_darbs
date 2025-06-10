<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $floor = $request->query('floor');
        $query = Student::query();

        if ($floor) {
            $query->where('floor', $floor);
        }

        // Get unique rooms with at least one student
        $rooms = $query->select('room', 'floor')
            ->groupBy('room', 'floor')
            ->orderBy('room')
            ->get()
            ->map(function ($room) {
                return [
                    'id' => $room->room,
                    'room' => $room->room,
                    'floor' => $room->floor,
                ];
            })
            ->values();

        return response()->json($rooms);
    }
}
