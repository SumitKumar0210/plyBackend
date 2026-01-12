<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ChallanMail extends Mailable
{
    use Queueable, SerializesModels;

    public $customer;
    public $challan;
    public $company;
    public $publicLink; 

    public function __construct($customer, $challan, $company, $publicLink)
    {
        $this->customer     = $customer;
        $this->challan    = $challan;
        $this->company      = $company;
        $this->publicLink  = $publicLink; 
    }
    
    

    public function build()
    {
        return $this->subject('Challan #' . $this->challan->invoice_no . ' from' . $this->company->app_name)
                    ->view('emails.delivery_challan');
    }
}
