# 🏢 Bedah Lengkap Arsitektur "Kantor AI 3D" (kantor-ai-template)

Catatan teknis dan arsitektur mendalam dari repositori `malikisaputra-source/kantor-ai-template` untuk panduan membangun kantor AI isometrik 3D sendiri dari nol.

---

## 1. Filosofi & Konsep Utama

1. **Lightweight & Zero 3D Assets**:
   - Tidak menggunakan model 3D eksternal (`.gltf`, `.glb`, atau `.obj`).
   - Semua objek (ruangan, meja, kursi, monitor, tanaman, hingga karakter agen) dibuat dari geometri primitif Three.js (`BoxGeometry`, `CapsuleGeometry`, `CylinderGeometry`, `SphereGeometry`).
   - Tekstur digenerate secara prosedural lewat HTML5 Canvas (tanpa download gambar/tekstur dari internet).
   - Hasil: Sangat ringan, load time instan, FPS stabil bahkan di smartphone spesifikasi menengah ke bawah.

2. **Kombinasi 3D WebGL + 2D HTML/CSS (Layered Canvas)**:
   - Kanvas WebGL hanya bertugas merender objek 3D dan bayangan.
   - Semua teks, label nama agen, balon obrolan (*speech bubbles*), tombol klik, dan Mission Board diposisikan menggunakan elemen HTML biasa di atas kanvas 3D menggunakan proyeksi koordinat `Vector3.project(camera)`.
   - Keuntungan: Teks tetap tajam (*crisp*), ramah aksesibilitas, mudah di-styling dengan CSS flex/grid, serta mudah menerima interaksi klik/sentuh tanpa perlu raycasting 3D yang berat.

3. **Autonomous Office Simulation**:
   - Agen memiliki jadwal kerja (WIB), status aktivitas (`kerja`, `laporan`, `belajar`, `siaga`, `pulang`).
   - Agen bergerak dinamis: mengetik saat jam kerja, membaca buku saat belajar, dan saat senggang (*idle*) berjalan menuju dispenser kopi, sofa, atau Mission Board melalui jalur koridor yang realistis.

---

## 2. Tech Stack & Dependencies

```json
{
  "dependencies": {
    "react": "^19.3.0",
    "react-dom": "^19.3.0",
    "three": "0.170.0"
  },
  "devDependencies": {
    "@types/react": "^19.3.0",
    "@types/react-dom": "^19.3.0",
    "@types/three": "0.170.0",
    "@vitejs/plugin-react": "^6.1.1",
    "typescript": "^7.0.2",
    "vite": "^8.3.1"
  }
}
```

*Tidak menggunakan React Three Fiber (R3F)*. Pengembang memilih **Three.js vanilla di dalam `useEffect` React** untuk kontrol performa render loop dan memori yang lebih presisi (mudah di-dispose saat ganti tema).

---

## 3. Struktur Direktori & Peran Berkas

```text
├── index.html                  # Entry point HTML & font Plus Jakarta Sans
├── package.json
├── vite.config.ts
├── tsconfig.json
├── scripts/
│   ├── rekam.mjs               # Script rendering video reels (CDP + Puppeteer + ffmpeg)
│   └── test.mjs                # Unit test logika jadwal, kalkulasi timelapse, dan status agen
├── src/
│   ├── main.tsx                # Mount React App
│   ├── App.tsx                 # Root UI: HUD, layer 2D, state waktu, tema picker, modal detail
│   ├── OfficeScene.tsx         # Inti Three.js: scene, kamera isometrik, pencahayaan, mesh bangunan & agen
│   ├── config.ts               # Konfigurasi kantor: ruangan, daftar agen, jadwal WIB, babak timelapse
│   ├── office.ts               # State machine agen: kalkulasi jam, status kerja, pathing santai
│   ├── laporan.json            # Data feed harian agen (ringkasan, menunggu, mendesak, metrik)
│   ├── styles.css              # Styling HUD, layout responsif, modal sheet, balon teks 2D
│   └── gaya/                   # Sistem theming 3D (17 variasi gaya visual)
│       ├── tipe.ts             # Interface Kit, Peran objek, Dunia
│       ├── alat.ts             # Builder material, geometri membulat, dan tekstur kanvas
│       ├── daftar.ts           # Registry seluruh tema
│       ├── index.ts            # Factory buatKitGaya()
│       └── *.ts                # Definisi gaya individual (clay, voxel, hologram, angkringan, dll.)
```

