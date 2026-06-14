<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LibraryMember extends Model
{
    /**
     * Maps member records to the legacy library_member table.
     */
    protected $table = 'library_member';

    protected $primaryKey = 'member_id';

    public $timestamps = false;

    protected $fillable = [
        'member_role',
        'reference_number',
        'last_name',
        'first_name',
        'm.i.',
    ];

    /**
     * Exposes the legacy `m.i.` column as a readable attribute.
     */
    public function getMiddleInitialAttribute(): ?string
    {
        return $this->{'m.i.'};
    }

    /**
     * Formats the member's full name with an optional middle initial.
     */
    public function getFullNameAttribute(): string
    {
        $middleInitial = $this->{'m.i.'} ? ' ' . $this->{'m.i.'} . '.' : '';

        return trim($this->first_name . $middleInitial . ' ' . $this->last_name);
    }
}
