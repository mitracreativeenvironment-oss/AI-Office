# DOKUMEN MASTER
# PEMBANGUNAN PERUSAHAAN AI MCE
## MCE — AI Company Operating System & 3D Virtual Office

**Versi Dokumen:** 0.1.0 
**Nama Sistem:** `mce`  
**Nama Perusahaan:** Mitra Creative Environment (MCE)  
**Konsep Sistem:** AI Company Operating System  
**Model Operasional:** Hybrid AI Automation with Human-in-the-Loop  
**Status:** Dokumen Master Acuan Pembangunan

---

# 1. VISI DAN KONSEP PROYEK

## 1.1 Visi

Membangun MCE sebagai perusahaan yang menjalankan sebagian besar aktivitas operasionalnya melalui sistem berbasis Artificial Intelligence (AI), dengan struktur organisasi digital, agen AI yang memiliki fungsi dan tanggung jawab masing-masing, serta lingkungan kantor virtual 3D yang merepresentasikan aktivitas perusahaan secara dinamis.

Sistem ini tidak sekadar menjadi chatbot, dashboard administrasi, atau simulasi kantor 3D. Sistem harus menjadi pusat kendali operasional perusahaan yang mampu menerima instruksi, memahami pekerjaan, membagi tugas, melaksanakan proses, memantau hasil, dan menyampaikan laporan.

## 1.2 Konsep Utama

MCE dikembangkan dengan tiga lapisan utama:

1. **Business Operating System:** Mengelola organisasi, divisi, pekerjaan, pelanggan, proyek, transaksi, aset, dan aktivitas perusahaan.
2. **AI Agent Organization:** Menjalankan pekerjaan melalui struktur Director AI, Division Head AI, dan Specialist AI Agents.
3. **3D Virtual Office:** Memvisualisasikan organisasi, ruang kerja, karakter, aktivitas, dan perkembangan pekerjaan secara real-time.

Ketiga lapisan harus terintegrasi. Aktivitas yang terlihat pada kantor 3D harus berasal dari data dan peristiwa sistem yang sebenarnya, bukan sekadar animasi yang berjalan tanpa hubungan dengan pekerjaan.

## 1.3 Prinsip Pembangunan

- AI menjadi pelaksana utama untuk pekerjaan yang telah didefinisikan dan diizinkan.
- Manusia tetap memiliki kendali terhadap keputusan penting.
- Setiap agen mempunyai identitas, tugas, batas kewenangan, dan tanggung jawab yang jelas.
- Seluruh pekerjaan memiliki status, riwayat, dan hasil yang dapat ditelusuri.
- Struktur perusahaan dan agen dapat dikelola melalui dashboard.
- Lingkungan 3D mengikuti struktur dan aktivitas organisasi yang tersimpan di backend.
- Sistem harus dapat dikembangkan tanpa membangun ulang keseluruhan arsitektur.

---

# 2. IDENTITAS DAN BIDANG USAHA MCE

## 2.1 Identitas Perusahaan

**Mitra Creative Environment (MCE)** merupakan perusahaan yang menjalankan kegiatan di bidang teknologi informasi, pengembangan perangkat lunak, konsultansi komputer, pendidikan teknologi, layanan teknis, manufaktur perangkat komputer, serta perdagangan komputer dan perlengkapannya.

MCE menjadi organisasi bisnis utama yang dioperasikan melalui sistem `mce`.

## 2.2 Bidang Usaha

Daftar bidang usaha yang menjadi dasar rancangan operasional:

| No. | KBLI | Bidang kegiatan |
|---|---|---|
| 1 | 62900 | Aktivitas Jasa Teknologi Informasi dan Komputer Lainnya |
| 2 | 85592 | Pendidikan Komputer (Teknologi Informasi dan Komunikasi) Swasta |
| 3 | 95101 | Reparasi dan Pemeliharaan Komputer dan Peralatan Sejenisnya |
| 4 | 95400 | Jasa Intermediasi Reparasi dan Perawatan Komputer, Barang Pribadi dan Rumah Tangga, Mobil, serta Sepeda Motor |
| 5 | 62199 | Aktivitas Pemrograman Komputer Lainnya YTDL |
| 6 | 26210 | Industri Komputer dan Perakitan Komputer |
| 7 | 46511 | Perdagangan Besar Komputer dan Perlengkapan Komputer |
| 8 | 47401 | Perdagangan Eceran Komputer dan Perlengkapannya |
| 9 | 62209 | Aktivitas Konsultansi Komputer dan Manajemen Fasilitas Komputer Lainnya |

Daftar tersebut menjadi referensi utama untuk pemetaan fungsi perusahaan. Implementasi operasional dan pengelompokan divisi tidak boleh mengubah arti atau status klasifikasi KBLI.

---

# 3. STRUKTUR ORGANISASI AI MCE

## 3.1 Struktur Hierarki

Organisasi AI MCE menggunakan struktur bertingkat:

**Director AI → Division Head AI → Specialist AI Agents**

Manusia bertindak sebagai pemilik perusahaan, pengarah, pengawas, dan pemberi persetujuan untuk tindakan yang memerlukan kewenangan manusia.

### Tingkat 1 — Human Owner / Human Management

Fungsi:
- Memberikan instruksi dan tujuan perusahaan.
- Menentukan kebijakan dan target.
- Menyetujui keputusan penting.
- Mengawasi kinerja perusahaan.
- Menangani pengecualian yang tidak dapat diselesaikan AI.

### Tingkat 2 — Director AI

Director AI menjadi pusat koordinasi seluruh organisasi AI.

Tanggung jawab:
- Menerima instruksi dari manusia.
- Memahami tujuan dan memecahnya menjadi pekerjaan.
- Menentukan divisi yang bertanggung jawab.
- Menugaskan pekerjaan kepada Division Head.
- Memantau perkembangan seluruh pekerjaan.
- Meminta evaluasi dan perbaikan hasil.
- Menyampaikan laporan kepada manusia.
- Meminta persetujuan untuk tindakan yang berada di luar batas kewenangan otomatis.

Director AI tidak boleh melewati kebijakan otorisasi hanya karena sebuah tugas dianggap mendesak.

### Tingkat 3 — Division Head AI

Setiap divisi memiliki kepala divisi AI yang bertanggung jawab terhadap:
- Penerimaan tugas dari Director AI.
- Perencanaan dan pembagian pekerjaan.
- Delegasi kepada Specialist AI Agents.
- Pengawasan proses.
- Pemeriksaan kualitas hasil.
- Pelaporan kepada Director AI.

### Tingkat 4 — Specialist AI Agents

Specialist AI Agents menjalankan pekerjaan teknis dan operasional sesuai kompetensi masing-masing.

Setiap agen mempunyai:
- ID dan nama unik.
- Divisi.
- Jabatan.
- Deskripsi tugas.
- Persona komunikasi.
- System instruction.
- Model AI yang digunakan.
- Memory policy.
- Daftar skills.
- Daftar tools.
- Hak akses.
- Batas kewenangan.
- Status runtime.
- Riwayat pekerjaan.
- Evaluasi kinerja.

Agen tidak boleh menggunakan tool atau mengakses data di luar izin yang diberikan.

---

