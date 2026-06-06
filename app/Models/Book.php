<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    protected $table = 'book';

    protected $primaryKey = 'book_id';

    public $timestamps = false;

    protected $fillable = [
        'author_id',
        'publisher_id',
        'genre_id',
        'isbn',
        'book_title',
        'book_status',
    ];
}