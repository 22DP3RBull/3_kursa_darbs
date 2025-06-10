<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $query = Student::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('surname', 'like', "%$search%")
                  ->orWhere('room', 'like', "%$search%");
            });
        }

        if ($request->filled('floor')) {
            $query->where('floor', $request->input('floor'));
        }

        if ($request->has('checkedIn') && $request->input('checkedIn') !== null && $request->input('checkedIn') !== '') {
            $checkedIn = $request->input('checkedIn');
            if ($checkedIn === 'true' || $checkedIn === 1 || $checkedIn === true) {
                $query->where('checkedIn', true);
            } elseif ($checkedIn === 'false' || $checkedIn === 0 || $checkedIn === false) {
                $query->where('checkedIn', false);
            }
        }

        $students = $query->orderBy('name', 'asc')->get();

        return response()->json($students);
    }

    public function store(Request $request)
    {
        try {
            \App\Models\Student::validate($request->all());
            $data = $request->only(['name', 'surname', 'room', 'floor', 'phone', 'email']);
            $data['checkedIn'] = false;
            $student = \App\Models\Student::create($data);
            return response()->json($student, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        return Student::find($id);
    }

    public function update(Request $request, $id)
    {
        try {
            $student = \App\Models\Student::findOrFail($id);
            \App\Models\Student::validate($request->all(), $student->id);
            $student->update($request->only(['name', 'surname', 'room', 'floor', 'phone', 'email', 'checkedIn']));
            return response()->json($student, 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateCheckedInStatus(Request $request, $id)
    {
        try {
            $data = $request->validate([
                'checkedIn' => 'required|boolean',
            ]);

            $student = \App\Models\Student::findOrFail($id);

            // Set Riga timezone
            $now = now('Europe/Riga');

            // Update checkedIn and last_check_in/last_check_out
            $student->checkedIn = $data['checkedIn'];
            if ($data['checkedIn']) {
                $student->last_check_in = $now;
            } else {
                $student->last_check_out = $now;
            }
            $student->save();

            // Save to history table
            \DB::table('history')->insert([
                'student_id' => $student->id,
                'action' => $data['checkedIn'] ? 'check-in' : 'check-out',
                'performed_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return response()->json(['message' => 'Status updated successfully', 'student' => $student], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        Student::destroy($id);
        return response()->json(null, 204);
    }

    public function joinExample()
    {
        $studentsWithRooms = Student::query()
            ->select('students.*', 'rooms.room_type', 'rooms.capacity')
            ->join('rooms', 'students.room', '=', 'rooms.room_number')
            ->get();

        return response()->json($studentsWithRooms);
    }

    public function groupByFloor()
    {
        $studentsByFloor = Student::query()
            ->select('floor', DB::raw('COUNT(*) as total_students'))
            ->groupBy('floor')
            ->orderBy('floor', 'asc')
            ->get();

        return response()->json($studentsByFloor);
    }

    public function orderByField(Request $request)
    {
        $field = $request->get('field', 'name');
        $direction = $request->get('direction', 'asc');

        $students = Student::query()
            ->orderBy($field, $direction)
            ->get();

        return response()->json($students);
    }

    public function groupByRoom()
    {
        $studentsByRoom = Student::query()
            ->select('room', DB::raw('COUNT(*) as total_students'))
            ->groupBy('room')
            ->orderBy('room', 'asc')
            ->get();

        return response()->json($studentsByRoom);
    }

    public function groupByCheckedInStatus()
    {
        $studentsByStatus = Student::query()
            ->select('checkedIn', DB::raw('COUNT(*) as total_students'))
            ->groupBy('checkedIn')
            ->orderBy('checkedIn', 'desc')
            ->get();

        return response()->json($studentsByStatus);
    }

    public function fetchHistory(Request $request)
    {
        $query = \DB::table('history')
            ->join('students', 'history.student_id', '=', 'students.id')
            ->select('history.*', 'students.name', 'students.surname', 'students.room', 'students.floor');

        if ($request->filled('action')) {
            $query->where('history.action', $request->input('action'));
        }

        if ($request->filled('date')) {
            $query->whereDate('history.performed_at', $request->input('date'));
        }

        if ($request->filled('room')) {
            $query->where('students.room', $request->input('room'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('students.name', 'like', "%$search%")
                  ->orWhere('students.surname', 'like', "%$search%")
                  ->orWhere('students.room', 'like', "%$search%");
            });
        }

        $history = $query->orderBy('history.performed_at', 'desc')->get();

        return response()->json($history);
    }

    public function calculateTimeSinceLastAction($id)
    {
        $student = Student::findOrFail($id);

        if ($student->checkedIn) {
            $lastActionTime = $student->last_check_in;
            $status = 'Checked In';
        } else {
            $lastActionTime = $student->last_check_out;
            $status = 'Checked Out';
        }

        if (!$lastActionTime) {
            return response()->json(['message' => 'No check-in or check-out history available for this student.'], 404);
        }

        $timeSinceLastAction = now()->diff($lastActionTime);

        return response()->json([
            'status' => $status,
            'time_since' => sprintf(
                '%d days, %d hours',
                $timeSinceLastAction->d,
                $timeSinceLastAction->h
            ),
        ]);
    }

    public function searchHistory(Request $request)
    {
        $query = DB::table('history')
            ->join('students', 'history.student_id', '=', 'students.id')
            ->select('history.*', 'students.name', 'students.surname', 'students.room', 'students.floor');

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('students.name', 'like', '%' . $request->search . '%')
                  ->orWhere('students.surname', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->has('action')) {
            $query->where('history.action', $request->action);
        }

        if ($request->has('date')) {
            $query->whereDate('history.performed_at', $request->date);
        }

        $history = $query->orderBy('history.performed_at', 'desc')->get();

        return response()->json($history);
    }

    public function getDashboardStats()
    {
        $studentsByFloor = Student::query()
            ->select('floor', DB::raw('COUNT(*) as total_students'))
            ->groupBy('floor')
            ->orderBy('floor', 'asc')
            ->get();

        $checkedInCount = Student::where('checkedIn', true)->count();
        $checkedOutCount = Student::where('checkedIn', false)->count();

        return response()->json([
            'studentsByFloor' => $studentsByFloor,
            'checkedInCount' => $checkedInCount,
            'checkedOutCount' => $checkedOutCount,
        ]);
    }
}
