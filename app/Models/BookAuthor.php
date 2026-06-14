<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookAuthor extends Model
{
    /**
     * Maps author records to the legacy book_author table.
     */
    protected $table = 'book_author';

    protected $primaryKey = 'author_id';

    public $timestamps = false;

    protected $fillable = [
        'last_name',
        'first_name',
        'm.i.',
    ];
}
