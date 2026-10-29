<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Rework (Placeholder)</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background: #f8fafc; color: #1e293b; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .btn-logout { background: #ef4444; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; background: white; margin-top: 15px; }
        th, td { border: 1px solid #cbd5e1; padding: 8px 12px; text-align: left; font-size: 14px; }
        th { background: #f1f5f9; }
        .empty { text-align: center; color: #64748b; padding: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>Riwayat Rework</h1>
            <p>User: <strong>{{ session('user_name') }}</strong> | Shift: <strong>{{ session('current_shift', '-') }}</strong> | Role: <strong>{{ session('user_role') }}</strong></p>
        </div>
        <div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn-logout">Logout</button>
            </form>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Waktu In</th>
                <th>Kanban Assy</th>
                <th>Nameplate Lama</th>
                <th>Status Rework</th>
                <th>Durasi (Menit)</th>
                <th>Rework Group ID</th>
                <th>Defect</th>
            </tr>
        </thead>
        <tbody>
            @forelse($defects as $defect)
                <tr>
                    <td>{{ $defect->id }}</td>
                    <td>{{ $defect->waktu_in ?? '-' }}</td>
                    <td>{{ $defect->kanban_assy ?? '-' }}</td>
                    <td>{{ $defect->nameplate_lama ?? '-' }}</td>
                    <td>{{ $defect->status_rework ?? '-' }}</td>
                    <td>{{ $defect->durasi_rework_menit ?? '-' }}</td>
                    <td><code>{{ $defect->rework_group_id }}</code></td>
                    <td>{{ $defect->jenis_defect ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="empty">Belum ada riwayat rework.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 15px;">
        {{ $defects->links() }}
    </div>
</body>
</html>
