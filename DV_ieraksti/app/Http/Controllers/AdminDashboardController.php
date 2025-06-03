<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Student;

class AdminDashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        // Fetch necessary data for the admin dashboard
        $studentsByFloor = Student::select('floor', \DB::raw('COUNT(*) as total_students'))
            ->groupBy('floor')
            ->orderBy('floor', 'asc')
            ->get();

        $checkedInCount = Student::where('checkedIn', true)->count();
        $checkedOutCount = Student::where('checkedIn', false)->count();

        return Inertia::render('AdminDashboard', [
            'studentsByFloor' => $studentsByFloor,
            'checkedInCount' => $checkedInCount,
            'checkedOutCount' => $checkedOutCount,
        ]);
    }
}
