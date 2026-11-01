<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ticket extends Model
{
    use HasFactory;
    protected $casts = [
    'due_at' => 'datetime',
    'resolved_at' => 'datetime',
    'closed_at' => 'datetime',
    'escalated_at' => 'datetime',
    'first_response_at' => 'datetime',
];

    protected $fillable = [
        'ticket_number',
        'tracking_token',
        'requester_name',
        'requester_email',
        'contact_number',
        'department_id',
        'branch_id',
        'category_id',
        'priority',
        'subject',
        'description',
        'status',
        'assigned_to',
        'due_at',
        'resolved_at',
        'closed_at',
        'escalated_at',
        'first_response_at',
        'merged_into_ticket_id',
        'merged_at',
    ];

    public function activities()
    {
        return $this->hasMany(TicketActivity::class);
    }

    public function comments()
    {
        return $this->hasMany(TicketComment::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

        public function attachments()
    {
        return $this->hasMany(TicketAttachment::class);
    }
    public function technicians()
{
    return $this->belongsToMany(
        User::class,
        'ticket_user'
    )->withTimestamps();
}
public function mergedInto()
{
    return $this->belongsTo(
        Ticket::class,
        'merged_into_ticket_id'
    );
}

public function mergedTickets()
{
    return $this->hasMany(
        Ticket::class,
        'merged_into_ticket_id'
    );
}
}
