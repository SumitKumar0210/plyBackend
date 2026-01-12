<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\MailController;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class SendAllAlerts extends Command
{
    protected $signature = 'alerts:send-all';
    protected $description = 'Send all alert emails at exactly 12 AM';

    public function handle()
    {
        // Ensure exact 12:00 AM (IST)
        // $now = Carbon::now('Asia/Kolkata');

        // if (!($now->hour === 10 && $now->minute === 0)) {
        //     return Command::SUCCESS;
        // }

        // // Prevent duplicate execution
        // if (!Cache::add('midnight_mail_lock', true, now()->addMinutes(5))) {
        //     return Command::SUCCESS;
        // }

        app(MailController::class)->sendAllAlerts();

        \Log::info('Midnight alert emails sent successfully');

        return Command::SUCCESS;
        // 
    }
}