---

## 4. Bedah Arsitektur Komponen Utama

### A. Pengaturan Kamera Isometrik & Sutradara (`OfficeScene.tsx`)

Kantor dilihat dari sudut isometrik menggunakan `OrthographicCamera`:
```ts
const camera = new OrthographicCamera(-halfW, halfW, halfH, -halfH, 0.1, 100);
```
- **Kamera Sinematik / Sutradara (*Camera Director*)**:
  - Kamera tidak statis membosankan. Setiap `SHOT = 3.2` detik, kamera secara halus melakukan transisi (*lerp*) antara tampilan lebar (*wide shot*) dan *close-up* ke meja agen yang sedang aktif.
  - Terdapat efek ayunan lembut (*gentle swing*): `Math.sin(t * 0.21)` untuk memberikan nuansa hidup.
  - Deteksi orientasi vertikal (*portrait reels*): jika aspek rasio `< 0.8`, sudut kamera diputar agar gedung memanjang ke bawah layar HP.

### B. Proyeksi 3D ke 2D untuk UI Overlay

Menghubungkan elemen HTML (seperti balon teks dan tag nama) dengan koordinat 3D kepala agen:
```ts
const proj = new Vector3();
const place = (node: HTMLElement | null, at: Vector3) => {
  if (!node) return;
  proj.copy(at).project(camera); // Mengubah koordinat 3D ke NDC (-1 s/d +1)
  const x = ((proj.x + 1) / 2) * el.clientWidth;
  const y = ((1 - proj.y) / 2) * el.clientHeight;
  node.style.transform = `translate3d(${x.toFixed(1)}px, ${y.toFixed(1)}px, 0)`;
};
```
Di setiap frame render:
- Posisi kepala agen diambil (`a.label`).
- Fungsi `place()` mengupdate posisi CSS `translate3d()` elemen HTML tag nama dan balon obrolan secara real-time.

### C. Anatomi Karakter Agen 3D

Setiap agen dibangun secara prosedural dalam sebuah `Group`:
- **Badan**: `CapsuleGeometry(0.23, 0.4)` dengan material berwarna baju agen (`a.warna`).
- **Kepala**: `SphereGeometry(0.22)` dengan warna kulit (`SKIN`).
- **Rambut**: `SphereGeometry(0.235, true)` (setengah bola yang diputar).
- **Lengan & Kaki**: `CapsuleGeometry(0.07, 0.34)` yang ditranslasikan agar titik poros (*pivot point*) berada di bahu dan pinggul.

**Animasi Karakter**:
1. **Mengetik (Kerja/Laporan)**:
   ```ts
   a.armL.rotation.set(-1.15 + 0.12 * Math.sin(t * 16 + a.phase), 0, 0.1);
   a.armR.rotation.set(-1.15 + 0.12 * Math.sin(t * 16 + a.phase + 2), 0, -0.1);
   a.body.position.y = 0.92 + 0.012 * Math.abs(Math.sin(t * 9 + a.phase));
   a.head.rotation.set(0.1 + 0.04 * Math.sin(t * 3 + a.phase), 0.08 * Math.sin(t * 1.3 + a.phase), 0);
   ```
2. **Berjalan (Walking)**:
   - Kaki dan lengan berayun berlawanan fasa: `armL = -0.5 * sin`, `armR = 0.5 * sin`, `legL = 0.6 * sin`, `legR = -0.6 * sin`.
   - Tubuh sedikit memantul vertikal (*bobbing*): `bob = 0.03 * Math.abs(sin)`.
3. **Membaca Buku (Belajar)**:
   - Lengan memegang meja/buku, kepala menunduk ke bawah meja.

### D. Pathfinding & Navigasi Koridor (`pathAt`)

Saat agen santai, agen berjalan ke sofa, dispenser kopi, atau Mission Board. Agar agen tidak berjalan menembus dinding ruangan:
- Didefinisikan garis koridor tengah pada sumbu `Z = 0.05` (posisi pintu antar ruangan).
- Jalur tempuh dipecah menjadi 3 segmen Manhattan:
  1. Meja asal `(x0, z0)` $\rightarrow$ Koridor `(x0, 0.05)`
  2. Susur Koridor `(x0, 0.05)` $\rightarrow$ Titik seberang tujuan `(x1, 0.05)`
  3. Koridor `(x1, 0.05)` $\rightarrow$ Meja/Sofa tujuan `(x1, z1)`

