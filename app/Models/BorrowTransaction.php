<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BorrowTransaction extends Model
{
    /**
     * Maps borrowing records to the legacy borrow_transaction table.
     */
    protected $table = 'borrow_transaction';

    protected $primaryKey = 'transaction_id';

    public $timestamps = false;

    protected $fillable = [
        'member_id',
        'book_id',
        'borrow_date',
        'return_date',
        'due_date',
        'borrow_status',
    ];
}
