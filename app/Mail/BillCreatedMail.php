<?php

namespace App\Mail;

use App\Models\Bill;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BillCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $bill;
    public $user;
    public $billToName;
    public $billFromName;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Bill $bill, User $user, $billToName = '', $billFromName = '')
    {
        $this->bill = $bill;
        $this->user = $user;
        $this->billToName = $billToName;
        $this->billFromName = $billFromName;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $dueDate = !empty($this->bill->due_date) ? \Carbon\Carbon::parse($this->bill->due_date)->format('d/m/Y') : 'Non définie';
        $receiptDate = !empty($this->bill->reciept_date) ? \Carbon\Carbon::parse($this->bill->reciept_date)->format('d/m/Y') : 'Aujourd\'hui';
        
        return $this->subject('Nouvelle Facture Créée - ' . $this->bill->bill_number)
                    ->view('emails.bill-created')
                    ->with([
                        'adminName' => $this->user->name,
                        'billNumber' => $this->bill->bill_number,
                        'billTitle' => $this->bill->title,
                        'billFromName' => $this->billFromName,
                        'billToName' => $this->billToName,
                        'receiptDate' => $receiptDate,
                        'dueDate' => $dueDate,
                        'totalAmount' => number_format((int)$this->bill->total_amount, 0, ',', ' '),
                        'subtotal' => number_format((int)$this->bill->subtotal, 0, ',', ' '),
                        'totalTax' => number_format((int)$this->bill->total_tax, 0, ',', ' '),
                        'totalDiscount' => number_format((int)$this->bill->total_disc, 0, ',', ' '),
                        'currency' => 'FCFA',
                        'billDescription' => $this->bill->description ?? '',
                    ]);
    }
}