# 4. STRUKTUR DIVISI PERUSAHAAN MCE

Sembilan bidang usaha dikelompokkan ke dalam enam unit operasional yang dirancang untuk menghindari tumpang tindih pekerjaan.

Pengelompokan ini merupakan rancangan internal organisasi dan dapat dikembangkan melalui dashboard.

## 4.1 MCE Technology & Digital Solutions

**Fokus:** Pengembangan teknologi, perangkat lunak, konsultansi, dan solusi digital.

Referensi bidang usaha:
- 62900
- 62199
- 62209

Specialist AI Agents:
- Software Architect AI
- Software Developer AI
- Frontend Developer AI
- Backend Developer AI
- UI/UX Designer AI
- Database Specialist AI
- QA Engineer AI
- DevOps Engineer AI
- IT Consultant AI
- Technical Documentation AI

Tanggung jawab:
- Analisis kebutuhan pelanggan.
- Perencanaan dan pengembangan aplikasi.
- Pengembangan website dan sistem informasi.
- Konsultansi infrastruktur dan teknologi.
- Pengujian dan pemeliharaan perangkat lunak.
- Dokumentasi teknis.
- Pengelolaan proyek teknologi.

## 4.2 MCE Academy

**Fokus:** Pendidikan dan pelatihan komputer serta teknologi informasi.

Referensi bidang usaha:
- 85592

Specialist AI Agents:
- Curriculum Designer AI
- Learning Material Developer AI
- Instructor Assistant AI
- Student Administrator AI
- Learning Evaluator AI
- Training Coordinator AI

Tanggung jawab:
- Perancangan kurikulum.
- Pengembangan materi pembelajaran.
- Pengelolaan peserta pelatihan.
- Penjadwalan kegiatan belajar.
- Pendampingan pembelajaran.
- Evaluasi hasil belajar.
- Pembuatan laporan pelatihan.

Pelaksanaan pembelajaran fisik, pengawasan peserta, dan sertifikasi yang memerlukan kewenangan manusia tetap melibatkan tenaga manusia.

## 4.3 MCE Technical Services

**Fokus:** Layanan reparasi, pemeliharaan, dan koordinasi layanan teknis.

Referensi bidang usaha:
- 95101
- 95400

Specialist AI Agents:
- Service Coordinator AI
- Customer Service AI
- Diagnostic Assistant AI
- Repair Administrator AI
- Maintenance Scheduler AI
- Technical Documentation AI
- Quality Control Assistant AI

Tanggung jawab:
- Penerimaan permintaan layanan.
- Pencatatan keluhan pelanggan.
- Pengumpulan informasi perangkat.
- Bantuan diagnosis awal.
- Penjadwalan pekerjaan.
- Pemantauan status perbaikan.
- Pemeriksaan dokumentasi hasil.
- Pelaporan layanan.

Pemeriksaan fisik, pembongkaran, perbaikan, dan pengujian perangkat tetap dilaksanakan oleh teknisi manusia atau tenaga teknis yang berwenang.

## 4.4 MCE Hardware & Manufacturing

**Fokus:** Pengembangan, perakitan, dan pengendalian kualitas perangkat komputer.

Referensi bidang usaha:
- 26210

Specialist AI Agents:
- Hardware Designer AI
- Hardware Researcher AI
- Assembly Planner AI
- Component Specialist AI
- Inventory Specialist AI
- Quality Control Assistant AI
- Production Administrator AI

Tanggung jawab:
- Riset komponen.
- Perencanaan spesifikasi perangkat.
- Perencanaan perakitan.
- Pencatatan komponen.
- Pengendalian persediaan.
- Dokumentasi produksi.
- Pemeriksaan standar kualitas.

Perakitan dan pemeriksaan fisik tetap membutuhkan pelaksana manusia.

## 4.5 MCE Commerce & Distribution

**Fokus:** Perdagangan besar, perdagangan eceran, penjualan, dan distribusi komputer serta perlengkapannya.

Referensi bidang usaha:
- 46511
- 47401

Specialist AI Agents:
- Sales Agent AI
- Product Researcher AI
- Procurement Agent AI
- Product Catalog Administrator AI
- Order Administrator AI
- Customer Support AI
- Inventory Coordinator AI

Tanggung jawab:
- Riset produk.
- Pengelolaan katalog.
- Pengelolaan permintaan pelanggan.
- Penyusunan penawaran.
- Pengelolaan pesanan.
- Koordinasi pengadaan.
- Pemantauan stok.
- Pelaporan penjualan.

Pembelian, pembayaran, dan komitmen transaksi mengikuti batas persetujuan yang ditentukan perusahaan.

## 4.6 MCE Corporate Operations

**Fokus:** Fungsi pendukung dan pengelolaan internal perusahaan.

Unit ini merupakan fungsi organisasi pendukung yang dirancang untuk mengintegrasikan kegiatan lintas divisi, bukan tambahan klasifikasi KBLI.

Specialist AI Agents:
- Finance Assistant AI
- Accounting Assistant AI
- HR Administrator AI
- Business Analyst AI
- Marketing Agent AI
- Corporate Communication AI
- Procurement Administrator AI
- Legal Documentation Assistant AI
- Operations Administrator AI

Tanggung jawab:
- Administrasi perusahaan.
- Pengelolaan sumber daya manusia.
- Bantuan pencatatan keuangan.
- Analisis kinerja bisnis.
- Komunikasi dan pemasaran.
- Pengelolaan dokumen.
- Koordinasi pengadaan.
- Penyusunan laporan manajemen.

Keputusan keuangan, hukum, ketenagakerjaan, dan komitmen perusahaan yang memiliki konsekuensi penting tetap berada dalam kewenangan manusia.

---

# 5. MODEL OTOMATISASI HYBRID

## 5.1 Keputusan Model Operasional

MCE menggunakan **Hybrid AI Automation with Human-in-the-Loop**.

Artinya, AI dapat menjalankan pekerjaan operasional secara mandiri selama pekerjaan tersebut:
- Telah didefinisikan dalam workflow.
- Memiliki sumber data yang sah.
- Menggunakan tools yang diizinkan.
- Berada dalam batas kewenangan agen.
- Memenuhi kebijakan keamanan dan persetujuan.

Manusia tetap mengawasi dan menyetujui tindakan yang memerlukan keputusan atau kewenangan khusus.

## 5.2 Klasifikasi Kewenangan

### Level A — Autonomous Execution

AI boleh menjalankan pekerjaan tanpa persetujuan satu per satu, selama sudah diotorisasi oleh kebijakan.

Contoh:
- Mengelompokkan permintaan pelanggan.
- Membuat draft dokumen.
- Melakukan riset produk.
- Menganalisis data.
- Menyusun laporan.
- Menjadwalkan pekerjaan internal.
- Membuat tiket atau task.
- Mengirim notifikasi internal.
- Menjalankan pengujian perangkat lunak dalam lingkungan yang diizinkan.

### Level B — Supervised Execution

AI dapat menyiapkan dan menjalankan proses, tetapi perlu pengawasan atau pemeriksaan manusia sesuai kebijakan.

