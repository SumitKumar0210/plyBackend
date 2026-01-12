<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LowProductInventoryAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public $admin;
    public $products;

    /**
     * Create a new message instance.
     */
    public function __construct($admin, $products)
    {
        $this->admin = $admin;
        $this->products = $products;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Low Product Inventory Alert')
                    ->view('emails.low_product_inventory_alert')
                    ->with([
                        'admin'    => $this->admin,
                        'products' => $this->products,
                    ]);
    }
}
