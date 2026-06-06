<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LibraryMember extends Model
{
    protected $table = 'library_member';

    protected $primaryKey = 'member_id';

    public $timestamps = false;

    protected $fillable = [
        'member_role',
        'last_name',
        'first_name',
        'm.i.',
    ];

    public function getMiddleInitialAttribute(): ?string
    {
        return $this->{'m.i.'};
    }

    public function getFullNameAttribute(): string
    {
        $middleInitial = $this->{'m.i.'} ? ' ' . $this->{'m.i.'} . '.' : '';

        return trim($this->first_name . $middleInitial . ' ' . $this->last_name);
    }
}