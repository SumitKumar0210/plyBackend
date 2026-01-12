<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LowInventoryAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public $admin;
    public $materials;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($admin, $materials)
    {
        $this->admin = $admin;
        $this->materials = $materials;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Low Material Inventory Alert')
                    ->view('emails.low_inventory_alert')
                    ->with([
                        'admin' => $this->admin,
                        'materials' => $this->materials,
                    ]);
    }
}