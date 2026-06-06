<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LibraryVisitor extends Model
{
    protected $table = 'library_visitor';

    protected $primaryKey = 'visitor_id';

    public $timestamps = false;

    protected $fillable = [
        'last_name',
        'first_name',
        'm.i.',
    ];
}