### E. Siklus Siang/Malam (*Day/Night Cycle*)

Waktu kantor dicek setiap pergantian menit:
- Malam ditentukan jika jam $\ge$ 18:30 atau $<$ 06:00.
- Perubahan visual:
  - Cahaya matahari (`DirectionalLight`) meredup dan berubah kebiruan.
  - Langit (`HemisphereLight`) menjadi gelap.
  - Kaca jendela (`glassM`) menyala kebiruan/gelap dari dalam.
  - Lampu gantung dan lampu meja personal agen menyala otomatis (`PointLight`).
  - Monitor memancarkan cahaya (*emissive material*).

---

## 5. Sistem Feed Laporan Agen (`laporan.json`)

Skema data yang dibaca oleh sistem:
```json
{
  "riset": {
    "ringkasan": "Enam paket tender baru ditemukan; dua cocok dengan portofolio.",
    "menunggu": [
      "Persetujuan pengajuan proposal Dinas Pariwisata",
      "Konfirmasi jadwal temu calon mitra"
    ],
    "mendesak": [
      "Batas akhir submission tender pukul 16:00"
    ],
    "angka": [
      { "label": "Tender Dipantau", "nilai": 6 },
      { "label": "Lolos Seleksi", "nilai": 2 }
    ]
  }
}
```
- **`ringkasan`**: Ditampilkan di balon bicara (*speech bubble*) di atas kepala agen (bergantian setiap 2.5 detik).
- **`menunggu`**: Ditampilkan sebagai sticky notes di Mission Board 3D dan badge tanda seru `!` di tag agen.
- **`mendesak`**: Membuat meja agen berkedip warna merah (`#f3355b`) sebagai tanda urgensi.
- **`angka`**: Ditampilkan saat modal/sheet detail agen dibuka.

---

## 6. Perekam Video Reels/Shorts Otomatis (`rekam.mjs`)

Mekanisme perekaman frame demi frame:
1. Menjalankan Google Chrome Headless via parameter Chrome DevTools Protocol (CDP).
2. Membuka URL: `http://localhost:5173/?timelapse=1&rekam=1`.
3. Hook khusus pada `window.__koFrame(t)`:
   - React dipaksa merender frame pada waktu `t` menggunakan `flushSync()`.
   - Mengambil screenshot beresolusi tinggi via `Page.captureScreenshot`.
   - Menghasilkan 900 frame JPEG (30 detik $\times$ 30 FPS).
4. Menjahit frame-frame tersebut menggunakan `ffmpeg`:
   ```bash
   ffmpeg -framerate 30 -i %05d.jpg -c:v libx264 -pix_fmt yuv420p -crf 18 out.mp4
   ```
5. Hasil video sangat mulus tanpa lag/drop frame, terlepas dari spesifikasi kartu grafis komputer.

---

## 7. Roadmap Rekomendasi: Membangun Sendiri dari Nol

Jika Anda ingin membuat sistem kantor AI sendiri:

1. **Tahap 1: Dasar 3D & Isometrik**
   - Setup project React + Vite + TypeScript.
   - Buat satu komponen canvas yang menginisialisasi `Three.WebGLRenderer`, `OrthographicCamera`, dan pencahayaan dasar.
   - Buat lantai dasar (`BoxGeometry`) dan dinding pembatas.

2. **Tahap 2: Karakter & Perabot Prosedural**
   - Buat komponen perabot meja, kursi, dan monitor menggunakan kombinasi balok (`box`).
   - Buat karakter agen sederhana (badan kapsul, kepala bola).

3. **Tahap 3: State Machine & Sinkronisasi Waktu**
   - Buat modul pembaca waktu (jam nyata atau simulasi).
   - Tentukan state agen: bekerja (animasi tangan bergetar), santai (duduk diam atau berdiri).

4. **Tahap 4: Layer 2D HTML di Atas 3D**
   - Implementasikan fungsi proyeksi `Vector3.project(camera)`.
   - Buat komponen tag nama agen yang melayang tepat di atas kepala masing-masing agen.

5. **Tahap 5: Integrasi Data AI & Mission Board**
   - Hubungkan dengan data JSON laporan dari agen AI Anda.
   - Tambahkan efek visual (kedip meja jika urgent, sticky note di papan misi).
