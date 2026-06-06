<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LibraryPersonnel extends Model
{
    protected $table = 'library_personnel';

    protected $primaryKey = 'personnel_id';

    public $timestamps = false;

    protected $fillable = [
        'personnel_role',
        'username',
        'password_hash',
        'department_id',
        'last_name',
        'first_name',
        'm.i.',
        'shift_day',
        'shift_period',
    ];

    protected $hidden = [
        'password_hash',
    ];

    public function getFullNameAttribute(): string
    {
        $middleInitial = $this->{'m.i.'} ? ' ' . $this->{'m.i.'} . '.' : '';

        return trim($this->first_name . $middleInitial . ' ' . $this->last_name);
    }
}