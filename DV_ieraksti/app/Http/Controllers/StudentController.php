<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index()
    {
        return Student::all();
    }

    public function store(Request $request)
    {
        Student::validate($request->all());

        return Student::create($request->all());
    }

    public function update(Request $request, Student $student)
    {
        Student::validate($request->all());

        $student->update($request->all());

        return $student;
    }

    public function destroy(Student $student)
    {
        $student->delete();

        return response()->noContent();
    }
}
