# MCE VIRTUAL HEADQUARTERS
## Masterplan Kawasan dan Spesifikasi Teknis Kompleks 3D

**Dokumen:** MCE Virtual Headquarters — Campus Masterplan & 3D Technical Specification  
**Status:** Design Baseline — Approved Concept  
**Versi:** 1.0  
**Sistem:** MCE — Mitra Creative Environment

---

# 1. VISI DAN KONSEP

MCE Virtual Headquarters merupakan representasi digital sebuah kompleks perkantoran milik MCE yang dibangun dalam bentuk lingkungan 3D isometrik, menyerupai miniatur kawasan perkantoran yang hidup.

Kompleks terdiri atas beberapa gedung terpisah berdasarkan fungsi organisasi, jalan utama, jalan penghubung, taman, plaza, area parkir, fasilitas umum, dan infrastruktur pendukung.

Setiap gedung memiliki fungsi tertentu sesuai struktur perusahaan MCE.

Lingkungan ini mempunyai dua perspektif:

1. **Public Observation Experience:** masyarakat dapat melihat kehidupan kompleks, mengamati agent di setiap divisi, dan menyaksikan aktivitas mereka secara visual.
2. **Private Operations Experience:** Owner dan personel berwenang dapat melihat kondisi operasional secara menyeluruh, termasuk detail pekerjaan, tugas, aktivitas, dan koordinasi agent.

Prinsip utama:

- Satu kompleks 3D dengan identitas visual yang konsisten.
- Gedung dan fasilitas memiliki lokasi tetap.
- Agent dapat bergerak antar-ruangan dan antar-gedung.
- Aktivitas agent mengikuti status runtime sebenarnya.
- Publik dapat melihat agent bekerja tetapi tidak mengetahui detail pekerjaannya.
- Dashboard internal mempunyai akses visualisasi dan informasi yang lebih lengkap.
- Lingkungan tetap dapat digunakan meskipun layanan AI atau koneksi realtime sedang tidak tersedia.

# 2. KARAKTERISTIK DESAIN

## 2.1 Susunan Kawasan

Menggunakan District Layout.

Kompleks dibagi menjadi beberapa kawasan berdasarkan fungsi organisasi dan fasilitas perusahaan.

## 2.2 Gaya Arsitektur

Modern Tropical Architecture.

Karakteristik:

- Bangunan modern dengan sentuhan arsitektur tropis.
- Kombinasi beton, kaca, baja, dan material kayu dekoratif.
- Atap datar, atap tropis, atau kombinasi keduanya sesuai fungsi bangunan.
- Kanopi dan area teduh.
- Jendela besar.
- Teras dan balkon.
- Lanskap tropis dengan pepohonan, tanaman hias, dan ruang hijau.
- Jalan dan pedestrian yang terintegrasi.
- Pencahayaan alami dan buatan.

Hindari desain yang terlalu futuristik sehingga kehilangan karakter kompleks perkantoran nyata.

## 2.3 Transparansi Gedung

Menggunakan Cutaway Miniature Architecture.

Karakteristik:

- Sebagian dinding bangunan dihilangkan atau dibuat transparan.
- Interior dapat dilihat dari sudut pandang isometrik.
- Ruangan memiliki batas geometris yang jelas.
- Furnitur dan workstation dapat terlihat.
- Agent dapat terlihat bekerja di dalam ruangan.
- Struktur atap dapat disederhanakan atau sebagian dihilangkan untuk mempertahankan keterlihatan interior.

Cutaway merupakan teknik visualisasi, bukan representasi akses data. Data internal tetap dilindungi oleh backend.

# 3. MASTERPLAN KAWASAN

## 3.1 Pembagian District

Kompleks MCE memiliki empat kawasan utama.

### DISTRICT A — EXECUTIVE & CORPORATE HEADQUARTERS

Pusat kepemimpinan dan koordinasi perusahaan.

