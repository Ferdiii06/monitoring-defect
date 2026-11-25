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
        $master = $parsed['_master'] ?? AssyNumber::findByAssyNumber($parsed['assy'] ?? $assyNumber);
        unset($parsed['_master']);

        $scannedConveyor = isset($parsed['conveyor']) && trim((string) $parsed['conveyor']) !== ''
            ? strtoupper(trim((string) $parsed['conveyor']))
            : null;

        $carTypeId = $master?->car_type_id ?? $master?->carline?->car_type_id ?? null;
        $carTypeNama = $master?->carType?->name ?? $master?->carline?->carType?->name ?? null;
        $jenisMobil = $master !== null ? ($carTypeNama ?? '-') : '-';

        $masterConveyor = $master !== null && filled($master->conveyor)
            ? strtoupper(trim((string) $master->conveyor))
            : null;
        $conveyorBeda = $master !== null
            && $masterConveyor !== null
            && $scannedConveyor !== null
            && strcasecmp($masterConveyor, $scannedConveyor) !== 0;

        return response()->json([
            'assy_number'         => $assyNumber,
            'assy'                => $parsed['assy'] ?? $assyNumber,
            'family'              => $parsed['family'] ?? null,
            'conveyor'            => $scannedConveyor,
            'conveyor_master'     => $masterConveyor,
            'conveyor_beda'       => $conveyorBeda,
            'serial'              => $parsed['serial'] ?? null,
            'carline_id'          => $master?->carline_id ?? null,
            'carline_nama'        => $master?->carline?->name ?? null,
            'car_type_id'         => $carTypeId,
            'car_type_nama'       => $carTypeNama,
            'jenis_mobil'         => $jenisMobil,
            'ditemukan_di_master' => $master !== null,
        ]);
    }
}
