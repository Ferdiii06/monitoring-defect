<?php

namespace App\Http\Controllers;

use App\Models\AssyNumber;
use App\Support\KanbanCodeParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssyLookupController extends Controller
{
    /**
     * Endpoint lookup assy number dari hasil scan kanban atau QR nameplate.
     */
    public function lookup(Request $request): JsonResponse
    {
        $scan = $request->query('code')
            ?? $request->query('scan')
            ?? $request->query('q')
            ?? $request->query('raw');

        if ($scan === null || trim((string) $scan) === '') {
            return response()->json([
                'message' => 'String hasil scan tidak boleh kosong.',
            ], 422);
        }

        $parsed = KanbanCodeParser::detect((string) $scan);

        if ($parsed === null) {
            return response()->json([
                'message' => 'Format kode tidak valid. Pastikan scan kode kanban atau QR nameplate yang sesuai.',
            ], 422);
        }

        $assyNumber = $parsed['assy_number'];
        $normalizedAssy = KanbanCodeParser::normalizeAssyNumber($assyNumber);

        $master = AssyNumber::with(['carline.carType'])
            ->where(function ($q) use ($assyNumber, $normalizedAssy) {
                $q->where('assy_number', $assyNumber)
                  ->orWhere('assy_number', $normalizedAssy)
                  ->orWhereRaw("REPLACE(REPLACE(UPPER(assy_number), '-', ''), ' ', '') = ?", [$normalizedAssy]);
            })
            ->first();

        return response()->json([
            'assy_number'         => $assyNumber,
            'conveyor'            => $parsed['conveyor'] ?? null,
            'serial'              => $parsed['serial'] ?? null,
            'carline_id'          => $master?->carline_id ?? null,
            'carline_nama'        => $master?->carline?->name ?? null,
            'car_type_id'         => $master?->carline?->car_type_id ?? null,
            'car_type_nama'       => $master?->carline?->carType?->name ?? null,
            'ditemukan_di_master' => $master !== null,
        ]);
    }
}