**Gedung A1 — Executive Headquarters**
- Owner Office.
- Director AI Office.
- Executive Reception.
- Executive Meeting Room.
- Strategic Planning Room.
- Private Office Area.

**Gedung A2 — Corporate Operations Building**
- Administration Office.
- Finance & Budget.
- Human Resources.
- CRM & Customer Relations.
- Business Growth Center.
- Marketing & Communication.
- General Operations.

Corporate Operations menjadi pusat layanan bersama bagi seluruh divisi.

### DISTRICT B — BUSINESS DIVISIONS

Kawasan operasional enam unit bisnis MCE.

**Gedung B1 — Technology & Digital Solutions**
- Technology Director / Division Head.
- Software Development Room.
- Web & Application Development.
- System Architecture Room.
- Research & Innovation Lab.
- Testing & Quality Assurance.

**Gedung B2 — MCE Academy**
- Academy Office.
- Training Room.
- Digital Learning Studio.
- Course Development Room.
- Educational Media Studio.

**Gedung B3 — Technical Services**
- Service Reception.
- Computer Maintenance Workshop.
- Hardware Diagnostics.
- Repair Workshop.
- Quality Control.
- Equipment Storage.

**Gedung B4 — Hardware & Manufacturing**
- Hardware Design Lab.
- Computer Assembly Area.
- Electronics Workshop.
- Product Testing Lab.
- Inventory & Components Storage.

**Gedung B5 — Commerce & Distribution**
- Commerce Office.
- Product Catalog Room.
- Sales & Distribution.
- Procurement Coordination.
- Logistics & Storage.

### DISTRICT C — SHARED FACILITIES

Fasilitas yang dapat digunakan bersama oleh berbagai divisi.

**Gedung C1 — Meeting & Conference Center**
- Main Conference Room.
- Collaboration Room.
- Presentation Room.
- Project Meeting Room.

**Gedung C2 — Innovation & Product Showroom**
- Technology Exhibition.
- Product Demonstration.
- Digital Solution Showcase.
- Portfolio Display.

**Gedung C3 — Cafeteria & Lounge**
- Cafeteria.
- Break Area.
- Informal Meeting Area.
- Outdoor Seating.

**Area C4 — Central Park**
- Taman utama.
- Plaza.
- Jalur pedestrian.
- Area duduk.
- Air mancur atau elemen air dekoratif.
- Vegetasi tropis.

### DISTRICT D — CAMPUS INFRASTRUCTURE

Infrastruktur dan fasilitas pendukung.

- Main Gate & Security Post.
- Main Road.
- Secondary Roads.
- Pedestrian Network.
- Visitor Parking.
- Staff Parking.
- Operational Vehicle Parking.
- Loading & Service Area.
- Utility Building.
- Landscape & Green Buffer.
- Drainage dan infrastruktur kawasan.

## 3.2 Ketentuan Hubungan Antar-District

- Executive District menjadi pusat orientasi kawasan.
- Corporate Operations ditempatkan berdekatan dengan Executive Headquarters.
- Business Divisions memiliki akses ke jalan utama.
- Meeting & Conference Center berada pada lokasi yang mudah dicapai seluruh divisi.
- Showroom ditempatkan pada area yang mudah dijangkau pengunjung.
- Technical Services dan Hardware memiliki akses yang memadai ke area servis dan logistik.
- Academy memiliki hubungan dengan fasilitas pelatihan dan ruang terbuka.
- Central Park menjadi ruang hijau yang menghubungkan kawasan.
- Main Gate menjadi titik masuk utama kompleks.

# 4. MASTERPLAN SPASIAL 3D

## 4.1 Sistem Koordinat

Gunakan sistem koordinat lokal Three.js:

- X: arah horizontal kawasan.
- Z: kedalaman kawasan.
- Y: elevasi.

Pusat kawasan berada di koordinat lokal (0, 0, 0).

Gunakan unit virtual yang konsisten. Satu unit Three.js mewakili satu unit ukuran virtual yang digunakan seluruh objek.

