<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $query = Student::query();

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
            return response()->json($student, 201);
        } catch (ValidationException $e) {
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
            $student->checkedIn = $data['checkedIn'];
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
}