Contoh:
- Publikasi konten eksternal.
- Pengiriman penawaran kepada pelanggan.
- Perubahan data penting.
- Pelaksanaan kampanye pemasaran.
- Perubahan konfigurasi sistem produksi.
- Penyelesaian pekerjaan yang memerlukan pemeriksaan kualitas manusia.

### Level C — Human Approval Required

AI tidak boleh menyelesaikan tindakan tanpa persetujuan manusia yang berwenang.

Contoh:
- Pembayaran dan transfer dana.
- Pembelian di atas batas nominal.
- Penandatanganan kontrak.
- Persetujuan kewajiban hukum.
- Komitmen bisnis yang mengikat perusahaan.
- Penghapusan data penting.
- Pemberian atau perubahan hak akses administrator.
- Keputusan strategis perusahaan.
- Pengungkapan data sensitif.

## 5.3 Approval Workflow

Setiap tindakan yang membutuhkan persetujuan harus memiliki:
1. Identitas pemohon atau agen.
2. Deskripsi tindakan.
3. Alasan dan tujuan.
4. Data pendukung.
5. Dampak dan risiko.
6. Nilai transaksi jika ada.
7. Pejabat atau pengguna yang berwenang menyetujui.
8. Status persetujuan.
9. Waktu dan identitas pemberi keputusan.
10. Catatan audit.

Status:
- Pending Approval
- Approved
- Rejected
- Expired
- Cancelled

Persetujuan harus tercatat dan dapat ditelusuri.

---

# 6. ALUR KERJA ORGANISASI AI

## 6.1 Alur Instruksi

Alur standar:

1. Manusia memberikan instruksi kepada Director AI.
2. Director AI memahami tujuan dan batasan.
3. Director AI membuat rencana pekerjaan.
4. Director AI menentukan divisi yang relevan.
5. Division Head menerima tugas.
6. Division Head membagi tugas kepada Specialist Agents.
7. Specialist Agents menjalankan pekerjaan.
8. Hasil dikembalikan kepada Division Head.
9. Division Head melakukan pemeriksaan.
10. Hasil diserahkan kepada Director AI.
11. Director AI menyusun laporan atau hasil akhir.
12. Manusia menerima hasil dan melakukan persetujuan apabila diperlukan.

## 6.2 Delegasi Multi-Agent

Sistem harus mendukung:
- Satu tugas untuk satu agen.
- Satu tugas dengan beberapa agen.
- Pembagian tugas paralel.
- Delegasi bertingkat.
- Ketergantungan antartugas.
- Pemeriksaan hasil.
- Pengulangan pekerjaan.
- Eskalasi kepada Director AI.
- Penanganan kegagalan.

Setiap subtask harus mempunyai hubungan dengan tugas induk agar keseluruhan pekerjaan dapat dilacak.

## 6.3 Contoh Workflow

### A. Permintaan Pengembangan Website

1. Customer Support menerima permintaan.
2. Director AI menganalisis kebutuhan.
3. Technology Head menerima pekerjaan.
4. Software Architect menyusun rancangan.
5. Developer mengerjakan aplikasi.
6. UI/UX Designer menyiapkan antarmuka jika diperlukan.
7. QA Engineer melakukan pengujian.
8. Division Head memeriksa hasil.
9. Director AI menyusun laporan dan hasil akhir.
10. Manusia menyetujui publikasi atau penyerahan apabila dibutuhkan.

### B. Penjualan Komputer

1. Sales Agent menerima permintaan.
2. Product Researcher mencari spesifikasi dan produk.
3. Inventory Coordinator memeriksa stok.
4. Procurement Agent menyiapkan alternatif pengadaan bila stok tidak tersedia.
5. Order Administrator menyiapkan pesanan.
6. Finance Assistant menyiapkan dokumen administrasi.
7. Manusia menyetujui transaksi sesuai kebijakan.
8. Sistem memperbarui status pesanan dan laporan.

### C. Layanan Reparasi

1. Customer Support menerima keluhan.
2. Service Coordinator membuat tiket layanan.
3. Diagnostic Assistant membantu analisis awal.
4. Maintenance Scheduler menyusun jadwal.
5. Teknisi manusia melakukan pemeriksaan dan perbaikan.
6. Quality Control Assistant menyiapkan checklist pemeriksaan.
7. Teknisi atau petugas berwenang memverifikasi hasil fisik.
8. Customer Support menyampaikan status kepada pelanggan.
9. Sistem menyimpan riwayat layanan.

### D. Program Pelatihan

1. Permintaan pelatihan diterima.
2. Academy Head menganalisis kebutuhan.
3. Curriculum Designer menyusun materi.
4. Training Coordinator menyiapkan jadwal.
5. Instructor Assistant mendukung pelaksanaan.
6. Learning Evaluator mengolah hasil evaluasi.
7. Academy Head menyusun laporan.
8. Manusia mengesahkan hasil atau dokumen yang memerlukan kewenangan instruktur/lembaga.

---

# 7. ARSITEKTUR TEKNOLOGI

## 7.1 Keputusan Teknologi

| Lapisan | Teknologi |
|---|---|
| Frontend | React + TypeScript + Vite |
| 3D Engine | Three.js |
| Styling | CSS |
| Backend API | CodeIgniter 4 REST API |
| Database | MySQL |
| Agent Runtime | Hermes |
| Model Gateway | 9Router |
| Realtime | Server-Sent Events (SSE) |
| Web Server | Nginx |
| Process Manager | Supervisor atau systemd |
| Authentication | JWT atau mekanisme token yang aman |
| Deployment | Server/VPS atau infrastruktur yang mendukung runtime |

Keputusan ini menjadi arsitektur dasar. Perubahan teknologi harus melalui evaluasi dan persetujuan desain arsitektur, bukan dilakukan secara sepihak pada saat implementasi.

## 7.2 Arsitektur Komunikasi

Alur utama:

**Frontend React / Three.js → CI4 REST API → Business Logic & Database → Hermes → 9Router → AI Model Provider**

Ketentuan:
- Frontend tidak memanggil Hermes secara langsung.
- Frontend tidak memanggil 9Router secara langsung.
- CI4 menjadi pintu masuk dan pengendali akses aplikasi.
- Hermes menjalankan orkestrasi agen.
- 9Router menjadi gateway untuk seluruh pemanggilan model AI.
- MySQL menjadi sumber data utama aplikasi.
- Event dari runtime dikirim ke backend untuk diproses dan diteruskan ke frontend.
- Kunci API dan kredensial tidak boleh ditempatkan di frontend.

## 7.3 Tanggung Jawab CI4

CI4 menjadi application system-of-record.

Mengelola:
- Identitas perusahaan.
- Struktur organisasi.
- Divisi.
- Jabatan.
- Data agen dan konfigurasi.
- Pengguna dan hak akses.
- Pelanggan.
- Proyek.
- Tugas.
- Workflow.
- Approval.
- Transaksi dan administrasi.
- Ruang dan tata letak kantor 3D.
- Konfigurasi sistem.
- Audit log.
- Riwayat dan laporan.

CI4 juga bertanggung jawab atas validasi, otorisasi, pembatasan akses, dan pencatatan aktivitas aplikasi.

## 7.4 Tanggung Jawab Hermes

Hermes merupakan mesin eksekusi dan orkestrasi AI.

