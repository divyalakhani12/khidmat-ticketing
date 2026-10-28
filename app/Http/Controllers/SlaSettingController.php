<?php

namespace App\Http\Controllers;

use App\Models\SlaSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SlaSettingController extends Controller
{
    public function index()
    {
        // Only the Super Admin may manage SLA settings.
        if (Auth::user()->role !== 'super_admin') {
            abort(403);
        }

        $settings = SlaSetting::orderBy('id')->get();

        return view('dashboard.sla', compact('settings'));
    }

    public function update(Request $request)
    {
        if (Auth::user()->role !== 'super_admin') {
            abort(403);
        }

        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.response_hours' => 'required|integer|min:1',
            'settings.*.resolution_hours' => 'required|integer|min:1',
        ]);

                // Start from the saved values, then overlay whatever the form sent.
        $order = ['Low', 'Medium', 'High', 'Critical'];

        $byPriority = [];
        
        // 1. Load current saved values for every priority.
        foreach (SlaSetting::all() as $sla) {
            $byPriority[$sla->priority] = [
                'response_hours' => $sla->response_hours,
                'resolution_hours' => $sla->resolution_hours,
            ];
        }

        // 2. Overlay the submitted values on top.
        foreach ($validated['settings'] as $id => $values) {
            $sla = SlaSetting::find($id);
            if ($sla) {
                $byPriority[$sla->priority] = $values;
            }
        }

        // 3. Check the ordering: Critical <= High <= Medium <= Low.
        foreach (['response_hours', 'resolution_hours'] as $field) {
            for ($i = 1; $i < count($order); $i++) {
                $higher = $order[$i];
                $lower  = $order[$i - 1];

                if (
                    isset($byPriority[$higher], $byPriority[$lower]) &&
                    $byPriority[$higher][$field] > $byPriority[$lower][$field]
                ) {
                    return back()
                        ->withErrors([
                            'settings' =>
                                ucfirst(str_replace('_', ' ', $field)) .
                                " for {$higher} cannot be longer than {$lower}.",
                        ])
                        ->withInput();
                }
            }
        }


        foreach ($validated['settings'] as $id => $values) {
            $sla = SlaSetting::find($id);

            if ($sla) {
                $sla->response_hours = $values['response_hours'];
                $sla->resolution_hours = $values['resolution_hours'];
                $sla->save();
            }
        }

        return redirect()
            ->route('dashboard.sla')
            ->with('success', 'SLA settings updated.');
    }
}