<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
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
    ];
}