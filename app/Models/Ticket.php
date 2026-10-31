<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $casts = [
    'due_at' => 'datetime',
    'resolved_at' => 'datetime',
    'closed_at' => 'datetime',
    'escalated_at' => 'datetime',
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
    ];

    public function activities()
    {
        return $this->hasMany(TicketActivity::class);
    }

    public function comments()
    {
        return $this->hasMany(TicketComment::class);
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