Fungsi:
- Mengelola agent profiles.
- Menjalankan agent runtime.
- Menjalankan instruksi dan skills.
- Menggunakan tools.
- Mengelola delegasi dan subagents.
- Mengelola konteks dan memori agen sesuai kebijakan.
- Mengirimkan hasil pekerjaan.
- Menyampaikan event runtime.

Model awal yang direkomendasikan adalah satu pusat Hermes runtime dengan beberapa agent profiles dan subagents. Tidak perlu membuat satu server Hermes untuk setiap karakter atau agen.

Pemisahan runtime dapat dipertimbangkan kemudian apabila beban, keamanan, atau kebutuhan isolasi meningkat.

## 7.5 Tanggung Jawab 9Router

9Router menjadi satu-satunya gateway pemanggilan model AI.

Fungsi:
- Menghubungkan beberapa provider model.
- Memilih model sesuai konfigurasi agen.
- Mengatur penggunaan model.
- Mendukung strategi fallback jika provider gagal.
- Mengelola routing berdasarkan kebutuhan pekerjaan.

Integrasi harus diuji untuk:
- Endpoint kompatibel.
- Streaming.
- Tool calling.
- Error handling.
- Timeout.
- Model fallback.
- Penggunaan token.
- Konsistensi respons.

Tidak boleh ada jalur pemanggilan model alternatif yang melewati kebijakan gateway tanpa keputusan arsitektur.

---

# 8. SISTEM MEMORI, SKILLS, DAN TOOLS

## 8.1 Agent Memory

Memori agen dibedakan berdasarkan kebutuhan:

- **Working Context:** Konteks pekerjaan yang sedang berlangsung.
- **Task Memory:** Informasi yang relevan terhadap tugas tertentu.
- **Agent Memory:** Pengetahuan dan preferensi kerja agen.
- **Organizational Knowledge:** Pengetahuan bersama yang telah disetujui untuk digunakan organisasi.
- **Business Records:** Data resmi perusahaan yang disimpan dalam database aplikasi.

Memori tidak boleh menjadi pengganti data resmi perusahaan. Informasi keuangan, pelanggan, persediaan, dan transaksi harus mengacu pada sumber data yang berwenang.

## 8.2 Skills

Skills merupakan kemampuan atau prosedur yang dapat digunakan agen.

Contoh:
- Software Development.
- Web Research.
- Data Analysis.
- Document Generation.
- Customer Service.
- Product Research.
- Financial Reporting.
- Technical Diagnosis.
- Quality Assurance.
- Project Planning.

Skills harus memiliki dokumentasi, versi, izin penggunaan, dan pengujian.

## 8.3 Tools

Tools merupakan fungsi yang memungkinkan agen melakukan tindakan.

Contoh:
- Database Query.
- Internal Search.
- File Management.
- Web Search.
- Email.
- Project Management.
- CRM.
- Inventory.
- Document Generator.
- Code Repository.
- Notification Service.

Setiap tool harus:
- Memiliki identitas.
- Memiliki deskripsi dan skema input.
- Memiliki kontrol akses.
- Memiliki validasi.
- Memiliki batas penggunaan.
- Memiliki logging.
- Mendukung penanganan kegagalan.

Agen hanya dapat menggunakan tools yang diizinkan untuk perannya.

---

# 9. DESAIN 3D MCE VIRTUAL OFFICE

## 9.1 Konsep Visual

Kantor virtual MCE menggunakan visualisasi 3D isometrik dengan tampilan kantor yang dapat diamati dan dijelajahi.

Lingkungan ini harus menjadi representasi visual dari organisasi dan aktivitas sistem.

## 9.2 Teknologi Rendering

- React sebagai kerangka aplikasi.
- Three.js sebagai mesin 3D.
- TypeScript untuk pengelolaan kode.
- Orthographic Camera untuk tampilan isometrik.
- HTML/CSS overlay untuk elemen antarmuka.
- Procedural geometry untuk objek dasar.
- Canvas texture untuk tekstur prosedural.

Tidak perlu menggunakan model 3D eksternal untuk seluruh objek. Geometri sederhana dapat dibuat langsung menggunakan Three.js. GLTF/GLB dapat ditambahkan untuk objek atau karakter khusus ketika memang diperlukan.

## 9.3 Layered Canvas Architecture

Visualisasi menggunakan dua lapisan:

**Lapisan 3D**
- Bangunan.
- Ruangan.
- Meja dan kursi.
- Komputer.
- Karakter agen.
- Pencahayaan.
- Bayangan.
- Animasi.

**Lapisan HTML/CSS**
- Nama agen.
- Jabatan.
- Status pekerjaan.
- Speech bubble.
- Label ruangan.
- Mission Board.
- Notifikasi.
- Panel interaksi.

Elemen HTML dapat diproyeksikan dari posisi 3D menggunakan koordinat kamera dan `Vector3.project(camera)`.

Pendekatan ini menjaga teks tetap tajam, mudah diakses, dan lebih mudah dikelola.

## 9.4 Ruangan Virtual

Rancangan ruang awal:

1. Executive Office
2. Technology Lab
3. Academy Room
4. Technical Service Workshop
5. Hardware Assembly Lab
6. Commerce Office
7. Corporate Operations Office
8. Meeting Room
9. Lounge / Break Area

Ruangan harus terhubung dengan data divisi dan aktivitas yang sebenarnya.

## 9.5 Karakter Agen

Setiap karakter mewakili satu agen.

Karakter memiliki:
- Identitas.
- Nama.
- Jabatan.
- Divisi.
- Tampilan/avatar.
- Posisi awal.
- Ruangan.
- Status.
- Aktivitas.
- Animasi.
- Interaksi.

Karakter tidak boleh berubah secara acak. Identitas visual, proporsi, material, dan ciri karakter harus konsisten.

## 9.6 Animasi dan State

Animasi yang diperlukan:
- Idle.
- Typing.
- Reading.
- Walking.
- Talking.
- Thinking.
- Reviewing.
- Meeting.
- Waiting.
- Celebrating completion.

Animasi harus terhubung dengan status pekerjaan atau event runtime.

Contoh:
- Agen menerima tugas → Assigned.
- Agen mulai bekerja → Working dan animasi mengetik.
- Agen berpindah divisi → Walking.
- Agen memeriksa hasil → Reviewing.
- Agen menunggu keputusan manusia → Waiting Approval.
- Pekerjaan selesai → Completed.

Jangan membuat animasi aktivitas seolah-olah agen sedang bekerja apabila tidak ada pekerjaan yang benar-benar berjalan.

## 9.7 Navigasi dan Pathfinding

Sistem harus mendukung:
- Pergerakan antar meja.
- Pergerakan menuju ruang rapat.
- Pergerakan menuju Mission Board.
- Pergerakan menuju area istirahat.
- Penghindaran tabrakan sederhana.
- Jalur yang dapat disesuaikan dengan tata ruang.

Pathfinding awal dapat menggunakan jalur berbasis segmen koridor dan Manhattan routing. Implementasi harus dapat dikembangkan apabila layout kantor bertambah kompleks.

## 9.8 Camera Director

