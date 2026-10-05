# 📘 PANDUAN LENGKAP BELAJAR KODE: PROJECT MONITORING DEFECT QA

> **Hai! Selamat datang di panduan belajar kode project Monitoring Defect QA.**  
> Dokumen ini ditulis khusus untuk kamu yang ingin memahami cara kerja aplikasi ini dari nol, baris demi baris, tanpa pusing dengan istilah-istilah rumit.  
> Anggap panduan inTolong buatkan dokumentasi LENGKAP untuk project Monitoring Defect ini, dengan gaya bahasa untuk PEMULA yang sedang belajar ulang coding dari nol.
JANGAN edit, hapus, atau ubah file apa pun — ini murni membaca kode dan menulis dokumentasi baru. Tidak ada perubahan pada codebase.

Buat file baru: docs/PANDUAN_BELAJAR_KODE.md

ATURAN GAYA BAHASA (penting, ikuti ketat):
- Anggap pembacanya baru belajar Laravel dan PHP, jangan asumsikan sudah paham istilah
- Setiap kali pakai istilah teknis (migration, Eloquent, middleware, FK, dll), jelaskan dulu dengan bahasa sederhana / analogi sebelum lanjut
- Pakai kalimat pendek, bertahap, seperti menjelaskan ke teman, bukan seperti manual resmi
- Setiap bagian kode yang dijelaskan, sertakan potongan kode SINGKAT, lalu jelaskan baris demi baris apa fungsinya dengan bahasa awam
- Gunakan analogi dunia nyata kalau membantu (misalnya: "Controller itu seperti resepsionis yang menerima permintaan lalu meneruskan ke bagian yang tepat")
- Hindari paragraf panjang tanpa jeda — pecah jadi poin-poin atau langkah-langkah bernomor

ISI YANG HARUS DIBAHAS:

1. **Gambaran Besar Dulu (sebelum masuk detail)**
   - Apa itu Laravel secara singkat, dan kenapa struktur foldernya seperti itu
   - Alur paling dasar: "kalau user klik sesuatu, apa yang terjadi step-by-step sampai muncul di layar" (routes → controller → model → view), dijelaskan seperti cerita/alur, bukan diagram teknis

2. **Database — dijelaskan sesederhana mungkin**
   - Tabel-tabel yang ada, tapi dijelaskan: "tabel ini isinya apa, kenapa ada, contoh datanya kira-kira seperti apa"
   - Relasi antar tabel dijelaskan pakai analogi (misalnya "satu defect itu 'milik' satu carline, seperti satu barang belanjaan yang masuk satu kategori")

3. **Alur Fitur Utama — jelaskan sebagai cerita, bukan daftar teknis**
   - Ceritakan LANGKAH DEMI LANGKAH apa yang terjadi saat operator input defect: mulai dari buka HP, isi form, submit, sampai datanya nongol di dashboard admin
   - Ceritakan bagaimana dashboard bisa "auto-update" tiap 8 detik (AJAX polling) — jelaskan konsepnya dulu (apa itu AJAX/polling) sebelum tunjukkan kodenya
   - Ceritakan alur shift: dari operator login sampai data shift tersimpan di laporan

4. **Bagian-bagian kode penting, dijelaskan satu-satu**
   - Controller: untuk tiap Controller utama, jelaskan "kalau dipanggil, dia ngapain aja, step by step"
   - Model: jelaskan tiap Model itu representasi tabel apa, dan relasinya ke mana
   - View (Blade): jelaskan bagaimana tampilan dan data itu nyambung

5. **Bagian yang membingungkan / butuh hati-hati** (dijelaskan dengan bahasa "hati-hati, ini beda dari kelihatannya"):
   - Kenapa Final Assy pakai jenis_mobil tapi Pre Assy pakai carline — jelaskan sejarah/alasannya sesederhana mungkin
   - Logic dropdown Final Assy Inspect Type (kombinasi kode + tipe MAZDA) — jelaskan aturannya pelan-pelan
   - Kode yang kemungkinan sudah tidak kepakai (DefectApiController, ExternalApiService) — jelaskan kenapa ini ada tapi mungkin nggak aktif

6. **Istilah-istilah yang sering muncul** — buat semacam "kamus mini" di akhir dokumen, isi istilah teknis yang dipakai di dokumen ini + penjelasan sederhananya (contoh: Migration, Seeder, Eloquent, Middleware, FK, dsb)

Setelah selesai, JANGAN commit dulu. Tunjukkan isi dokumentasinya ke saya untuk saya baca dan pelajari.i seperti teman yang sedang duduk di sampingmu sambil menceritakan bagaimana setiap kepingan puzzle di project ini saling terhubung.

---