Ukuran kawasan awal bersifat relatif dan dapat disesuaikan berdasarkan kebutuhan desain.

## 4.2 Tata Letak Konseptual

Kawasan menggunakan susunan district yang saling terhubung melalui jalan utama.

Skema orientasi awal:

- Bagian depan: Main Gate, Visitor Parking, dan Showroom.
- Bagian tengah: Central Park, Meeting Center, dan jalan utama.
- Sisi kawasan: enam gedung divisi.
- Bagian belakang: Executive Headquarters dan Corporate Operations.
- Area servis: Technical Services, Hardware, dan Logistics.

Tata letak final harus disusun dalam masterplan berbasis koordinat agar posisi gedung, jalan, taman, dan fasilitas tidak berubah secara acak ketika scene dimuat ulang.

## 4.3 Infrastruktur Jalan

Jenis jalan:

1. Main Road — jalan utama yang menghubungkan gerbang dengan seluruh district.
2. Secondary Road — jalan penghubung antarbangunan.
3. Service Road — jalur operasional dan logistik.
4. Pedestrian Path — jalur pejalan kaki.
5. Parking Lane — jalur kendaraan dan area parkir.

Semua jalan harus mempunyai geometri yang saling terhubung.

Gunakan sistem waypoint dan graph untuk menentukan jalur agent serta kendaraan.

## 4.4 Area Hijau

Elemen lanskap:

- Pohon tropis.
- Palem.
- Tanaman semak.
- Rumput.
- Taman bunga.
- Bangku taman.
- Lampu taman.
- Jalur pedestrian.
- Elemen air dekoratif.

Vegetasi menggunakan variasi model dan instancing untuk mengurangi jumlah draw calls.

# 5. SISTEM GEDUNG DAN INTERIOR

## 5.1 Struktur Gedung

Setiap gedung memiliki:

- Building ID.
- Nama gedung.
- District ID.
- Fungsi.
- Posisi dan rotasi.
- Ukuran.
- Material.
- Daftar ruangan.
- Daftar pintu dan akses.
- Jalur masuk dan keluar.
- Public visibility policy.
- Agent occupancy.

## 5.2 Sistem Ruangan

Setiap ruangan memiliki:

- Room ID.
- Building ID.
- Nama ruangan.
- Fungsi.
- Posisi.
- Ukuran.
- Batas navigasi.
- Daftar workstation.
- Daftar furnitur.
- Agent yang sedang berada di dalamnya.
- Status aktivitas publik.
- Pengaturan visibilitas.

## 5.3 Furnitur dan Objek

Gunakan model 3D sederhana dan konsisten.

Objek dasar:

- Meja kerja.
- Kursi.
- Komputer.
- Monitor.
- Lemari.
- Rak.
- Pintu.
- Jendela.
- Lampu.
- Sofa.
- Meja rapat.
- Peralatan workshop.
- Peralatan laboratorium.

Gunakan reusable geometry dan material agar penggunaan memori tetap terkendali.

# 6. SISTEM AI AGENT DALAM KOMPLEKS

## 6.1 Identitas Agent

Setiap agent mempunyai representasi karakter 3D yang terhubung dengan identitas agent pada sistem MCE.

Karakteristik:

- Agent ID.
- Nama karakter.
- Divisi.
- Jabatan.
- Gedung utama.
- Ruangan utama.
- Model karakter.
- Animation controller.
- Runtime status.
- Current location.
- Current destination.

## 6.2 Aktivitas Karakter

Animasi dasar:

- Idle.
- Walking.
- Working.
- Typing.
- Reading.
- Reviewing.
- Collaborating.
- Meeting.
- Waiting.
- Paused.

Animasi harus memiliki transisi yang halus dan tidak membuat karakter berpindah secara tiba-tiba.

## 6.3 Pergerakan Agent

Agent dapat:

- Berpindah antar-workstation.
- Berjalan menuju ruang rapat.
- Berjalan menuju ruangan lain dalam gedung.
- Berpindah antar-gedung.
- Mengikuti jalur pedestrian.
- Menghindari tabrakan dengan objek dan karakter.
- Berhenti pada lokasi tujuan.

Gunakan sistem pathfinding berbasis navigation graph atau waypoint graph.

Hindari penggunaan sistem navigasi yang terlalu kompleks sebelum jumlah karakter dan kebutuhan simulasi benar-benar diketahui.

## 6.4 Hubungan Status dan Animasi

| Runtime status | Visualisasi |
|---|---|
| Idle | Berdiri atau duduk santai |
| Assigned | Persiapan menerima pekerjaan |
| Working | Mengetik atau bekerja |
| Delegating | Berjalan atau berkoordinasi |
| Reviewing | Memeriksa secara generik |
| Waiting Approval | Menunggu |
| Completed | Mengakhiri aktivitas dan kembali idle |
| Failed | Berhenti dan masuk status pemeriksaan |
| Paused | Berhenti sementara |
| Cancelled | Mengakhiri aktivitas |

Tidak semua status perlu memiliki animasi yang unik. Yang penting adalah karakter tidak menampilkan aktivitas yang bertentangan dengan status aktualnya.

# 7. PUBLIC OBSERVATION MODE

## 7.1 Tujuan

Publik dapat menyaksikan aktivitas kompleks perkantoran MCE secara visual dan real-time.

Publik bukan operator kantor dan tidak memiliki kemampuan untuk memerintah agent internal.

## 7.2 Kemampuan Publik

- Melihat keseluruhan kawasan.
- Melihat gedung dan district.
- Melihat agent di setiap divisi.
- Mengamati agent bergerak.
- Mengamati agent bekerja.
- Memperbesar dan memperkecil kamera.
- Memilih gedung.
- Memilih agent.
- Melihat nama publik, divisi, lokasi, dan status aktivitas umum.
- Melihat indikator aktivitas kawasan yang telah diizinkan.

## 7.3 Batasan Informasi

Tidak boleh ditampilkan:

- Judul tugas internal.
- Isi instruksi agent.
- Isi percakapan internal.
- Dokumen pekerjaan.
- Kode program.
- Data pelanggan.
- Nama proyek privat.
- Informasi keuangan.
- Model AI dan kredensial.
- Tools dan konfigurasi internal.
- Hasil kerja yang belum diizinkan untuk publikasi.

## 7.4 Public Activity Projection

Backend harus menghasilkan data proyeksi khusus untuk publik.

Data publik tidak boleh mengambil objek runtime Hermes secara langsung.

Informasi minimal:

- Public Agent ID.
- Nama publik.
- Divisi.
- Gedung.
- Ruangan.
- Status aktivitas umum.
- Jenis animasi yang diizinkan.
- Lokasi karakter.
- Timestamp pembaruan.
- Public visibility.

Data ini dikirim melalui Public API dan dapat disinkronkan menggunakan SSE.

## 7.5 Status Koneksi

Jika koneksi realtime terputus:

- Tampilkan indikator koneksi terputus.
- Pertahankan posisi terakhir yang valid.
- Jangan mengklaim aktivitas sebagai realtime.
- Sambungkan kembali ketika layanan tersedia.
- Jangan membuat aktivitas palsu untuk menutupi ketidaktersediaan data.

# 8. PRIVATE OPERATIONS MODE

Owner dan pengguna internal yang berwenang dapat melihat:

- Semua district dan gedung.
- Semua ruangan.
- Seluruh agent.
- Detail aktivitas aktual.
- Detail tugas dan pekerjaan.
- Perpindahan agent.
- Delegasi dan koordinasi.
- Status runtime.
- Riwayat pekerjaan.
- Informasi operasional.
- Mission Board.

Tampilan internal menggunakan kompleks dan model 3D yang sama, tetapi memperoleh lapisan informasi tambahan berdasarkan permission.