Kamera mendukung:
- Wide Shot.
- Close-up.
- Fokus agen.
- Fokus divisi.
- Fokus ruangan.
- Transisi kamera.
- Rotasi atau pergerakan terbatas.
- Penyesuaian untuk desktop dan mobile.

Tampilan utama tetap isometrik. Interaksi kamera tidak boleh mengganggu keterbacaan informasi.

## 9.9 Day/Night Cycle

Sistem pencahayaan dapat berubah berdasarkan waktu:
- Directional Light.
- Hemisphere Light.
- Cahaya jendela.
- Lampu plafon.
- Lampu meja.
- Emissive monitor.

Siklus siang dan malam merupakan visualisasi tambahan dan tidak boleh menggantikan status operasional yang sebenarnya.

## 9.10 Theme System

Sistem tema harus menggunakan registry/factory agar tema dapat dikelola secara konsisten.

Jumlah tema aktual harus mengikuti hasil audit implementasi template. Jangan mengunci jumlah tema berdasarkan asumsi sebelum kode sumber diperiksa.

Tema dapat mengatur:
- Warna ruangan.
- Material.
- Pencahayaan.
- Lingkungan.
- Tampilan UI.
- Efek visual.

## 9.11 Mission Board

Mission Board merupakan pusat informasi pekerjaan.

Informasi:
- Tugas aktif.
- Tugas menunggu.
- Tugas mendesak.
- Tugas selesai.
- Angka kinerja.
- Divisi yang sedang bekerja.
- Approval yang tertunda.

Data berasal dari backend, bukan data simulasi.

Skema laporan awal seperti `ringkasan`, `menunggu`, `mendesak`, dan `angka` dapat dipertahankan apabila sesuai dengan kebutuhan API.

## 9.12 Timelapse dan Video Recorder

Fitur perekaman aktivitas kantor dapat dikembangkan menggunakan:
- Headless Chrome.
- Chrome DevTools Protocol.
- Screenshot frame.
- FFmpeg.

Script perekaman yang sudah tersedia dalam template harus diaudit dan dipertahankan apabila masih sesuai.

Perekaman tidak boleh menghambat proses operasional utama.

---

# 10. DASHBOARD MANAGEMENT

Dashboard merupakan pusat administrasi dan pengendalian perusahaan.

Semua konfigurasi organisasi, agen, workflow, dan lingkungan virtual harus dapat dikelola tanpa mengedit source code untuk setiap perubahan operasional.

## 10.1 Dashboard Overview

Informasi:
- Status perusahaan.
- Jumlah divisi.
- Jumlah agen.
- Agen aktif.
- Pekerjaan berjalan.
- Pekerjaan selesai.
- Pekerjaan gagal.
- Approval tertunda.
- Penggunaan model AI.
- Ringkasan performa.

## 10.2 Organization Management

Fitur:
- Profil perusahaan.
- Struktur organisasi.
- Divisi.
- Jabatan.
- Hubungan hierarki.
- Penanggung jawab.
- Status divisi.

## 10.3 Agent Management

Fitur:
- Tambah/edit/nonaktifkan agen.
- Penempatan divisi.
- Jabatan dan tanggung jawab.
- Persona.
- System instruction.
- Model.
- Memory policy.
- Skills.
- Tools.
- Hak akses.
- Status runtime.
- Riwayat pekerjaan.
- Evaluasi kinerja.

## 10.4 Agent Runtime Monitoring

Informasi:
- Agent online/offline.
- Status eksekusi.
- Task aktif.
- Delegasi.
- Penggunaan model.
- Error.
- Waktu respons.
- Riwayat event.

Dashboard harus membedakan status runtime sebenarnya dari status konfigurasi agen.

## 10.5 Model & Provider Management

Fitur:
- Konfigurasi model.
- Provider.
- Model default.
- Routing.
- Fallback.
- Pengaturan per agen.
- Monitoring penggunaan.
- Batas pemakaian.
- Riwayat error.

Kredensial disimpan secara aman di sisi server.

## 10.6 Memory Management

Fitur:
- Kebijakan memori.
- Jenis memori.
- Kepemilikan memori.
- Masa penyimpanan.
- Penggunaan memori.
- Pemeriksaan dan koreksi informasi.
- Kebijakan penghapusan.

## 10.7 Skills & Tools Management

Fitur:
- Registrasi skills.
- Versi skills.
- Registrasi tools.
- Skema input/output.
- Pengaturan izin.
- Pengujian.
- Riwayat penggunaan.

## 10.8 Task & Workflow Management

Fitur:
- Pembuatan tugas.
- Penugasan.
- Delegasi.
- Subtask.
- Prioritas.
- Deadline.
- Dependensi.
- Approval.
- Riwayat.
- Hasil.
- Retry dan eskalasi.

## 10.9 3D Office Management

Fitur:
- Pengelolaan ruangan.
- Penempatan agen.
- Tata letak.
- Objek.
- Avatar.
- Tema.
- Kamera.
- Pencahayaan.
- Label.
- Pengaturan tampilan.

## 10.10 Business Operations Modules

Dashboard harus dikembangkan sebagai pusat pengelolaan kegiatan bisnis MCE.

Modul yang direncanakan:

1. **CRM:** Pelanggan, prospek, komunikasi, dan riwayat layanan.
2. **Project Management:** Proyek, tahapan, tugas, deadline, dan hasil.
3. **Sales & Orders:** Penawaran, pesanan, dan status penjualan.
4. **Inventory Management:** Stok, barang, komponen, dan mutasi.
5. **Service Management:** Tiket layanan, diagnosis, perbaikan, dan pemeliharaan.
6. **Academy Management:** Program, kurikulum, peserta, jadwal, dan evaluasi.
7. **Finance Management:** Administrasi keuangan, pencatatan, dan laporan.
8. **HR & Organization:** Data personel, jabatan, dan administrasi internal.
9. **Procurement:** Permintaan dan proses pengadaan.
10. **Business Intelligence:** Analisis dan laporan kinerja perusahaan.

Modul dapat dikembangkan secara bertahap. Jangan membuat seluruh modul sebagai fitur kosong hanya demi melengkapi tampilan dashboard.

## 10.11 User, Roles & Permissions

Hak akses minimal:
- Owner / Super Administrator.
- Administrator.
- Human Supervisor.
- Divisional Manager.
- Operational Staff.
- Auditor / Read Only.

Role dan permission harus dapat dikonfigurasi. Akses ke data, tools, dan tindakan harus mengikuti prinsip least privilege.

## 10.12 Audit & System Settings

Fitur:
- Audit log.
- Riwayat aktivitas.
- Konfigurasi aplikasi.
- Integrasi.
- Notifikasi.
- Backup.
- Keamanan.
- Kebijakan approval.
- Batas penggunaan AI.

---

# 11. DESAIN DATABASE DAN SUMBER DATA

MySQL menjadi basis data utama aplikasi.

Struktur database harus modular dan mengikuti relasi yang jelas.

Kelompok entitas utama:

### A. Organization
- companies
- divisions
- positions
- organization_units
- organization_relations

### B. Agents
- agents
- agent_profiles
- agent_instructions
- agent_models
- agent_memories
- agent_skills
- agent_tools
- agent_permissions

