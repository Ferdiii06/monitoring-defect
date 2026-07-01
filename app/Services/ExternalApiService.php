<?php

namespace App\Services;

use App\Models\Defect;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ExternalApiService
{
    /**
     * Fetch data dari API eksternal dan sync ke database lokal.
     * Menggunakan updateOrCreate berdasarkan external_id agar tidak duplikat.
     * 
     * @return bool true jika sync berhasil, false jika gagal (fallback ke data lokal)
     */
    public static function syncFromApi(): bool
    {
        try {
            $apiUrl = config('services.external_api.url');
            
            $response = Http::timeout(10)->get($apiUrl . '/reports');

            if (!$response->successful()) {
                Log::warning('External API returned non-success status: ' . $response->status());
                return false;
            }

            $data = $response->json();

            if (!isset($data['status']) || $data['status'] !== true || !isset($data['data'])) {
                Log::warning('External API response format unexpected', ['response' => $data]);
                return false;
            }

            foreach ($data['data'] as $item) {
                Defect::updateOrCreate(
                    ['external_id' => $item['id']],
                    [
                        'waktu'             => self::parseDateTime($item),
                        'user_name'         => $item['nama_user'] ?? 'Unknown',
                        'shift'             => $item['shift'] ?? null,
                        'jenis_assy'        => $item['type'] ?? 'Final Assy',
                        'line_conveyor'     => $item['line'] ?? '-',
                        'jenis_mobil'       => $item['jenis_mobil'] ?? null,
                        'konveyor'          => $item['conveyor'] ?? '-',
                        'jenis_defect'      => $item['jenis_defect'] ?? '-',
                        'jenis_sub_defect'  => $item['sub_defect'] ?? '-',
                        'quantity'          => $item['jumlah'] ?? 1,
                    ]
                );
            }

            Log::info('External API sync completed. Records processed: ' . count($data['data']));
            return true;

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('External API connection failed: ' . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            Log::error('External API sync error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Parse waktu dari response API eksternal.
     * Menggunakan created_at sebagai waktu utama, fallback ke tanggal.
     */
    private static function parseDateTime(array $item): string
    {
        if (!empty($item['created_at'])) {
            return Carbon::parse($item['created_at'])->format('Y-m-d H:i:s');
        }

        if (!empty($item['tanggal'])) {
            return Carbon::parse($item['tanggal'])->startOfDay()->format('Y-m-d H:i:s');
        }

        return now()->format('Y-m-d H:i:s');
    }

    /**
     * Sync satu item dari WebSocket event ke database lokal.
     * Dipanggil dari frontend via AJAX atau langsung dari event handler.
     */
    public static function syncSingleItem(array $item): ?Defect
    {
        try {
            return Defect::updateOrCreate(
                ['external_id' => $item['id']],
                [
                    'waktu'             => self::parseDateTime($item),
                    'user_name'         => $item['nama_user'] ?? 'Unknown',
                    'shift'             => $item['shift'] ?? null,
                    'jenis_assy'        => $item['type'] ?? 'Final Assy',
                    'line_conveyor'     => $item['line'] ?? '-',
                    'jenis_mobil'       => $item['jenis_mobil'] ?? null,
                    'konveyor'          => $item['conveyor'] ?? '-',
                    'jenis_defect'      => $item['jenis_defect'] ?? '-',
                    'jenis_sub_defect'  => $item['sub_defect'] ?? '-',
                    'quantity'          => $item['jumlah'] ?? 1,
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to sync single item: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Hapus item dari database lokal berdasarkan external_id.
     */
    public static function deleteByExternalId(int $externalId): bool
    {
        try {
            return Defect::where('external_id', $externalId)->delete() > 0;
        } catch (\Exception $e) {
            Log::error('Failed to delete item by external_id: ' . $e->getMessage());
            return false;
        }
    }
}
