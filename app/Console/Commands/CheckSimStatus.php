<?php

namespace App\Console\Commands;

use App\Models\SimLogoTransaction;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckSimStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'simlogo:check-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks status for transactions pending for over an hour';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startWindow = Carbon::now()->subHours(2);
        $endWindow = Carbon::now()->subHour();

        $transactions = SimLogoTransaction::where('status', 'pending')
            ->whereBetween('updated_at', [$startWindow, $endWindow])
            ->get();

        foreach ($transactions as $txn) {
            // Logic: "To get the transaction status"
            $this->info("Checking status for: {$txn->transaction_id}");

            // Example: Update DB based on result
            // $txn->update(['status' => 'success']);
        }
    }
}