# 9. KAMERA DAN INTERAKSI

## 9.1 Default Camera

Gunakan Orthographic Camera dengan sudut isometrik.

Karakteristik:

- Melihat keseluruhan kompleks.
- Menampilkan seluruh district.
- Posisi kamera stabil.
- Tidak terlalu dekat dengan objek.
- Menjaga komposisi kawasan.

## 9.2 Navigasi Kamera

Fitur:

- Zoom In.
- Zoom Out.
- Pan.
- Orbit terbatas.
- Reset View.
- Focus Building.
- Focus District.
- Focus Agent.

## 9.3 Mode Kamera

1. Campus Overview.
2. District Overview.
3. Building Overview.
4. Agent Observation.

Public mode tidak boleh memiliki akses ke detail kamera atau objek yang membuka informasi internal.

# 10. SISTEM LINGKUNGAN DINAMIS

Lingkungan dapat mempunyai animasi yang tidak bergantung pada status agent:

- Pergantian siang dan malam.
- Matahari dan bayangan.
- Lampu gedung.
- Lampu jalan.
- Pergerakan dedaunan.
- Air mancur.
- Efek hujan.
- Efek lingkungan ringan.
- Kendaraan lingkungan jika diperlukan.

Cuaca dan waktu harus dapat dikendalikan oleh sistem konfigurasi.

Efek visual harus dapat dinonaktifkan untuk perangkat dengan kemampuan grafis terbatas.

# 11. SPESIFIKASI TEKNOLOGI 3D

## 11.1 Frontend

- React.
- TypeScript.
- Three.js.
- Vite.
- HTML/CSS overlay.
- Direct Three.js integration melalui React lifecycle.

Tidak menggunakan React Three Fiber sebagai migrasi default.

## 11.2 Rendering

Gunakan:

- WebGLRenderer.
- OrthographicCamera.
- Scene management.
- InstancedMesh.
- Reusable geometries.
- Reusable materials.
- Frustum culling.
- Level of Detail.
- Shadow quality control.
- Texture compression.
- Lazy loading.

## 11.3 Sistem Karakter

Gunakan karakter 3D low-poly atau stylized dengan rig yang konsisten.

Pilihan format:

- GLB/GLTF untuk model karakter dan animasi.
- Procedural geometry untuk objek sederhana.
- Shared animation clips.
- Animation state machine.

Hindari membuat setiap agent sebagai model 3D yang sepenuhnya berbeda apabila tidak dibutuhkan.

## 11.4 Collision dan Pathfinding

Sediakan:

- Building collision boundaries.
- Room navigation boundaries.
- Furniture collision.
- Road navigation.
- Pedestrian navigation.
- Agent avoidance.
- Destination waypoint.
- Route validation.

## 11.5 Scene Management

Scene dipisahkan secara logis:

- CampusScene.
- BuildingScene.
- RoomScene.
- AgentScene.
- EnvironmentScene.
- CameraScene.
- PublicOverlay.
- PrivateOverlay.

Komponen dapat berbagi aset, tetapi aturan visibilitas dan data harus berbeda.

# 12. INTEGRASI BACKEND DAN AI RUNTIME

Arsitektur tetap mengikuti:

React + Three.js → CI4 REST API → MySQL → Hermes → 9Router → Model Providers.

CI4 menjadi pusat data dan kontrol sistem.

Hermes bertanggung jawab atas runtime agent.

9Router menjadi gateway model AI.

Frontend tidak boleh mengakses Hermes atau 9Router secara langsung.

## 12.1 Data yang Dikelola CI4

- Buildings.
- Districts.
- Rooms.
- Furniture.
- Agents.
- Agent locations.
- Runtime status projection.
- Workstations.
- Navigation graph.
- Visibility policies.
- Campus settings.
- Public activity projection.

## 12.2 Event yang Diterima dari Runtime

