<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class QuotationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $customer;
    public $quotation;
    public $company;
    public $publicLink; 

    public function __construct($customer, $quotation, $company, $publicLink)
    {
        $this->customer     = $customer;
        $this->quotation    = $quotation;
        $this->company      = $company;
        $this->publicLink  = $publicLink; 
    }
    
    

    public function build()
    {
        return $this->subject('Quotation #' . $this->quotation->batch_no . ' from' . $this->company->app_name)
                    ->view('emails.quotation');
    }
}
