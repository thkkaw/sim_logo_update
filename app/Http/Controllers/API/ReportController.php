<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\SimLogoTransaction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Exceptions\HttpResponseException;

class ReportController extends Controller
{
    public function download(Request $request)
    {
        // Default dates: last 30 days excluding today
        $today = Carbon::today();
        $defaultEnd = $today->subDay();        // yesterday
        $defaultStart = $defaultEnd->copy()->subDays(29); // 30 days ago

        $request->merge([
            'start_date' => $request->start_date ?? $defaultStart->toDateString(),
            'end_date'   => $request->end_date ?? $defaultEnd->toDateString(),
        ]);

        $validator = validator($request->all(), [
            'start_date' => ['required', 'date', 'before:today'],
            'end_date'   => ['required', 'date', 'before:today', 'after_or_equal:start_date'],
            'status'     => ['nullable', 'in:fail,success,pending,404'],
        ], [
            'start_date.date' => 'Start date must be a valid date.',
            'end_date.date' => 'End date must be a valid date.',
            'start_date.before' => 'Start date cannot be today or in the future.',
            'end_date.before' => 'End date cannot be today or in the future.',
            'end_date.after_or_equal' => 'End date cannot be before start date.',
            'status.in' => 'Status must be one of: fail, success, pending, 404.',
        ]);

        if ($validator->fails()) {
            // Return JSON error response with 422 Unprocessable Entity
            throw new HttpResponseException(
                response()->json([
                    'message' => 'Invalid input parameters.',
                    'errors' => $validator->errors(),
                ], 422)
            );
        }

        $start = Carbon::parse($request->start_date)->startOfDay();
        $end = Carbon::parse($request->end_date)->endOfDay();

        // Ensure date range is not more than 30 days
        if ($start->diffInDays($end) > 30) {
            throw new HttpResponseException(
                response()->json([
                    'message' => 'Date range cannot exceed 30 days.',
                ], 422)
            );
        }

        $fileName = "sim_logo_transactions_{$start->toDateString()}_to_{$end->toDateString()}.csv";

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        return response()->stream(function () use ($start, $end, $request) {

            $handle = fopen('php://output', 'w');

            // CSV header
            fputcsv($handle, [
                'ID',
                'MSISDN',
                'SPN Flag',
                'PNN Flag',
                'Status',
            ]);

            $query = SimLogoTransaction::whereBetween('created_at', [$start, $end])
                ->orderBy('id');

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $query->chunk(1000, function ($rows) use ($handle) {
                    foreach ($rows as $row) {
                        fputcsv($handle, [
                            $row->id,
                            $row->msisdn,
                            $row->spn_flag,
                            $row->pnn_flag,
                            $row->status,
                        ]);
                    }
                });

            fclose($handle);
        }, 200, $headers);
    }
}
