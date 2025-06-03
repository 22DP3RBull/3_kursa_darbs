<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Import the DB facade
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $query = Student::query()->orderBy('name', 'asc'); 

        // Log the received parameters for debugging
        Log::info('Filter parameters received:', [
            'search' => $request->search,
            'floor' => $request->floor,
            'checkedIn' => $request->checkedIn,
        ]);

        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('surname', 'like', '%' . $request->search . '%')
                  ->orWhere('room', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->has('floor') && $request->floor) {
            $query->where('floor', $request->floor);
        }

        if ($request->has('checkedIn') && $request->checkedIn !== null) {
            $checkedIn = filter_var($request->checkedIn, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            Log::info('Parsed checkedIn value:', ['checkedIn' => $checkedIn]);
            if ($checkedIn !== null) {
                $query->where('checkedIn', $checkedIn);
            }
        }

        $students = $query->get();

        // Log the executed query and results
        Log::info('Executed query:', ['query' => $query->toSql(), 'bindings' => $query->getBindings()]);
        Log::info('Query results:', ['students' => $students]);

        return response()->json($students);
    }

    public function store(Request $request)
    {
        try {
            Log::info('Storing new student:', $request->all());
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'surname' => 'required|string|max:255',
                'room' => 'required|integer|between:101,528',
                'floor' => 'required|integer|between:1,5',
                'phone' => 'required|string|size:8',
                'email' => 'required|string|email|unique:students,email|regex:/@rvt\.lv$/',
            ]);
            $data['checkedIn'] = false; // Ensure checkedIn is set to false by default

            $student = Student::create($data);
            Log::info('Student created successfully:', $student->toArray());
            return response()->json($student, 201);
        } catch (ValidationException $e) {
            Log::error('Validation error:', $e->errors());
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error storing student:', ['message' => $e->getMessage()]);
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
            $student = Student::findOrFail($id);

            $data = $request->validate([
                'name' => 'required|string|max:255',
                'surname' => 'required|string|max:255',
                'room' => 'required|integer|between:101,528',
                'floor' => 'required|integer|between:1,5',
                'phone' => 'required|string|size:8',
                'email' => 'required|string|email|regex:/@rvt\.lv$/|unique:students,email,' . $student->id,
                'checkedIn' => 'boolean',
            ]);

            $student->update($data);

            return response()->json($student, 200);
        } catch (ValidationException $e) {
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

            $student = Student::findOrFail($id);

            // Update the student's checked-in status
            $student->checkedIn = $data['checkedIn'];

            // Save history record
            DB::table('history')->insert([
                'student_id' => $student->id,
                'action' => $data['checkedIn'] ? 'check-in' : 'check-out',
                'performed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $student->save();

            return response()->json(['message' => 'Status updated successfully', 'student' => $student], 200);
        } catch (ValidationException $e) {
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
            ->select('students.*', 'rooms.room_type', 'rooms.capacity') // Select specific columns
            ->join('rooms', 'students.room', '=', 'rooms.room_number') // Join with rooms table
            ->get();

        return response()->json($studentsWithRooms);
    }

    public function groupByFloor()
    {
        $studentsByFloor = Student::query()
            ->select('floor', DB::raw('COUNT(*) as total_students')) // Use DB::raw for raw SQL
            ->groupBy('floor') // Group by floor
            ->orderBy('floor', 'asc') // Order by floor
            ->get();

        return response()->json($studentsByFloor);
    }

    public function orderByField(Request $request)
    {
        $field = $request->get('field', 'name'); // Default to 'name'
        $direction = $request->get('direction', 'asc'); // Default to ascending

        $students = Student::query()
            ->orderBy($field, $direction) // Order by the specified field and direction
            ->get();

        return response()->json($students);
    }

    public function groupByRoom()
    {
        $studentsByRoom = Student::query()
            ->select('room', DB::raw('COUNT(*) as total_students')) // Use DB::raw for raw SQL
            ->groupBy('room') // Group by room
            ->orderBy('room', 'asc') // Order by room
            ->get();

        return response()->json($studentsByRoom);
    }

    public function groupByCheckedInStatus()
    {
        $studentsByStatus = Student::query()
            ->select('checkedIn', DB::raw('COUNT(*) as total_students')) // Use DB::raw for raw SQL
            ->groupBy('checkedIn') // Group by checked-in status
            ->orderBy('checkedIn', 'desc') // Order by status (checked-in first)
            ->get();

        return response()->json($studentsByStatus);
    }

    public function fetchHistory(Request $request)
    {
        $query = DB::table('history')
            ->join('students', 'history.student_id', '=', 'students.id')
            ->select('history.*', 'students.name', 'students.surname', 'students.room', 'students.floor');

        if ($request->has('action')) {
            $query->where('history.action', $request->action);
        }

        if ($request->has('date')) {
            $query->whereDate('history.performed_at', $request->date);
        }

        if ($request->has('room')) {
            $query->where('students.room', $request->room);
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('students.name', 'like', '%' . $request->search . '%')
                  ->orWhere('students.surname', 'like', '%' . $request->search . '%');
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