## DAFTAR ISI
1. [Gambaran Besar (Sebelum Masuk ke Kode)](#bab-1-gambaran-besar-sebelum-masuk-ke-kode)
2. [Database: Lemari Penyimpanan Data](#bab-2-database-lemari-penyimpanan-data)
3. [Alur Fitur Utama: Kisah di Balik Layar](#bab-3-alur-fitur-utama-kisah-di-balik-layar)
4. [Membedah Kode Penting Baris Demi Baris](#bab-4-membedah-kode-penting-baris-demi-baris)
5. [Bagian Unik yang Butuh Perhatian Khusus (Awas Terkecoh!)](#bab-5-bagian-unik-yang-butuh-perhatian-khusus-awas-terkecoh)
6. [Kamus Mini Istilah Coding](#bab-6-kamus-mini-istilah-coding)

---

## BAB 1: Gambaran Besar (Sebelum Masuk ke Kode)

### 1.1 Apa Itu Laravel dan Kenapa Kita Memakainya?

Bayangkan kamu ingin membangun sebuah mobil:
- **Cara 1 (PHP Murni / Native):** Kamu membuat sendiri mesinnya dari lempengan besi, memotong kaca sendiri, dan merangkai kabel dari nol. Hasilnya lama, rawan korsleting, dan melelahkan.
- **Cara 2 (Pakai Framework Laravel):** Kamu sudah diberi rangka mobil yang kokoh, rem ABS yang teruji aman, dashboard elektrik, dan setir yang presisi. Tugasmu tinggal mendesain bodi luar, menentukan warna kursi, dan mengatur arah jalannya mobil.

**Laravel** adalah "rangka mobil" itu untuk bahasa pemrograman PHP. Laravel sudah menyediakan sistem keamanan, pengatur rute halaman, cara mudah bicara ke database, dan mesin pembuat tampilan.

---

### 1.2 Kenapa Struktur Foldernya Seperti Itu?

Saat pertama kali membuka folder project ini di VS Code, mungkin kamu kaget melihat banyaknya folder. Tenang, kamu tidak perlu menghafal semuanya. Cukup kenali 6 folder utama ini:

| Nama Folder | Peran / Fungsinya | Analogi Dunia Nyata |
| :--- | :--- | :--- |
| `routes/` | Menentukan alamat URL web dan siapa yang mengurusnya | Papan penunjuk arah di jalan raya |
| `app/Http/Controllers/` | Otak pemikir logika aplikasi | Resepsionis & Manajer restoran |
| `app/Models/` | Penghubung antara kode PHP dan tabel database | Petugas gudang yang mencatat stok |
| `resources/views/` | File tampilan HTML yang dilihat user | Piring saji dan etalase makanan |
| `database/migrations/` | Cetak biru struktur tabel database | Denah arsitektur bangunan |
| `database/seeders/` | Pengisi data awal / data contoh | Truk yang memasukkan barang pertama kali |

---

### 1.3 Alur Kerja Paling Dasar: Dari Klik User Sampai Muncul di Layar

Pola ini disebut arsitektur **MVC (Model - View - Controller)**. Mari kita ikuti alurnya seperti cerita sebuah pesanan:

```
[User Klik Sesuatu di Layar]
           │
           ▼
     1. routes/web.php
  (Papan Penunjuk Jalan)
           │
           ▼
     2. Middleware
  (Satpam / Pengecek Tiket)
           │
           ▼
     3. Controller
  (Koki / Resepsionis Utama)
       │              ▲
       │ Meminta data │ Mengembalikan data
       ▼              │
     4. Model ────────┘
  (Petugas Gudang Database)
           │
           ▼
     5. View (Blade)
  (Penata Piring / Tampilan)
           │
           ▼
[Hasil Muncul Indah di Layar User]
```

#### Cerita Langkah Demi Langkah:
1. **User mengeklik link:** Misalnya operator mengeklik tombol *"Input Defect"* di HP-nya. Browser mengirim permintaan alamat URL: `/input-defect`.
2. **Route menyambut:** File `routes/web.php` memeriksa: *"Oh, ada yang mau ke `/input-defect`! Saya teruskan permintaan ini ke ReportController bagian createInputDefect."*
3. **Middleware memeriksa:** Satpam bertanya: *"Tunggu sebentar, apakah user ini sudah login? Apakah dia operator?"* Jika lolos, permintaan diteruskan.
4. **Controller bekerja:** `ReportController` mengumpulkan bahan-bahan: memanggil daftar mobil, jenis cacat, dan nama konveyor.
5. **Model mengambil data:** Controller meminta bantuan `CarType::get()` dan `DefectType::get()`. Model `CarType` mengambil data dari database MySQL lalu menyerahkannya kembali ke Controller.
6. **View menata tampilan:** Controller menyerahkan data itu ke file tampilan `resources/views/input_defect.blade.php`.
7. **Layar tampil:** Browser user menampilkan formulir rapi lengkap dengan pilihan dropdown yang siap diisi.

---

## BAB 2: Database: Lemari Penyimpanan Data

Database kita ibarat lemari arsip raksasa. Lemari ini berisi map-map arsip yang disebut **Tabel**.

### 2.1 Peta Tabel di Database

Berikut adalah tabel-tabel penting dalam project ini:

```
┌─────────────────────────────────────────────────────────────┐
│                    TABEL UTAMA: defects                     │
│  (Menyimpan setiap laporan temuan cacat wiring harness)     │
└──────────────┬──────────────┬──────────────┬──────────────┬─┘
               │              │              │              │
        milik satu     milik satu     milik satu     milik satu
               │              │              │              │
               ▼              ▼              ▼              ▼
        ┌─────────────┐┌─────────────┐┌─────────────┐┌─────────────┐
        │  carlines   ││defect_types ││inspect_proc.││final_assy...│
        │(Jalur kabel)││(Kategori QA)││ (Jenis tes) ││(Khusus Mazda)
        └──────┬──────┘└──────┬──────┘└─────────────┘└─────────────┘
               │              │
          milik satu      punya banyak
               │              │
               ▼              ▼
        ┌─────────────┐┌─────────────┐
        │  car_types  ││sub_defect...│
        │(Jenis Mobil)││(Detail cacat│
        └─────────────┘└─────────────┘
```

---

### 2.2 Penjelasan Tabel Satu per Satu

#### 1. Tabel `users` (Data Pengguna)
- **Tujuannya:** Menyimpan siapa saja yang boleh masuk ke sistem (Admin QA atau Operator).
- **Kenapa sederhana?** Di pabrik, operator butuh login secepat mungkin. Karena itu, login tidak menggunakan password panjang dan email yang rumit, melainkan cukup **Nama + PIN Angka** (4 digit untuk Admin, 6 digit untuk Operator).
- **Contoh Isinya:**
  - `name`: "Agus Operator"
  - `pin`: "123456"
  - `role`: "User" (artinya Operator)

#### 2. Tabel `defects` (Jantung Aplikasi)
- **Tujuannya:** Menyimpan setiap kali ada kabel atau komponen cacat yang ditemukan.
- **Tabel ini memiliki dua generasi kolom:**
  - **Generasi Awal (Kolom Teks Bebas):** `jenis_assy`, `jenis_mobil`, `conveyor`, `jenis_defect`, `quantity`, `shift`, `user_name`, `waktu`. Kolom-kolom ini berupa teks biasa agar cepat dibuat di awal project.
  - **Generasi Baru / Master Data (Kolom Relasi / ID):** `carline_id`, `defect_type_id`, `sub_defect_type_id`, `inspect_process_type_id`, `final_inspect_type_id`, `inspect_quantity`, `ditemukan_oleh`, `pattern`. Kolom-kolom ini menyimpan angka ID yang merujuk ke tabel master agar datanya baku dan tidak ada salah ketik.
- **Contoh Isinya:**
  - `waktu`: "2026-09-23 14:15:00"
  - `user_name`: "Budi QA"
  - `shift`: "1A"
  - `jenis_assy`: "Final Assy"
  - `jenis_mobil`: "TOYOTA"
  - `conveyor`: "664W-C5"
  - `jenis_defect`: "DAMAGE/DEFORM/BROKEN PART"
  - `jenis_sub_defect`: "2.A - DAMAGE CLIP"
  - `quantity`: 2
  - `inspect_quantity`: 50

#### 3. Tabel `activity_logs` (CCTV Digital)
- **Tujuannya:** Mencatat jejak audit digital. Siapa melakukan apa, kapan, dan dari komputer mana.
- **Kenapa penting?** Jika ada laporan defect yang tiba-tiba diedit atau dihapus, Admin bisa mengecek di sini.
- **Contoh Isinya:**
  - `waktu`: "2026-09-23 14:16:02"
  - `user_name`: "Admin QA"
  - `jenis_aksi`: "Delete Report"
  - `aktivitas`: "Menghapus laporan defect Final Assy - TOYOTA"
  - `ip_address`: "192.168.1.15"

#### 4. Tabel-Tabel Master Data (Data Acuan)
Tabel master adalah data rujukan yang jarang berubah, diatur oleh Admin:
- `car_types`: Daftar merek mobil (`TOYOTA`, `NISSAN`, `MAZDA`).
- `carlines`: Daftar jalur conveyor atau lini pengerjaan kabel (misalnya `664W-C5`, `Battery Cable`, `J72A`).
- `defect_types`: Kelompok besar jenis cacat (misalnya `INSER CIRCUIT`, `MISSING PART`, `CRIMPING`).
- `sub_defect_types`: Rincian cacat di dalam kelompok tersebut (misalnya di bawah `MISSING PART` ada `MISSING CLIP`, `MISSING TUBE`).
- `inspect_process_types`: Jenis proses inspeksi Pre Assy (misalnya `Cutting`, `Manual Crimping`, `Bonder`, `Heatshrink`).
- `final_assy_inspect_types`: Kode tipe inspeksi khusus Final Assy untuk mobil Mazda (misalnya `67120 - AB6 EXTEND LHD`).

---

### 2.3 Konsep Relasi Antar Tabel (Bahasa Sederhana)

Di database relasional, tabel saling terhubung menggunakan **Foreign Key (FK)**.
- **Foreign Key** itu ibarat **Nomor KTP**.
- Daripada menuliskan nama mobil "TOYOTA MOTOR CORP INDONESIA CABANG SURABAYA" berulang kali di jutaan baris defect, kita cukup menuliskan angka `car_type_id: 1`.
- Jika ingin tahu detail mobil nomor 1 itu apa, komputer tinggal melirik ke tabel `car_types`.

#### Dua Relasi yang Paling Sering Kamu Temukan di Project Ini:
1. **One-to-Many (`hasMany`): Satu induk punya banyak anak.**
   - Satu Merek Mobil (`CarType`) punya banyak Konveyor (`carlines`).
   - Kode di model `CarType.php`:
     ```php
     public function carlines()
     {
         return $this->hasMany(Carline::class);
     }
     ```
2. **Many-to-One (`belongsTo`): Setiap anak merujuk ke satu induk.**
   - Satu Konveyor (`Carline`) adalah milik satu Merek Mobil (`CarType`).
   - Kode di model `Carline.php`:
     ```php
     public function carType()
     {
         return $this->belongsTo(CarType::class);
     }
     ```

---

## BAB 3: Alur Fitur Utama: Kisah di Balik Layar

Mari kita lihat bagaimana fitur-fitur aplikasi ini bekerja melalui skenario kehidupan nyata di pabrik.

---

### 3.1 Cerita 1: Operator Melaporkan Defect dari HP

```
[Operator Buka HP]
       │
       ▼
1. Login (Nama + Shift + PIN) ───► Disimpan di Session Browser
       │
       ▼
2. Buka Form Input ──────────────► Form Dinamis (Pilih Final Assy / Pre Assy)
       │
       ▼
3. Isi Data Cacat ───────────────► Tanggal, Jam, Defect, Sub-Defect, Qty
       │
       ▼
4. Konfirmasi (Step 2) ──────────► Periksa Ulang Data Tanpa Salah Ketik
       │
       ▼
5. Kirim (Submit) ───────────────► Masuk ke Database & Ditulis di Log CCTV
       │
       ▼
[Nongol di Dashboard Admin!]
```

1. **Operator Menuju Lokasi Kerja:**  
   Operator membuka web monitoring di browser HP-nya. Muncul halaman `/login`.  
   Operator mengetik namanya, memilih shift kerjanya saat itu (misalnya: `1A`), dan memasukkan PIN 6 digit.
2. **Session Dibuat:**  
   Server memeriksa kecocokan Nama dan PIN. Begitu cocok, server membuat **Session** (semacam gelang tanda pengenal digital yang ditempelkan di browser). Gelang ini mengingat: *Nama: Joko, Shift: 1A, Role: User*.
3. **Membuka Form Input (`/input-defect`):**  
   Operator mengeklik tombol *"Input Defect"*. Form ini pintar (menggunakan Alpine.js):
   - Jika Operator memilih **Final Assy**, layar langsung memunculkan pilihan: *Jenis Mobil (Toyota/Nissan/Mazda)* dan *Konveyor*.
   - Jika Operator memilih **Pre Assy**, kolom Jenis Mobil langsung disembunyikan dan diganti menjadi pilihan *Carline* serta *Quantity Inspect Type*.
4. **Memilih Defect & Sub-Defect Berjenjang:**  
   Saat Operator memilih Jenis Defect `MISSING PART`, dropdown Sub-Defect otomatis hanya menampilkan pilihan barang-barang yang hilang (Clip, Grommet, Fuse, dll). Jika memilih `LAIN-LAIN`, kotak teks khusus langsung muncul agar operator bisa mengetik bebas.
5. **Konfirmasi Langkah 2 (Cegah Salah Input):**  
   Operator mengeklik *"Lanjut ke Konfirmasi"*. Layar berpindah ke Step 2 untuk memperlihatkan ringkasan data yang rapi. Operator memastikan jumlah cacat sudah benar.
6. **Kirim Laporan:**  
   Operator mengeklik *"Kirim Laporan"*. Data dikirim dengan aman lewat metode `POST`. Server menyimpan data ke tabel `defects`, otomatis mencatat aktivitas di tabel `activity_logs`, dan mengarahkan Operator kembali ke halaman riwayat pribadinya (`operator_home`) dengan pesan hijau: *"Laporan defect berhasil disimpan!"*.

---

### 3.2 Cerita 2: Dashboard Admin Auto-Update Tiap 8 Detik (AJAX Polling)

Pernahkah kamu memesan makanan di aplikasi ojek online, di mana posisi driver bergerak sendiri di peta tanpa kamu harus merefresh layar HP-mu? Itulah keajaiban **AJAX**.

#### Apa Itu AJAX dan Apa Itu Polling?
- **Cara Tradisional (Tanpa AJAX):** Untuk melihat apakah ada data baru, Admin harus menekan tombol reload/refresh browser (`F5`). Layar akan berkedip putih, halaman dimuat ulang dari awal, dan posisi scroll kembali ke atas. Sangat tidak nyaman!
- **AJAX (Asynchronous JavaScript and XML):** Browser diam-diam mengirim kurir lewat pintu belakang untuk bertanya ke server: *"Hei server, ada data baru nggak?"*. Ketika server menjawab, browser hanya memperbarui angka atau tabel yang berubah saja, tanpa me-reload seluruh halaman!
- **Polling (Tanya Berkala):** Seperti anak kecil di mobil yang setiap 8 detik bertanya: *"Ibu, sudah sampai belum?"*. Browser komputer Admin menjalankan pengingat waktu (timer) otomatis setiap 8000 milidetik (8 detik) untuk meminta data terbaru.

#### Potongan Kodenya di `resources/views/dashboard.blade.php`:

```javascript
// Timer otomatis berjalan tiap 8 detik (8000 ms)
pollingTimer = setInterval(() => {
    fetchStats();         // 1. Minta 4 angka kotak atas (Total Defect, Hari Ini, User Aktif)
    fetchChartsData();    // 2. Minta data grafik terbaru
    fetchRecentDefects(); // 3. Minta 4 baris tabel defect paling baru
}, 8000);
```

#### Apa yang Terjadi Saat Data Baru Ditemukan?
1. Fungsi `fetchStats()` mengirim permintaan ke URL `/api/dashboard/stats`.
2. Server mengirimkan jawaban ringkas berbentuk **JSON** (format teks standar pertukaran data), contohnya:
   ```json
   {
       "totalDefect": 154,
       "defectToday": 12,
       "activeUsers": 3,
       "totalUsers": 8
   }
   ```
3. JavaScript membandingkan: *"Apakah angka Total Defect di layar sama dengan 154?"*
4. Jika berbeda, JavaScript mengubah angkanya seketika dan menambahkan efek animasi kedip (`poll-updated`) agar Admin langsung sadar ada data yang baru saja masuk.

---

### 3.3 Cerita 3: Alur Shift Kerja Operator

Di industri perakitan kabel mobil, jam kerja dibagi dalam beberapa regu giliran kerja yang disebut **Shift** (contoh di pabrik ini: `1A`, `1B`, `2A`, `2B`).

#### Kenapa Kolom Shift di Tabel `users` Pernah Dihapus?
- **Dulu:** Kolom `shift` disimpan permanen di tabel `users`.
- **Masalahnya:** Di dunia nyata, operator tidak selamanya bekerja di Shift 1A. Minggu ini dia masuk pagi (1A), minggu depan rolling ke malam (2B). Jika shift disimpan di profil user, Admin harus mengedit profil ratusan operator setiap minggu!
- **Solusinya (Sangat Elegan):**  
  Shift dikeluarkan dari tabel profil `users`. Shift dijadikan pilihan wajib **hanya saat operator hendak login** di pagi/malam hari.
  Pilihan shift itu disimpan sementara di dalam **Session** operator:
  ```php
  session([
      'logged_in'     => true,
      'user_name'     => $user->name,
      'current_shift' => $credentials['shift'], // Disimpan di sesi saat ini!
      'user_role'     => 'User',
  ]);
  ```
- **Hasilnya:** Setiap kali operator tersebut menginput defect pada shift itu, controller otomatis mengambil nilai shift dari session:
  ```php
  $shift = session('current_shift', '1A');
  ```
  Operator tidak perlu memilih shift berulang-ulang di setiap form, dan data laporan tetap mencatat shift kerja dengan 100% akurat.

---

## BAB 4: Membedah Kode Penting Baris Demi Baris

Sekarang saatnya kita melihat langsung kode PHP yang membuat semua keajaiban di atas terjadi. Jangan takut, kita baca pelan-pelan seperti membaca resep masakan.

---

### 4.1 Controller (Pengatur Logika)

#### 1. `app/Http/Controllers/AuthController.php` (Pintu Masuk & Keluar)
Fungsi `login()` menangani pengecekan keamanan saat tombol *"Masuk"* ditekan:

```php
// Baris 16: Cek apakah yang mau login itu operator biasa atau admin
$role = $request->input('login_type', 'user');

if ($role === 'user') {
    // Baris 19-23: Validasi input: Nama wajib ada, shift harus 1A/1B/2A/2B, PIN wajib 6 digit
    $credentials = $request->validate([
        'name'  => 'required|string',
        'shift' => 'required|string|in:1A,1B,2A,2B',
        'pin'   => 'required|string|size:6',
    ]);

    // Baris 25-27: Cari di database user yang punya role 'User' dan namanya cocok (tanpa peduli huruf besar/kecil)
    $user = User::where('role', 'User')
        ->whereRaw('LOWER(name) = ?', [strtolower(trim($credentials['name']))])
        ->first();

    // Baris 29-33: Kalau user tidak ketemu atau PIN-nya tidak pas, tolak dan beri pesan error
    if (!$user || $user->pin !== $credentials['pin']) {
        throw ValidationException::withMessages([
            'name' => 'Nama atau PIN salah.',
        ]);
    }

    // Baris 35-40: Kalau cocok, buat kartu tanda pengenal (Session)
    session([
        'logged_in'     => true,
        'user_name'     => $user->name,
        'current_shift' => $credentials['shift'],
        'user_role'     => 'User',
    ]);

    // Baris 42: Lindungi session dari serangan pembajakan sesi (Session Fixation)
    $request->session()->regenerate();

    // Baris 44: Arahkan operator masuk ke halaman berandanya
    return redirect()->route('operator.home');
}
```

---

#### 2. `app/Http/Controllers/ReportController.php` (Pusat Pengolahan Laporan Defect)
Ini adalah controller paling lengkap dalam project ini. Mari kita bedah fungsi `storeInputDefect()` yang bertugas menyimpan laporan temuan cacat:

```php
public function storeInputDefect(Request $request)
{
    // 1. Cek keamanan: Jangan layani jika user belum login
    if (!session('logged_in')) {
        return redirect()->route('login');
    }

    // 2. Periksa kelengkapan formulir secara ketat
    $validated = $request->validate([
        'type'                     => 'required|string|in:Final Assy,Pre Assy',
        // Jika type adalah Final Assy, maka jenis_mobil dan conveyor WAJIB diisi
        'jenis_mobil'              => 'required_if:type,Final Assy|nullable|string|max:255',
        'conveyor'                 => 'required_if:type,Final Assy|nullable|string|max:255',
        'tanggal'                  => 'required|date',
        'jam'                      => 'required|date_format:H:i',
        'jumlah'                   => 'required|integer|min:1',
        // Jika type adalah Pre Assy, maka carline_id WAJIB diisi
        'carline_id'               => 'required_if:type,Pre Assy|nullable|exists:carlines,id',
        // Khusus mobil MAZDA pada Final Assy, final_inspect_type_id WAJIB diisi
        'final_inspect_type_id'    => 'required_if:jenis_mobil,MAZDA|nullable|exists:final_assy_inspect_types,id',
    ]);

    // 3. Ambil data operator dari session yang sedang aktif
    $userName = session('user_name', 'Operator');
    $shift = session('current_shift', '1A');
    $isPreAssy = $validated['type'] === 'Pre Assy';

    // 4. Perintahkan Model Defect untuk menulis baris baru ke tabel MySQL
    $defect = Defect::create([
        // Gabungkan tanggal dan jam menjadi format waktu lengkap (Y-m-d H:i:s)
        'waktu'                    => Carbon::parse($validated['tanggal'] . ' ' . $validated['jam']),
        'user_name'                => $userName,
        'shift'                    => $shift,
        'jenis_assy'               => $validated['type'],
        // Jika Pre Assy, jenis mobil dan konveyor dikosongkan (null)
        'jenis_mobil'              => $isPreAssy ? null : ($validated['jenis_mobil'] ?? null),
        'conveyor'                 => $isPreAssy ? null : ($validated['conveyor'] ?? null),
        'quantity'                 => $validated['jumlah'],
        'carline_id'               => $validated['carline_id'] ?? null,
        'final_inspect_type_id'    => $validated['final_inspect_type_id'] ?? null,
        // ... field lainnya
    ]);

    // 5. Catat jejak aksi ini ke tabel activity_logs (CCTV audit)
    ActivityLog::create([
        'waktu'        => now(),
        'user_name'    => $userName,
        'jenis_aksi'   => 'Create Report',
        'aktivitas'    => "Melaporkan defect {$validated['type']} - Jumlah {$validated['jumlah']}",
        'ip_address'   => $request->ip() ?? '127.0.0.1',
    ]);

    // 6. Kembalikan operator ke beranda dengan pesan sukses
    return redirect()->route('operator.home')->with('success', 'Laporan defect berhasil disimpan!');
}
```

---

#### 3. `app/Http/Controllers/DashboardController.php` (Dapur Olahan Grafik)
Fungsi `index()` menyiapkan ringkasan data saat Admin membuka halaman dashboard:

```php
// Hitung total seluruh kuantitas defect dari awal masa pabrik
$totalDefect = Defect::sum('quantity');

// Hitung defect khusus yang terjadi hari ini saja
$defectToday = Defect::whereDate('waktu', Carbon::today())->sum('quantity');

// Ambil 4 data defect paling baru untuk tabel kecil di bawah grafik
$recentDefects = Defect::orderBy('waktu', 'desc')->take(4)->get();

// Olah data untuk grafik garis (Line Chart) 24 jam hari ini, 7 hari minggu ini, dan bulan ini
$monthRecords = Defect::where('waktu', '>=', Carbon::now()->startOfMonth())->get();
$finalAssyChart = $this->buildAssyChartData('Final Assy', $monthRecords, $startOfWeek);
$preAssyChart   = $this->buildAssyChartData('Pre Assy', $monthRecords, $startOfWeek);

// Kirim semua data matang ini ke tampilan resources/views/dashboard.blade.php
return view('dashboard', compact(
    'totalDefect', 'defectToday', 'recentDefects', 'finalAssyChart', 'preAssyChart'
));
```

---

### 4.2 Model (Jembatan Database)

Model adalah perwakilan sebuah tabel di dunia PHP. Semua baris di tabel `defects` diwakili oleh file `app/Models/Defect.php`.

Mari kita lihat isi pentingnya:

```php
class Defect extends Model
{
    use HasFactory;

    // $fillable: Daftar kolom yang diizinkan untuk diisi data secara massal (Mass Assignment)
    // Ini fitur keamanan Laravel agar orang jahat tidak bisa menyisipkan kolom sembarangan
    protected $fillable = [
        'waktu', 'user_name', 'shift', 'jenis_assy', 'jenis_mobil',
        'conveyor', 'quantity', 'carline_id', 'final_inspect_type_id'
        // ...
    ];

    // $casts: Mengubah tipe data dari database menjadi tipe data PHP otomatis
    // Kolom 'waktu' otomatis menjadi objek Carbon (mudah diformat tgl/jam)
    // Kolom 'quantity' otomatis dipastikan bertipe angka bulat (integer)
    protected $casts = [
        'waktu'    => 'datetime',
        'quantity' => 'integer',
    ];

    // Relasi: Menjelaskan bahwa setiap data Defect ini terikat ke satu baris data Carline
    public function carline()
    {
        return $this->belongsTo(Carline::class);
    }

    // Accessor: Cara cerdas mengambil nama jenis mobil lewat relasi carline
    // Contoh pemanggilan di kode: $defect->car_type_name
    public function getCarTypeNameAttribute(): ?string
    {
        return $this->carline?->carType?->name;
    }
}
```

---

### 4.3 View (Tampilan Blade & Interaksi Alpine.js)

Tampilan di Laravel dibuat menggunakan **Blade** (file berekstensi `.blade.php`).

#### Kenapa Menggunakan Blade?
Blade memungkinkan kita menggabungkan tag HTML biasa dengan perintah PHP secara bersih:
- `@if ($kondisi) ... @endif`: Menampilkan sesuatu hanya jika kondisi benar.
- `@foreach ($items as $item) ... @endforeach`: Mengulang baris tabel sebanyak data yang ada.
- `{{ $nama }}`: Menampilkan isi variabel secara aman (otomatis mencegah serangan hacker XSS).

#### Contoh Hubungan Data dan Tampilan:
Di Controller kita menulis:
```php
$users = User::orderBy('name')->get();
return view('add_account', compact('users'));
```
Maka di file `resources/views/add_account.blade.php`, kita tinggal menampilkan daftarnya:
```html
@foreach ($users as $user)
    <tr>
        <td>{{ $user->name }}</td>
        <td>{{ $user->role }}</td>
        <td>{{ $user->pin }}</td>
    </tr>
@endforeach
```

#### Keajaiban Alpine.js di Halaman Form (`input_defect.blade.php`):
Project ini menggunakan pustaka JavaScript ringan bernama **Alpine.js** untuk membuat form menjadi interaktif di HP tanpa perlu reload:
- `x-data="defectForm()"`: Mengaktifkan memori lokal form di browser.
- `x-show="form.type === 'Final Assy'"`: Sembunyikan elemen ini jika yang dipilih adalah Pre Assy, tampilkan jika Final Assy.
- `x-model="form.jenis_mobil"`: Mengikat pilihan dropdown langsung ke variabel form secara real-time.

---

## BAB 5: Bagian Unik yang Butuh Perhatian Khusus (Awas Terkecoh!)

Ada 3 bagian di project ini yang sering membuat bingung programmer pemula jika hanya melihat sekilas. Mari kita kupas alasannya!

---

### 5.1 Kenapa Final Assy Pakai `jenis_mobil`, Tapi Pre Assy Pakai `carline`?

Jika kamu membuka form input atau tabel laporan, kamu akan melihat perbedaan mencolok:
- **Final Assy:** Memilih Jenis Mobil (Toyota / Nissan / Mazda) lalu memilih Konveyor.
- **Pre Assy:** Tidak memilih Jenis Mobil, melainkan langsung memilih Carline.

#### Kenapa Bisa Begitu? (Sejarah & Alur Pabrik)
1. **Di Pabrik Wiring Harness (Kabel Mobil):**
   - **Proses Pre Assy (Persiapan Awal):** Kabel gelondongan dipotong-potong (`cutting`), dikupas kulitnya, dan dipasang terminal logam (`crimping`). Pada tahap ini, kabel masih berupa potongan-potongan pendek. Potongan kabel ini **belum menjadi sebuah mobil utuh**, melainkan dikerjakan dalam kelompok mesin / lini kerja yang dinamakan **Carline** (misalnya lini `852W/853W`, `Battery Cable`, `DFM`, `Toyota Gedung C`).
   - **Proses Final Assy (Perakitan Akhir):** Ribuan potongan kabel dari Pre Assy tadi dirangkai di papan perakitan besar berjalan (konveyor) menjadi satu set kabel mobil utuh. Pada tahap ini, rakitan kabel sudah jelas milik mobil apa (`TOYOTA`, `NISSAN`, atau `MAZDA`).
2. **Dampaknya pada Desain Database:**
   - Karena Carline di Pre Assy tidak selalu terikat langsung pada satu merek mobil tunggal, maka pada migrasi database dibuat aturan: **kolom `car_type_id` di tabel `carlines` boleh bernilai NULL (kosong)**:
     ```php
     // File: 2026_09_16_000001_make_car_type_id_nullable_in_carlines_table.php
     Schema::table('carlines', function (Blueprint $table) {
         $table->foreignId('car_type_id')->nullable()->change();
     });
     ```
   - Di form input:
     - Jika memilih **Pre Assy** ➔ ambil daftar carline yang `car_type_id`-nya NULL (`PreAssyCarlineSeeder`).
     - Jika memilih **Final Assy** ➔ pilih merek mobil dulu di tabel `car_types`, lalu sistem menampilkan konveyor terkait.

---

### 5.2 Logic Dropdown Final Assy Inspect Type (Khusus Mobil MAZDA)

Di form Final Assy, ada dropdown misterius bernama **"Quantity Inspect Type"**. Dropdown ini tidak muncul saat memilih Toyota atau Nissan. Dia **hanya muncul jika jenis mobil yang dipilih adalah MAZDA**.

#### Kenapa Hanya MAZDA?
Di pabrik perakitan harness pelanggan Mazda, standar penjaminan kualitas (Quality Assurance) mewajibkan inspektor mencatat kode nomor part pemeriksaan khusus (kombinasi nomor 5 digit + tipe kabin stir mobil LHD/RHD).

Contoh datanya (dari `MasterDataSeeder.php`):
- `67120 - AB6 EXTEND LHD` (Stir Kiri / Left Hand Drive)
- `67120 - AB6 EXTEND RHD` (Stir Kanan / Right Hand Drive)
- `67240 - AB6 EXTEND LHD`
- `67550 - AB6 EXTEND RHD`

#### Aturan Kodenya di Form dan Controller:
1. **Di Tampilan (`input_defect.blade.php`):**
   ```html
   <!-- Hanya tampil jika Final Assy DAN mobil MAZDA -->
   <div x-show="form.type === 'Final Assy' && form.jenis_mobil === 'MAZDA'">
       <label>QUANTITY INSPECT TYPE *</label>
       <select x-model="form.final_inspect_type_id" ...>
   </div>
   ```
2. **Di Validasi Controller (`ReportController.php`):**
   ```php
   // Wajib diisi HANYA JIKA jenis_mobil bernilai 'MAZDA'
   'final_inspect_type_id' => 'required_if:jenis_mobil,MAZDA|nullable|exists:final_assy_inspect_types,id',
   ```
3. **Pembersihan Otomatis:**  
   Jika operator tadinya memilih MAZDA lalu berubah pikiran menggantinya ke TOYOTA, JavaScript di form otomatis mengosongkan kembali kolom `final_inspect_type_id` agar data Toyota tidak tercemar oleh kode Mazda.

---

### 5.3 Kode yang Kemungkinan Sudah Tidak Aktif / Cadangan (Legacy Code)

Ketika membaca kode, kamu akan menemukan dua file ini dan mungkin bertanya: *"Kapan file ini dipakai?"*

#### 1. `app/Http/Controllers/Api/DefectApiController.php`
- **Kenapa ini ada?**  
  Pada awal perancangan sistem, ada rencana pembuatan aplikasi Android/iOS menggunakan framework Flutter untuk operator input defect lewat aplikasi HP native (rute: `Route::post('/defects', ...)`).
- **Kondisi sekarang:**  
  Tim akhirnya memutuskan membuat antarmuka Web Responsif (`resources/views/input_defect.blade.php`) yang bisa langsung dibuka lewat browser Chrome/Safari HP tanpa perlu menginstal aplikasi. Oleh karena itu, controller ini lebih bersifat cadangan atau arsip jika suatu saat aplikasi Flutter kembali dikembangkan.

#### 2. `app/Services/ExternalApiService.php`
- **Kenapa ini ada?**  
  Class ini dibuat untuk menyinkronkan (download & upload) data defect dengan server pusat lain bernama `qa-backend` melalui HTTP request:
  ```php
  $response = Http::timeout(10)->get($apiUrl . '/reports');
  ```
- **Kondisi sekarang:**  
  Di berbagai controller (seperti `DashboardController` dan `ReportController`), pemanggilan fungsi ini selalu dibungkus dengan blok pengaman `try-catch`:
  ```php
  try {
      ExternalApiService::syncFromApi();
  } catch (\Exception $e) {
      Log::warning('External API sync skipped - ' . $e->getMessage());
  }
  ```
  Artinya: Jika server eksternal tersebut sedang mati atau alamatnya tidak diisi di file `.env`, aplikasi monitoring lokal kita **tetap berjalan 100% normal** dengan membaca database lokal MySQL sendiri tanpa error.

---

## BAB 6: Kamus Mini Istilah Coding

Simpan bab ini sebagai contekan cepat setiap kali kamu lupa arti sebuah istilah teknis:

| Istilah | Apa Artinya? | Penjelasan Sangat Sederhana |
| :--- | :--- | :--- |
| **Framework** | Kerangka Kerja | Paket pondasi kode siap pakai agar kita tidak perlu membuat roda dari nol (contoh: Laravel). |
| **Route** | Rute / Jalur | Daftar yang memetakan alamat URL di browser ke fungsi mana kode harus dieksekusi. |
| **Controller** | Pengendali | Manajer logika yang menerima permintaan user, mengolah data, dan memutuskan tampilan apa yang dikirim balik. |
| **Model** | Model Data | Kode PHP yang bertugas mewakili dan berkomunikasi dengan tabel database tertentu. |
| **Migration** | Migrasi Database | File skrip pengendali versi database. Berguna untuk membuat, mengubah, atau menghapus tabel secara otomatis tanpa klik-klik di phpMyAdmin. |
| **Seeder** | Pembibit Data | Skrip untuk menyuntikkan data awal atau data uji coba ke dalam tabel database. |
| **Eloquent** | Eloquent ORM | Fitur pintar Laravel untuk mengambil data database menggunakan objek PHP biasa tanpa perlu mengetik kueri SQL panjang (`SELECT * FROM ...`). Cukup ketik: `Defect::all()`. |
| **Middleware** | Perangkat Tengah | Satpam pemeriksa di gerbang rute. Tugasnya memeriksa apakah user berhak lewat sebelum mencapai controller. |
| **Session** | Sesi Pengguna | Tempat penyimpanan data sementara di server untuk mengingat siapa yang sedang login selama browser belum ditutup. |
| **Primary Key (PK)** | Kunci Utama | Kolom identitas unik pada setiap baris data di tabel (biasanya kolom `id`). Tidak boleh ada dua baris dengan PK yang sama. |
| **Foreign Key (FK)** | Kunci Tamu | Kolom di suatu tabel yang menyimpan Primary Key milik tabel lain untuk menghubungkan keduanya (relasi). |
| **Blade** | Mesin Template Laravel | Format file `.blade.php` yang memudahkan penulisan HTML yang dipadukan dengan logika PHP sederhana. |
| **Alpine.js** | Pustaka JavaScript Ringan | Alat untuk membuat tampilan web hidup dan interaktif (show/hide elemen, ganti langkah step form) langsung dari tag HTML. |
| **AJAX** | Panggilan Latar Belakang | Teknik JavaScript untuk meminta data ke server secara diam-diam tanpa perlu memuat ulang (refresh) halaman. |
| **Polling** | Tanya Berkala | Teknik AJAX yang memanggil server berulang-ulang setiap interval waktu tertentu (misalnya tiap 8 detik). |
| **JSON** | JavaScript Object Notation | Format teks standar yang sangat ringkas untuk mengirim data bolak-balik antara server dan browser. |
| **CSRF Token (`@csrf`)**| Kunci Anti Pemalsuan | Token rahasia yang disisipkan di setiap form Laravel untuk mencegah website jahat mengirimkan aksi palsu atas nama user kita. |
| **Validation** | Pemeriksaan Data | Aturan ketat di Controller untuk memastikan data yang diinput user tidak kosong, tidak salah format, dan aman sebelum disimpan. |

---

> **Tips Belajar:**  
> Jika kamu ingin mencoba mengubah sesuatu, mulailah dari hal kecil:  
> 1. Ubah teks label di view (`resources/views/`).  
> 2. Coba tambahkan satu filter pencarian baru di Controller.  
> 3. Buka file migration dan pelajari bagaimana kolom-kolomnya dirancang.  
>  
> *Selamat belajar, kamu pasti bisa menguasai project ini dengan baik!* 🚀
