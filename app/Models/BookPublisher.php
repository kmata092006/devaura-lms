<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookPublisher extends Model
{
    protected $table = 'book_publisher';

    protected $primaryKey = 'publisher_id';

    public $timestamps = false;

    protected $fillable = [
        'last_name',
        'first_name',
        'm.i.',
        'date_published',
    ];
}