- Agent assigned.
- Agent started.
- Agent status changed.
- Agent delegated.
- Agent moved.
- Agent completed.
- Agent paused.
- Agent failed.
- Agent resumed.

Event yang tidak berasal dari runtime aktual tidak boleh diperlakukan sebagai bukti bahwa agent sedang bekerja.

## 12.3 Realtime

Gunakan SSE untuk memperbarui:

- Status agent.
- Posisi agent.
- Perpindahan agent.
- Aktivitas ruangan.
- Status divisi.
- Status koneksi.

Posisi visual boleh diinterpolasi di frontend agar gerakan halus, tetapi tujuan dan status dasarnya harus berasal dari data aktual.

# 13. STRUKTUR MODUL PROJECT 3D

Pisahkan sistem kompleks 3D menjadi modul yang mudah dikembangkan.

```text
src/
├── components/
│   ├── campus/
│   ├── buildings/
│   ├── rooms/
│   ├── roads/
│   ├── landscape/
│   ├── furniture/
│   ├── agents/
│   ├── vehicles/
│   └── environment/
│
├── engine/
│   ├── renderer/
│   ├── camera/
│   ├── scene/
│   ├── lighting/
│   ├── materials/
│   ├── collision/
│   ├── navigation/
│   ├── animation/
│   └── lod/
│
├── campus/
│   ├── masterplan/
│   ├── districts/
│   ├── buildings/
│   ├── room-layouts/
│   ├── road-network/
│   └── landscape/
│
├── agents/
│   ├── models/
│   ├── controllers/
│   ├── state-machine/
│   ├── pathfinding/
│   └── runtime-sync/
│
├── modes/
│   ├── public/
│   └── private/
│
├── services/
│   ├── api/
│   ├── realtime/
│   └── permissions/
│
└── ui/
    ├── public-overlay/
    ├── private-overlay/
    ├── mission-board/
    └── controls/
```

Struktur ini merupakan target modularisasi. Perubahan harus disesuaikan dengan struktur repository aktual, bukan langsung mengganti seluruh proyek.

# 14. DATABASE DAN KONFIGURASI

Entitas utama yang diperlukan:

- campus_districts
- campus_buildings
- campus_rooms
- campus_assets
- campus_workstations
- campus_navigation_nodes
- campus_navigation_edges
- campus_settings
- campus_visibility_policies
- agent_locations
- agent_runtime_events
- agent_public_projections

Setiap tabel harus mempunyai relasi, indeks, dan kebijakan akses yang sesuai.

Data posisi karakter tidak harus ditulis ke database setiap frame. Simpan perubahan lokasi atau checkpoint yang relevan, sedangkan interpolasi animasi ditangani oleh frontend.

# 15. PERFORMANCE DAN OPTIMASI

Target implementasi:

- Kompleks dapat dibuka pada perangkat desktop dan mobile.
- Kamera tetap responsif.
- Tidak semua detail harus dirender pada saat overview.
- Gedung dan interior dapat menggunakan LOD.
- Model karakter menggunakan animasi bersama.
- Vegetasi dan furnitur berulang menggunakan instancing.
- Aset besar dimuat secara bertahap.
- Tekstur dan geometri dioptimalkan.
- Shadow dan efek visual dapat disesuaikan.
- Jumlah agent aktif dan pembaruan realtime harus dibatasi sesuai kebutuhan performa.

Sediakan minimal tiga profil grafis:

1. Low — perangkat terbatas.
2. Balanced — pengaturan standar.
3. High — desktop dengan kemampuan grafis lebih tinggi.

# 16. KEAMANAN DAN PRIVASI

Prinsip wajib:

