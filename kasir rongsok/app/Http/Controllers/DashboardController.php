<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected ReportService $reportService
    ) {}

    public function index(Request $request): View
    {
        $period = $request->query('period', 'today');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $metrics = $this->reportService->getDashboardMetrics($period, $startDate, $endDate);

        return view('dashboard.index', compact('metrics', 'period', 'startDate', 'endDate'));
    }
}