### C. Tasks & Workflow
- tasks
- subtasks
- task_assignments
- task_dependencies
- workflows
- workflow_steps
- task_events
- task_results

### D. Approval
- approval_requests
- approval_actions
- approval_policies

### E. Users & Security
- users
- roles
- permissions
- role_permissions
- user_roles
- access_tokens
- audit_logs

### F. Business Operations
- customers
- projects
- products
- inventory
- sales_orders
- service_tickets
- academy_programs
- finance_records
- procurement_requests

### G. Virtual Office
- office_rooms
- office_objects
- office_layouts
- agent_placements
- avatar_configs
- themes
- visual_settings

### H. AI Runtime & Monitoring
- runtime_sessions
- runtime_events
- model_usage
- provider_status
- agent_execution_logs

Struktur ini merupakan rancangan entitas awal, bukan ketentuan bahwa semua tabel harus dibuat sekaligus. Setiap fase harus menghasilkan skema yang sesuai kebutuhan nyata dan migrasi yang terkontrol.

---

# 12. REALTIME EVENT & STATUS MANAGEMENT

## 12.1 Mekanisme Realtime

SSE menjadi mekanisme utama untuk mengirim pembaruan dari backend ke frontend.

Contoh event:
- agent.status_changed
- agent.task_assigned
- agent.task_started
- agent.task_completed
- agent.task_failed
- agent.delegated
- task.progress_updated
- approval.created
- approval.updated
- runtime.connected
- runtime.disconnected
- office.activity_updated

WebSocket hanya digunakan apabila terdapat kebutuhan komunikasi dua arah yang tidak dapat dipenuhi secara memadai oleh REST API dan SSE.

## 12.2 Status Agen

Status yang harus didukung:
- Idle
- Assigned
- Working
- Delegating
- Reviewing
- Waiting Approval
- Completed
- Failed
- Paused
- Cancelled

Status pekerjaan dan status runtime harus dikelola secara konsisten.

## 12.3 Konsistensi Data dan Event

- Event harus mempunyai ID unik.
- Event memiliki timestamp.
- Event memiliki identitas sumber.
- Event dapat dikaitkan dengan task dan agent.
- Frontend harus mampu menangani koneksi terputus.
- Sistem harus mendukung pengambilan ulang snapshot kondisi terbaru.
- Event yang sama tidak boleh menyebabkan tindakan bisnis terduplikasi.

---

# 13. KEAMANAN DAN GOVERNANCE

Keamanan merupakan bagian inti sistem, bukan pekerjaan tambahan di akhir proyek.

Persyaratan:

1. Autentikasi dan otorisasi di backend.
2. Validasi seluruh input.
3. Proteksi endpoint API.
4. Pembatasan akses berdasarkan role dan permission.
5. Penyimpanan secret dan API key di server.
6. Pengamanan komunikasi antarkomponen.
7. Pembatasan tool dan akses agen.
8. Audit log untuk tindakan penting.
9. Approval untuk tindakan berisiko.
10. Backup database.
11. Penanganan error dan pemulihan layanan.
12. Pengelolaan sesi dan token secara aman.
13. Pembatasan akses file dan sistem operasi.
14. Isolasi proses eksekusi yang berisiko.
15. Pengujian keamanan sebelum produksi.

AI tidak boleh memperoleh akses administrator secara otomatis. Akses terhadap sistem, file, database, dan layanan eksternal harus diberikan berdasarkan kebutuhan tugas dan batas kewenangan.

---

# 14. STANDAR PENGEMBANGAN DAN KUALITAS

## 14.1 Repository Foundation

Repository `malikisaputra-source/kantor-ai-template` menjadi titik awal pengembangan.

Template harus dipertahankan dan diaudit sebelum dilakukan perubahan besar.

Frontend tetap menggunakan React + Three.js. Tidak dilakukan migrasi ke Vue.

## 14.2 Audit Source Code

Audit meliputi:
- Struktur folder.
- Dependensi.
- Versi package.
- Komponen React.
- Siklus hidup Three.js.
- Pengelolaan renderer.
- Scene dan camera.
- Animasi.
- Pathfinding.
- Overlay.
- Tema.
- Script testing.
- Script recorder.
- Potensi memory leak.
- Responsivitas.

Jangan mengasumsikan versi dependensi yang tercantum dalam dokumen lama pasti sama dengan kondisi repository aktual.

## 14.3 Struktur Kode

Struktur awal yang direkomendasikan:

```text
mce/
├── frontend/
│   ├── public/
│   └── src/
│       ├── app/
│       ├── components/
│       ├── dashboard/
│       ├── office3d/
│       │   ├── scene/
│       │   ├── camera/
│       │   ├── characters/
│       │   ├── animations/
│       │   ├── navigation/
│       │   ├── rooms/
│       │   ├── objects/
│       │   ├── themes/
│       │   └── overlays/
│       ├── services/
│       ├── hooks/
│       ├── types/
│       └── styles/
│
├── backend/
│   ├── app/
│   │   ├── Controllers/
│   │   ├── Models/
│   │   ├── Services/
│   │   ├── Entities/
│   │   ├── Filters/
│   │   ├── Database/
│   │   └── Config/
│   ├── public/
│   └── writable/
│
├── infrastructure/
│   ├── nginx/
│   ├── supervisor/
│   ├── deployment/
│   └── monitoring/
│
├── scripts/
│   ├── test/
│   ├── recorder/
│   └── maintenance/
│
└── documentation/
    ├── architecture/
    ├── database/
    ├── api/
    ├── workflows/
    └── deployment/
```

Struktur final mengikuti audit repository dan tidak boleh memindahkan atau menghapus file penting tanpa pemeriksaan dependensi.

## 14.4 Standar Kualitas

- Kode modular.
- Penamaan konsisten.
- API terdokumentasi.
- Validasi input.
- Penanganan error.
- Pengujian unit dan integrasi.
- Pengujian workflow.
- Pengujian realtime.
- Pengujian rendering 3D.
- Pengujian responsivitas.
- Dokumentasi teknis.
- Migrasi database terkontrol.

---

# 15. ROADMAP PEMBANGUNAN — 10 FASE

Pembangunan dilaksanakan bertahap. Setiap fase harus mempunyai hasil yang dapat diuji dan kriteria penerimaan sebelum melanjutkan ke fase berikutnya.

## FASE 1 — Repository Foundation & Source Code Audit

Tujuan:
Memahami template dan menetapkan fondasi kode.

Pekerjaan:
- Audit repository.
- Audit package dan dependensi.
- Audit React dan Three.js.
- Audit struktur scene.
- Audit karakter dan animasi.
- Audit camera.
- Audit pathfinding.
- Audit theme system.
- Audit script test dan recorder.
- Identifikasi bug dan technical debt.
- Menyusun struktur target.

Hasil:
- Laporan audit.
- Peta source code.
- Daftar perubahan.
- Struktur arsitektur final.
- Baseline pengujian.

Kriteria selesai:
Template dapat dijalankan, komponen penting dipahami, dan rencana refactoring telah disetujui.

## FASE 2 — Backend Foundation & Database

Tujuan:
Membangun backend resmi MCE.

