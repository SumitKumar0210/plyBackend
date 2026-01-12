<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PurchaseOrderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $vendor;
    public $purchaseOrder;
    public $company;
    public $publicLink; 

    public function __construct($vendor, $purchaseOrder, $company, $publicLink)
    {
        $this->vendor     = $vendor;
        $this->purchaseOrder    = $purchaseOrder;
        $this->company      = $company;
        $this->publicLink  = $publicLink; 
    }
    
    

    public function build()
    {
        return $this->subject('Purchase Order #' . $this->purchaseOrder->purchase_no . ' from' . $this->company->app_name)
                    ->view('emails.purchase_order_to_vendor');
    }
}
