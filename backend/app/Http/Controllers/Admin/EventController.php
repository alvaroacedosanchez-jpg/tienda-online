<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->query('tipo');

        $events = Event::query()
            ->when($type, fn ($q) => $q->where('type', $type))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $types = Event::select('type')->distinct()->orderBy('type')->pluck('type');

        return view('admin.events.index', compact('events', 'types', 'type'));
    }

    /** Exportación JSON pensada para que otro sistema la consuma (Tarea 2). */
    public function json(Request $request)
    {
        $events = $this->filtered($request)->orderBy('id')->get()->map(fn (Event $e) => [
            'id' => $e->id,
            'type' => $e->type,
            'occurred_at' => $e->occurred_at?->toIso8601String(),
            'session_id' => $e->session_id,
            'order_id' => $e->order_id,
            'payload' => $e->payload,
        ]);

        return response()->json(['count' => $events->count(), 'events' => $events]);
    }

    public function csv(Request $request)
    {
        $events = $this->filtered($request)->orderBy('id')->get();

        return response()->streamDownload(function () use ($events) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'type', 'occurred_at', 'session_id', 'order_id', 'payload']);
            foreach ($events as $e) {
                fputcsv($out, [$e->id, $e->type, $e->occurred_at?->toIso8601String(), $e->session_id, $e->order_id, json_encode($e->payload, JSON_UNESCAPED_UNICODE)]);
            }
            fclose($out);
        }, 'eventos.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filtered(Request $request)
    {
        return Event::query()
            ->when($request->query('tipo'), fn ($q, $t) => $q->where('type', $t))
            ->when($request->query('desde'), fn ($q, $d) => $q->where('occurred_at', '>=', $d));
    }
}