Pekerjaan:
- Menyiapkan CI4 REST API.
- Konfigurasi MySQL.
- Autentikasi dan otorisasi.
- API response standard.
- Error handling.
- Database migration.
- Struktur perusahaan dasar.
- Audit log.
- Konfigurasi environment.
- API dokumentasi.

Hasil:
Backend berjalan dengan API yang aman dan struktur data dasar.

Kriteria selesai:
Frontend dapat mengambil data backend melalui API terautentikasi dan data tersimpan konsisten.

## FASE 3 — Dynamic Organization Management

Tujuan:
Membangun struktur perusahaan dan organisasi AI yang dinamis.

Pekerjaan:
- Profil perusahaan MCE.
- Enam divisi.
- Jabatan.
- Director AI.
- Division Head AI.
- Specialist Agents.
- Relasi hierarki.
- Agent profiles.
- Persona dan instructions.
- Model dan memory policy.
- Skills dan tools metadata.
- Permissions.

Hasil:
Organisasi MCE dapat dikelola melalui dashboard tanpa mengubah source code untuk setiap penambahan agen atau divisi.

Kriteria selesai:
Struktur organisasi dapat ditampilkan, diubah, dan divalidasi melalui backend.

## FASE 4 — Dynamic 3D Virtual Office

Tujuan:
Mengubah template menjadi kantor virtual MCE yang terhubung dengan organisasi.

Pekerjaan:
- Menyiapkan scene kantor.
- Ruangan sesuai divisi.
- Tata letak.
- Karakter agen.
- Identitas karakter konsisten.
- Penempatan dinamis.
- Camera Director.
- Overlay HTML/CSS.
- Animasi.
- Pathfinding.
- Tema.
- Day/night cycle.
- Mission Board.
- Responsivitas.

Hasil:
Kantor 3D MCE dapat menampilkan struktur organisasi dan agen berdasarkan data backend.

Kriteria selesai:
Penambahan atau perubahan agen dan ruangan melalui backend dapat tercermin pada lingkungan 3D.

## FASE 5 — Hermes Agent Engine Integration

Tujuan:
Mengintegrasikan mesin eksekusi AI.

Pekerjaan:
- Menyiapkan Hermes runtime.
- Integrasi melalui backend.
- Konfigurasi Director AI.
- Konfigurasi Division Head.
- Konfigurasi Specialist Agents.
- Delegasi.
- Subagents.
- Skills.
- Tools.
- Memory policy.
- Runtime monitoring.
- Error handling.

Hasil:
Agen AI dapat menerima dan menjalankan pekerjaan sesuai struktur organisasi.

Kriteria selesai:
Instruksi dapat diproses melalui Director AI, didelegasikan, dijalankan, dan hasilnya dikembalikan melalui backend.

## FASE 6 — 9Router Multi-Provider Integration

Tujuan:
Membangun konektivitas model AI melalui satu gateway.

Pekerjaan:
- Integrasi 9Router.
- Provider configuration.
- Model routing.
- Pemilihan model per agen.
- Fallback.
- Streaming.
- Tool calling.
- Timeout.
- Error handling.
- Usage monitoring.

Hasil:
Seluruh agen menggunakan model AI melalui gateway yang terpusat.

Kriteria selesai:
Model dapat dipilih berdasarkan konfigurasi dan kegagalan provider ditangani tanpa merusak keseluruhan workflow.

## FASE 7 — Business Operations & Multi-Agent Workflow

Tujuan:
Mengoperasikan kegiatan bisnis MCE melalui AI.

Pekerjaan:
- Task Management.
- Workflow Management.
- CRM.
- Project Management.
- Sales & Orders.
- Inventory.
- Service Management.
- Academy Management.
- Finance Administration.
- HR Administration.
- Procurement.
- Business Intelligence.
- Delegasi multi-agent.
- Approval workflow.
- Human-in-the-loop.

Implementasi modul bisnis dilakukan berdasarkan prioritas dan kebutuhan proses nyata. Tidak harus seluruh modul diselesaikan sekaligus.

Hasil:
AI dapat menjalankan proses bisnis yang diizinkan dan mengelola hasil pekerjaan melalui modul aplikasi.

Kriteria selesai:
Setidaknya satu workflow bisnis nyata berjalan dari penerimaan permintaan hingga penyelesaian dan pelaporan, dengan kontrol kewenangan yang berfungsi.

## FASE 8 — Realtime Event & Activity Monitoring

Tujuan:
Menghubungkan aktivitas aktual sistem dengan kantor virtual.

Pekerjaan:
- SSE.
- Runtime event.
- Task event.
- Agent status.
- Progress.
- Delegation.
- Approval.
- Notifikasi.
- Reconnection.
- Snapshot state.
- Event audit.

Hasil:
Dashboard dan kantor 3D menampilkan kondisi operasional secara realtime.

Kriteria selesai:
Perubahan status yang terjadi pada backend dan runtime dapat terlihat secara konsisten di frontend.

## FASE 9 — Dashboard Organization & Business Management

Tujuan:
Menyediakan pusat kendali lengkap bagi pemilik dan pengelola MCE.

Pekerjaan:
- Dashboard Overview.
- Organization Management.
- Agent Management.
- Runtime Monitoring.
- Model & Provider Management.
- Memory Management.
- Skills & Tools Management.
- Task & Workflow Management.
- Business Operations Modules.
- 3D Office Management.
- User & Permissions.
- Audit & Settings.
- Approval Center.
- Laporan perusahaan.

Hasil:
Seluruh fungsi pengelolaan sistem dan kegiatan bisnis dapat dikendalikan melalui dashboard.

Kriteria selesai:
Administrator dapat mengelola organisasi, agen, proses, hak akses, dan konfigurasi visual tanpa mengedit source code untuk perubahan operasional normal.

## FASE 10 — Production, Security & Optimization

Tujuan:
Mempersiapkan MCE untuk operasional produksi.

Pekerjaan:
- Deployment.
- Nginx.
- Process manager.
- Pengamanan server.
- Pengamanan API.
- Backup dan restore.
- Monitoring.
- Logging.
- Pengujian beban.
- Pengujian keamanan.
- Optimasi Three.js.
- Optimasi asset.
- Lazy loading.
- Optimasi penggunaan memori.
- Pengujian mobile.
- Dokumentasi operasional.
- Prosedur pemulihan.

Hasil:
Sistem MCE siap digunakan dalam lingkungan produksi dengan mekanisme pemantauan dan pemulihan.

Kriteria selesai:
Sistem stabil, aman, dapat dipantau, dan mempunyai prosedur backup, pemulihan, serta penanganan insiden.

---

# 16. PRIORITAS IMPLEMENTASI

Walaupun seluruh divisi menjadi target akhir, pembangunan tidak boleh memaksakan seluruh proses bisnis berjalan bersamaan sejak awal.

Tahapan implementasi bisnis yang disarankan:

### Prioritas 1 — Technology & Digital Solutions

Alasan:
- Memiliki proses yang relatif mudah didigitalisasi.
- Hasil pekerjaan dapat diverifikasi melalui dokumen, kode, dan pengujian.
- Dapat menjadi fondasi pengembangan sistem MCE sendiri.
- Cocok untuk menguji delegasi multi-agent.

### Prioritas 2 — Corporate Operations

