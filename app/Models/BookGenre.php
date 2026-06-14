<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookGenre extends Model
{
    /**
     * Maps genre records to the legacy book_genre table.
     */
    protected $table = 'book_genre';

    protected $primaryKey = 'genre_id';

    public $timestamps = false;

    protected $fillable = [
        'genre_name',
    ];
}
