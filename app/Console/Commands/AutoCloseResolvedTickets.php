<?php

namespace App\Console\Commands;

use App\Mail\TicketClosedMail;
use App\Models\Ticket;
use App\Models\TicketActivity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class AutoCloseResolvedTickets extends Command
{
    protected $signature = 'tickets:auto-close';

    protected $description = 'Automatically close resolved tickets after one month if requester has not responded';

    public function handle(): int
    {
        $tickets = Ticket::where('status', 'Resolved')
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '<=', now()->subMonth())
            ->get();

        foreach ($tickets as $ticket) {
            $closed = false;
            DB::transaction(function () use (&$closed, $ticket) {
                $locked = Ticket::whereKey($ticket->id)
                    ->where('status', 'Resolved')
                    ->lockForUpdate()
                    ->first();

                if (!$locked) {
                    return;
                }

                $locked->status = 'Closed';
                $locked->closed_at = now();
                $locked->save();
                $closed = true;

                TicketActivity::create([
                    'ticket_id' => $locked->id,
                    'user_id' => null,
                    'actor_label' => 'System',
                    'action' => 'Automatically Closed',
                    'old_value' => 'Resolved',
                    'new_value' => 'Closed',
                ]);
            });

            if ($closed) {
                Mail::to($ticket->requester_email)
                    ->send(new TicketClosedMail($ticket));
            }

            $this->info(
                "Auto-closed ticket {$ticket->ticket_number}"
            );
        }

        $this->info(
            'Auto-close complete. ' . $tickets->count() . ' ticket(s) closed.'
        );

        return self::SUCCESS;
    }
}
