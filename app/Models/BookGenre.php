<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookGenre extends Model
{
    protected $table = 'book_genre';

    protected $primaryKey = 'genre_id';

    public $timestamps = false;

    protected $fillable = [
        'genre_name',
    ];
}