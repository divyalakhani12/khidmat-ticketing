<?php

namespace App\Console\Commands;

use App\Mail\TicketEscalatedMail;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class EscalateOverdueTickets extends Command
{
    protected $signature = 'tickets:escalate';

    protected $description = 'Email admins about overdue, still-open tickets';

    public function handle(): void
    {
        // Find tickets that are overdue, still open, and not yet escalated.
        $tickets = Ticket::whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->whereNotIn('status', ['Resolved', 'Closed', 'Cancelled', 'Merged'])
            ->whereNull('escalated_at')
            ->get();

        if ($tickets->isEmpty()) {
            $this->info('No tickets to escalate.');
            return;
        }

        foreach ($tickets as $ticket) {
            // Recipients: the branch's admins + all super admins.
            $recipients = User::where(function ($q) use ($ticket) {
                $q->where('role', 'branch_admin')
                    ->where('branch_id', $ticket->branch_id);
            })
                ->where('is_active', true)
                ->orWhere(function ($q) {
                    $q->where('role', 'super_admin')->where('is_active', true);
                })
                ->pluck('email')
                ->all();

            foreach ($recipients as $email) {
                Mail::to($email)->send(new TicketEscalatedMail($ticket));
            }

            // Mark it so it isn't escalated again.
            $ticket->escalated_at = now();
            $ticket->save();

            $this->info('Escalated ' . $ticket->ticket_number);
        }

        $this->info('Done. Escalated ' . $tickets->count() . ' ticket(s).');
    }
}
