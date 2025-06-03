<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Room;


class RoomController extends Controller
{
    public function index()
    {
        // Fetch all rooms with their floor information
        $rooms = Room::select('id', 'room', 'floor')->get(); // Ensure 'room' and 'floor' fields exist
        return response()->json($rooms);
    }
}
