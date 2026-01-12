<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DailyAttendanceReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public $admin;
    public $attendance;
    public $date;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($admin, $attendance, $date)
    {
        $this->admin = $admin;
        $this->attendance = $attendance;
        $this->date = $date;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('📋 Daily Attendance Report - ' . date('d M, Y', strtotime($this->date)))
                    ->view('emails.daily_attendance_report')
                    ->with([
                        'admin' => $this->admin,
                        'attendance' => $this->attendance,
                        'date' => $this->date,
                    ]);
    }
}