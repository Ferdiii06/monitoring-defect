<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Rework</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            font-size: 16px;
            line-height: 1.4;
            background-color: #f1f5f9;
            color: #0f172a;
            padding: 12px;
            max-width: 480px;
            margin: 0 auto;
            overflow-x: hidden;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            padding: 12px 14px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            margin-bottom: 12px;
            gap: 8px;
        }
        .header-info {
            flex: 1;
            min-width: 0;
        }
        .header-title {
            font-size: 20px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 2px;
        }
        .header-meta {
            font-size: 14px;
            color: #475569;
            word-break: break-word;
        }
        .btn-logout {
            background-color: #ef4444;
            color: #ffffff;
            border: none;
            padding: 0 16px;
            min-height: 48px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            touch-action: manipulation;
        }
        .alert {
            padding: 12px 14px;
            border-radius: 6px;
            font-size: 16px;
            margin-bottom: 12px;
            word-break: break-word;
        }
        .alert-success {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        .alert-error {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .card {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 14px;
            margin-bottom: 14px;
        }
        .card-title {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 12px;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 8px;
        }
        .form-group {
            margin-bottom: 12px;
        }
        .form-label {
            display: block;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 6px;
            color: #334155;
        }
        .form-input {
            width: 100%;
            min-height: 48px;
            font-size: 16px;
            padding: 8px 12px;
            border: 1.5px solid #cbd5e1;
            border-radius: 6px;
            background: #ffffff;
            color: #0f172a;
            touch-action: manipulation;
        }
        .form-input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
        }
        .search-container {
            position: relative;
        }
        .dropdown-results {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #ffffff;
            border: 1.5px solid #cbd5e1;
            border-top: none;
            border-radius: 0 0 6px 6px;
            max-height: 220px;
            overflow-y: auto;
            z-index: 50;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .dropdown-item {
            padding: 12px 14px;
            font-size: 16px;
            border-bottom: 1px solid #f1f5f9;
            cursor: pointer;
            min-height: 48px;
            display: flex;
            align-items: center;
            color: #1e293b;
            touch-action: manipulation;
        }
        .dropdown-item:active {
            background-color: #e0e7ff;
        }
        .chips-container {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-top: 8px;
            margin-bottom: 8px;
        }
        .chip {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #ede9fe;
            color: #4c1d95;
            border: 1px solid #ddd6fe;
            border-radius: 6px;
            padding: 8px 12px;
            font-size: 15px;
            font-weight: 500;
            word-break: break-word;
        }
        .chip-remove {
            background: #f5f3ff;
            border: 1px solid #c4b5fd;
            border-radius: 4px;
            color: #7c3aed;
            font-size: 18px;
            font-weight: bold;
            margin-left: 10px;
            cursor: pointer;
            min-width: 44px;
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            touch-action: manipulation;
        }
        .btn-submit {
            width: 100%;
            min-height: 48px;
            font-size: 16px;
            font-weight: 700;
            background-color: #2563eb;
            color: #ffffff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            touch-action: manipulation;
        }
        .btn-submit:active {
            background-color: #1d4ed8;
        }
        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
            margin: 16px 0 10px 0;
        }
        .harness-card {
            background: #ffffff;
            border: 1.5px solid #cbd5e1;
            border-radius: 8px;
            padding: 14px;
            margin-bottom: 12px;
        }
        .harness-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 8px;
            gap: 6px;
        }
        .harness-title-wrap {
            flex: 1;
        }
        .harness-kanban {
            font-size: 17px;
            font-weight: 700;
            color: #0f172a;
        }
        .harness-np {
            font-size: 15px;
            font-weight: 600;
            color: #475569;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .badge-berjalan {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        .badge-selesai {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        .meta-list {
            font-size: 14px;
            color: #475569;
            margin-bottom: 8px;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .meta-list strong {
            color: #1e293b;
        }
        .defect-list {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 10px;
            margin-bottom: 10px;
        }
        .defect-item {
            font-size: 14px;
            color: #334155;
            padding: 4px 0;
            border-bottom: 1px dashed #e2e8f0;
            display: flex;
            align-items: baseline;
            gap: 6px;
        }
        .defect-item:last-child {
            border-bottom: none;
        }
        .defect-code {
            display: inline-block;
            background: #e2e8f0;
            color: #0f172a;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 13px;
            flex-shrink: 0;
        }
        .btn-out {
            width: 100%;
            min-height: 48px;
            font-size: 16px;
            font-weight: 700;
            background-color: #16a34a;
            color: #ffffff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            touch-action: manipulation;
        }
        .btn-out:active {
            background-color: #15803d;
        }
        .empty-history {
            text-align: center;
            color: #64748b;
            padding: 24px;
            background: #ffffff;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            font-size: 15px;
        }
        .pagination-wrapper {
            margin-top: 14px;
            margin-bottom: 24px;
        }
        .uuid-label {
            font-size: 11px;
            color: #94a3b8;
            font-family: monospace;
            word-break: break-all;
            margin-top: 4px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-info">
            <h1 class="header-title">Rework</h1>
            <p class="header-meta">
                User: <strong>{{ session('user_name') }}</strong> | Shift: <strong>{{ session('current_shift', '-') }}</strong> | Role: <strong>{{ session('user_role') }}</strong>
            </p>
        </div>
        <div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn-logout">Logout</button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-error">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-error">
            <ul style="padding-left: 20px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Form In -->
    <div class="card">
        <h2 class="card-title">Mulai Rework (In)</h2>
        <form id="rework_form" action="{{ route('rework.store_in') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="kanban_assy" class="form-label">Kanban Assy *</label>
                <input type="text"
                       id="kanban_assy"
                       name="kanban_assy"
                       class="form-input"
                       placeholder="Scan / ketik Kanban Assy"
                       value="{{ old('kanban_assy') }}"
                       autofocus
                       autocomplete="off"
                       required>
            </div>

            <div class="form-group">
                <label for="nameplate" class="form-label">Nameplate (NP) *</label>
                <input type="text"
                       id="nameplate"
                       name="nameplate"
                       class="form-input"
                       placeholder="Scan / ketik Nameplate"
                       value="{{ old('nameplate') }}"
                       style="text-transform: uppercase;"
                       autocomplete="off"
                       required>
            </div>

            <div class="form-group">
                <label for="defect_search" class="form-label">Defect (Kode / Nama) *</label>
                <div class="search-container">
                    <input type="text"
                           id="defect_search"
                           class="form-input"
                           placeholder="Ketik kode (1A) / nama defect"
                           autocomplete="off">
                    <div id="dropdown_results" class="dropdown-results"></div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" style="font-size: 14px; margin-bottom: 4px;">Defect Terpilih:</label>
                <div id="selected_defects_container" class="chips-container"></div>
            </div>

            <button type="submit" class="btn-submit">Mulai Rework (In)</button>
        </form>
    </div>

    <!-- Riwayat Grouped -->
    <h2 class="section-title">Riwayat Rework</h2>

    @forelse($paginatedGroups as $groupItem)
        @php
            $groupId = $groupItem->rework_group_id;
            $items = $defectsByGroup->get($groupId, collect());
            $first = $items->first();
        @endphp

        @if($first)
            <div class="harness-card">
                <div class="harness-header">
                    <div class="harness-title-wrap">
                        <div class="harness-kanban">{{ $first->kanban_assy ?? '-' }}</div>
                        <div class="harness-np">NP: {{ $first->nameplate_lama ?? '-' }}</div>
                    </div>
                    <div>
                        @if($first->status_rework === 'Selesai')
                            <span class="badge badge-selesai">Selesai</span>
                        @else
                            <span class="badge badge-berjalan">Berjalan</span>
                        @endif
                    </div>
                </div>

                <div class="meta-list">
                    <div>Waktu In: <strong>{{ $first->waktu_in ? \Carbon\Carbon::parse($first->waktu_in)->format('d/m/Y H:i') : '-' }}</strong></div>
                    @if($first->status_rework === 'Selesai')
                        <div>Waktu Out: <strong>{{ $first->waktu_out ? \Carbon\Carbon::parse($first->waktu_out)->format('d/m/Y H:i') : '-' }}</strong></div>
                        <div>Durasi: <strong>{{ $first->durasi_rework_menit ?? 0 }} menit</strong></div>
                    @else
                        <div>Durasi: <strong>Sedang berjalan</strong></div>
                    @endif
                </div>

                <div class="defect-list">
                    @foreach($items as $item)
                        @php
                            $code = $item->subDefectType?->code ?? \App\Support\SubDefectHelper::deriveCode($item->jenis_sub_defect) ?? '-';
                            $name = $item->jenis_sub_defect ?? $item->subDefectType?->name ?? '-';
                        @endphp
                        <div class="defect-item">
                            <span class="defect-code">[{{ $code }}]</span>
                            <span>{{ $name }}</span>
                        </div>
                    @endforeach
                </div>

                @if($first->status_rework === 'Berjalan')
                    <form action="{{ route('rework.store_out', $groupId) }}" method="POST" onsubmit="return confirm('Selesaikan rework untuk harness ini?');">
                        @csrf
                        <button type="submit" class="btn-out">Selesai (Out)</button>
                    </form>
                @endif

                <div class="uuid-label">Group: {{ $groupId }}</div>
            </div>
        @endif
    @empty
        <div class="empty-history">
            Belum ada riwayat rework.
        </div>
    @endforelse

    <div class="pagination-wrapper">
        {{ $paginatedGroups->links() }}
    </div>

    <script>
        (function() {
            const finalAssyDefects = @json($finalAssySubDefects);
            let selectedDefects = [];

            // Restore old selected defects if validation failed
            @if(old('sub_defect_ids'))
                const oldIds = @json(old('sub_defect_ids'));
                if (Array.isArray(oldIds)) {
                    oldIds.forEach(function(id) {
                        const match = finalAssyDefects.find(function(d) { return d.id == id; });
                        if (match) {
                            selectedDefects.push(match);
                        }
                    });
                }
            @endif

            const kanbanInput = document.getElementById('kanban_assy');
            const npInput = document.getElementById('nameplate');
            const defectSearch = document.getElementById('defect_search');
            const dropdown = document.getElementById('dropdown_results');
            const chipsContainer = document.getElementById('selected_defects_container');
            const reworkForm = document.getElementById('rework_form');

            // 1. Enter on Kanban input moves focus to Nameplate
            kanbanInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    npInput.focus();
                }
            });

            // 2. Enter on Nameplate input moves focus to Defect Search
            npInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    defectSearch.focus();
                }
            });

            // Render chips
            function renderChips() {
                chipsContainer.innerHTML = '';
                if (selectedDefects.length === 0) {
                    const emptyText = document.createElement('span');
                    emptyText.style.color = '#94a3b8';
                    emptyText.style.fontSize = '14px';
                    emptyText.textContent = 'Belum ada defect dipilih';
                    chipsContainer.appendChild(emptyText);
                    return;
                }

                selectedDefects.forEach(function(item, index) {
                    const chip = document.createElement('div');
                    chip.className = 'chip';

                    const labelWrap = document.createElement('span');
                    const code = item.code ? '[' + item.code + '] ' : '';
                    labelWrap.textContent = code + item.name;

                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'chip-remove';
                    removeBtn.innerHTML = '&times;';
                    removeBtn.setAttribute('aria-label', 'Hapus defect');
                    removeBtn.addEventListener('click', function(evt) {
                        evt.stopPropagation();
                        selectedDefects.splice(index, 1);
                        renderChips();
                    });

                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'sub_defect_ids[]';
                    hiddenInput.value = item.id;

                    chip.appendChild(labelWrap);
                    chip.appendChild(removeBtn);
                    chip.appendChild(hiddenInput);
                    chipsContainer.appendChild(chip);
                });
            }

            renderChips();

            function getFiltered(query) {
                const q = query.trim().toLowerCase();
                if (!q) return [];
                return finalAssyDefects.filter(function(d) {
                    const code = (d.code || '').toLowerCase();
                    const name = (d.name || '').toLowerCase();
                    return code === q || code.includes(q) || name.includes(q);
                });
            }

            function showDropdown(items) {
                dropdown.innerHTML = '';
                if (items.length === 0) {
                    dropdown.style.display = 'none';
                    return;
                }

                items.slice(0, 10).forEach(function(item) {
                    const div = document.createElement('div');
                    div.className = 'dropdown-item';
                    const code = item.code ? '[' + item.code + '] ' : '';
                    div.textContent = code + item.name;
                    div.addEventListener('click', function() {
                        addDefect(item);
                    });
                    dropdown.appendChild(div);
                });
                dropdown.style.display = 'block';
            }

            function addDefect(item) {
                selectedDefects.push(item);
                renderChips();
                defectSearch.value = '';
                dropdown.style.display = 'none';
                defectSearch.focus();
            }

            defectSearch.addEventListener('input', function() {
                const filtered = getFiltered(this.value);
                showDropdown(filtered);
            });

            defectSearch.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const val = this.value.trim().toLowerCase();
                    if (!val) return;

                    const filtered = getFiltered(val);
                    if (filtered.length > 0) {
                        const exact = filtered.find(function(d) {
                            return (d.code || '').toLowerCase() === val;
                        });
                        addDefect(exact || filtered[0]);
                    }
                }
            });

            // Close dropdown if clicked outside
            document.addEventListener('click', function(e) {
                if (!defectSearch.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.style.display = 'none';
                }
            });

            // Form validation before submit
            reworkForm.addEventListener('submit', function(e) {
                if (selectedDefects.length === 0) {
                    e.preventDefault();
                    alert('Minimal satu defect harus dipilih.');
                    defectSearch.focus();
                }
            });
        })();
    </script>
</body>
</html>