Alasan:
- Mendukung administrasi seluruh divisi.
- Menjadi pusat pelaporan dan pemantauan.
- Membantu menetapkan kebijakan dan kontrol operasional.

### Prioritas 3 — Technical Services dan Commerce

Alasan:
- Memiliki proses pelanggan, tiket, produk, stok, dan pesanan yang dapat dimodelkan dengan workflow.
- Memerlukan integrasi data operasional dan kontrol manusia.

### Prioritas 4 — Academy dan Hardware & Manufacturing

Alasan:
- Memerlukan pengelolaan proses yang lebih spesifik.
- Sebagian aktivitas melibatkan pelaksanaan fisik dan tenaga manusia.
- Dapat dibangun setelah fondasi organisasi dan workflow stabil.

Urutan ini merupakan rekomendasi implementasi, bukan pembatasan terhadap pengembangan divisi lain.

---

# 17. PRINSIP KINERJA DAN OPTIMASI

## 17.1 Frontend dan 3D

- Hindari rendering yang tidak diperlukan.
- Gunakan instancing untuk objek berulang bila sesuai.
- Batasi jumlah shadow-casting lights.
- Optimalkan geometri dan material.
- Gunakan level of detail apabila diperlukan.
- Kelola lifecycle renderer dan event listener.
- Hindari memory leak.
- Sesuaikan kualitas visual dengan kemampuan perangkat.
- Pertimbangkan reduced motion.
- Pastikan dashboard tetap dapat diakses tanpa harus menjalankan scene 3D.

## 17.2 Backend dan Runtime

- Pisahkan request API dari pekerjaan AI yang berjalan lama.
- Gunakan job/task tracking.
- Atur timeout dan retry.
- Gunakan logging terstruktur.
- Pantau penggunaan model.
- Hindari pemanggilan model yang tidak diperlukan.
- Terapkan rate limiting.
- Gunakan proses background untuk pekerjaan yang sesuai.
- Pastikan runtime dapat dipulihkan setelah kegagalan.

## 17.3 Infrastruktur

Sistem produksi tidak boleh mengandalkan keterbatasan shared hosting atau cPanel yang tidak mendukung kebutuhan runtime.

Infrastruktur harus mampu menjalankan:
- PHP dan CI4.
- MySQL.
- Hermes runtime.
- 9Router sesuai mekanisme deployment-nya.
- Nginx.
- Proses background.
- SSE.
- Monitoring.

Kapasitas server ditentukan melalui pengujian beban, jumlah agen aktif, concurrency, dan kebutuhan model. Jangan menetapkan kebutuhan hardware final tanpa hasil pengukuran.

---

# 18. INDIKATOR KEBERHASILAN

Keberhasilan proyek diukur melalui indikator berikut:

### Organisasi
- Seluruh divisi dapat dikelola.
- Agen memiliki fungsi dan kewenangan yang jelas.
- Delegasi dapat dilacak.
- Perubahan organisasi tercermin pada dashboard dan kantor 3D.

### AI Operations
- Director AI dapat mengoordinasikan pekerjaan.
- Division Head dapat membagi tugas.
- Specialist Agents dapat menjalankan tugas.
- Hasil dapat diperiksa dan dilaporkan.
- Kegagalan dapat ditangani dan dieskalasikan.

### Business Operations
- Proses bisnis memiliki workflow.
- Data operasional tersimpan konsisten.
- Persetujuan manusia berfungsi.
- Riwayat pekerjaan dapat ditelusuri.
- Laporan dapat dihasilkan dari data aktual.

### Virtual Office
- Karakter konsisten.
- Ruangan sesuai struktur organisasi.
- Aktivitas 3D mengikuti event nyata.
- Navigasi dan interaksi berjalan baik.
- Tampilan responsif.

### Governance
- Akses sesuai kewenangan.
- Tindakan penting tercatat.
- Approval dapat ditelusuri.
- Data dan kredensial terlindungi.
- Tersedia backup dan pemulihan.

---

# 19. ATURAN PENGEMBANGAN PROYEK

Seluruh pengembangan berikutnya harus mengikuti ketentuan:

1. Nama sistem tetap `mce`.
2. Identitas perusahaan tetap MCE — Mitra Creative Environment.
3. Tingkat otomatisasi tetap Hybrid.
4. React + Three.js dipertahankan sebagai frontend utama.
5. CI4 REST API menjadi backend aplikasi.
6. MySQL menjadi basis data utama.
7. Hermes menjadi mesin orkestrasi dan eksekusi agen.
8. 9Router menjadi gateway model AI.
9. Frontend tidak mengakses Hermes atau 9Router secara langsung.
10. Aktivitas 3D harus berasal dari status dan event aktual.
11. Semua organisasi dan agen harus dapat dikelola secara dinamis.
12. Dashboard harus mendukung pengelolaan bisnis, bukan hanya pengelolaan karakter AI.
13. Setiap agen memiliki batas kewenangan.
14. Tindakan penting memerlukan persetujuan manusia.
15. Pekerjaan fisik tetap membutuhkan pelaksana manusia.
16. Implementasi dilakukan berdasarkan fase dan kriteria penerimaan.
17. Jangan mengganti arsitektur utama tanpa evaluasi dan persetujuan.
18. Jangan membuat fitur simulasi yang seolah-olah sudah terhubung dengan sistem produksi.
19. Jangan membuat seluruh modul bisnis sekaligus tanpa prioritas dan kebutuhan yang jelas.
20. Setiap fase wajib memiliki dokumentasi dan pengujian.

---

# 20. DEFINISI AKHIR PROYEK

**MCE adalah perusahaan yang menggunakan AI sebagai tenaga operasional digital melalui sebuah sistem terintegrasi bernama `mce`.**

Sistem ini mempunyai:
- Struktur organisasi AI.
- Director AI sebagai pusat koordinasi.
- Division Head AI sebagai pengelola unit usaha.
- Specialist AI Agents sebagai pelaksana pekerjaan.
- Hermes sebagai mesin orkestrasi.
- 9Router sebagai gateway model AI.
- CI4 dan MySQL sebagai pusat pengelolaan aplikasi dan data.
- Dashboard sebagai pusat kendali perusahaan.
- Three.js sebagai representasi kantor virtual 3D.
- Human-in-the-Loop sebagai mekanisme pengawasan dan persetujuan.

Tujuan akhirnya adalah menjadikan MCE sebagai **AI-Driven Company dengan operasional Hybrid**, di mana AI menangani pekerjaan digital dan operasional yang telah diizinkan, sementara manusia tetap memegang kendali atas arah perusahaan, keputusan penting, tanggung jawab hukum, transaksi berisiko, dan pekerjaan yang membutuhkan tindakan fisik.

Kantor virtual 3D bukan sekadar visualisasi dekoratif. Ia menjadi representasi interaktif dari organisasi, tugas, koordinasi, dan aktivitas aktual perusahaan.

---

**STATUS DOKUMEN: FINAL — VERSI 3.0**

Dokumen ini menjadi acuan utama pembangunan MCE AI Company Operating System. Perubahan berikutnya harus berupa penyempurnaan teknis, spesifikasi per fase, atau keputusan baru yang terdokumentasi, tanpa mengubah keputusan dasar proyek secara sepihak.