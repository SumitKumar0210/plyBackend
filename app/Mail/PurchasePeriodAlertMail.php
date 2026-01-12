<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PurchasePeriodAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public $admin;
    public $payments;
    public $today;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($admin, $payments, $today)
    {
        $this->admin = $admin;
        $this->payments = $payments;
        $this->today = $today;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Payment Reminder Alert - Action Required')
                    ->view('emails.payment_reminder_alert')
                    ->with([
                        'admin' => $this->admin,
                        'payments' => $this->payments,
                        'today' => $this->today,
                    ]);
    }
}