1. Public API dan Management API terpisah.
2. Public mode hanya menerima public projections.
3. Tidak ada akses publik langsung ke Hermes.
4. Tidak ada informasi pekerjaan internal di payload publik.
5. Semua izin akses diperiksa di backend.
6. Data sensitif tidak dimasukkan ke metadata objek 3D publik.
7. Interaksi pengunjung tidak dapat menjalankan instruksi internal.
8. Owner mempunyai kontrol atas visibilitas gedung, agent, dan aktivitas publik.
9. Aktivitas internal tercatat dalam audit log.
10. Kegagalan realtime tidak boleh menghasilkan status aktivitas palsu.

# 17. TAHAP PEMBANGUNAN

**Tahap 1 — Campus Foundation**
- Membuat masterplan.
- Membuat seluruh district.
- Membuat jalan dan infrastruktur.
- Membuat gedung dasar.
- Membuat taman dan area parkir.
- Menetapkan sistem koordinat.

**Tahap 2 — Building Architecture**
- Membuat desain arsitektur tropis.
- Membuat sistem cutaway.
- Membuat interior gedung.
- Membuat ruangan dan furnitur.
- Membuat pintu dan jalur penghubung.

**Tahap 3 — Agent Simulation**
- Membuat model karakter.
- Membuat animasi.
- Membuat workstation.
- Membuat sistem navigasi.
- Membuat collision.
- Membuat state machine.

**Tahap 4 — Runtime Integration**
- Mengintegrasikan status agent dari CI4 dan Hermes.
- Menghubungkan event realtime.
- Membuat proyeksi aktivitas publik.
- Menghubungkan lokasi agent.
- Membuat sinkronisasi status dan animasi.

**Tahap 5 — Public Experience**
- Membuat Public Observation Mode.
- Membuat kamera pengamatan.
- Membuat interaksi pemilihan gedung dan agent.
- Membuat informasi publik.
- Menerapkan penyaringan data.

**Tahap 6 — Private Operations**
- Menghubungkan dashboard.
- Menampilkan detail operasional.
- Menghubungkan Mission Board.
- Membuat kontrol Owner.
- Membuat pengaturan kawasan.

**Tahap 7 — Optimization & Validation**
- Pengujian desktop dan mobile.
- Pengujian jumlah agent.
- Pengujian navigasi.
- Pengujian keamanan public payload.
- Pengujian realtime.
- Pengujian performa.
- Pengujian pemulihan koneksi.

# 18. KRITERIA PENERIMAAN

Kompleks dinyatakan memenuhi rancangan apabila:

- Seluruh district utama dapat ditampilkan.
- Setiap divisi memiliki gedung tersendiri.
- Gedung memiliki interior cutaway.
- Jalan dan pedestrian terhubung.
- Taman, plaza, parkir, dan gerbang tersedia.
- Agent dapat bekerja dan bergerak.
- Pergerakan tidak menembus objek.
- Status visual mengikuti runtime.
- Publik dapat mengamati agent tanpa mengetahui detail pekerjaan.
- Dashboard dapat melihat informasi operasional sesuai kewenangan.
- Public API tidak membocorkan data internal.
- Sistem tetap stabil pada perangkat dengan kemampuan grafis berbeda.

# 19. KEPUTUSAN FINAL

MCE Virtual Headquarters ditetapkan sebagai kompleks perkantoran virtual berbasis 3D isometrik dengan konsep:

**District Layout + Modern Tropical Architecture + Cutaway Miniature.**

Kawasan terdiri atas:

1. Executive & Corporate Headquarters.
2. Business Divisions.
3. Shared Facilities.
4. Campus Infrastructure.

Kompleks menjadi lingkungan visual bersama untuk Public Observation dan Private Operations.

Publik dapat menyaksikan kehidupan kantor MCE, sementara Owner memiliki kendali penuh atas detail operasional dan informasi internal.

Seluruh visualisasi agent terhubung dengan aktivitas nyata sistem AI MCE.

**Prinsip akhir: MCE bukan sekadar dashboard dengan animasi 3D. MCE adalah kompleks perkantoran digital yang merepresentasikan organisasi dan aktivitas AI perusahaan secara hidup, terukur, dan aman.**