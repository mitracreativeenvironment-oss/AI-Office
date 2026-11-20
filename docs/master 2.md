# MASTER DOCUMENT PEMBANGUNAN MCE
## MCE — AI COMPANY OPERATING SYSTEM
### Integrated Company Management, Autonomous AI Operations & Living Virtual Headquarters

**Nama Sistem:** MCE (`mce`)  
**Nama Perusahaan:** Mitra Creative Environment  
**Dokumen:** Master Development & System Architecture  
**Versi:** 5.0  
**Status:** Master Architecture — Integrated Specification  
**Model Otomatisasi:** Hybrid Automation  
**Arsitektur Visual:** Living Isometric Virtual Headquarters  
**Frontend:** React + Three.js + TypeScript + Vite  
**Backend:** CodeIgniter 4 Full REST API  
**Database:** MySQL  
**AI Agent Runtime:** Hermes  
**AI Model Gateway:** 9Router  
**Realtime:** Server-Sent Events (SSE)  
**Web Server:** Nginx

---

# BAGIAN I — VISI, IDENTITAS, DAN ARAH PERUSAHAAN

## 1. Latar Belakang

MCE (Mitra Creative Environment) akan dibangun sebagai perusahaan yang memanfaatkan sistem AI terintegrasi untuk menjalankan aktivitas bisnis, operasional, pengembangan produk, pemasaran, pelayanan pelanggan, serta pengelolaan perusahaan.

Sistem yang dibangun bukan sekadar chatbot, aplikasi manajemen proyek, atau dashboard administrasi.

MCE dirancang sebagai **AI Company Operating System**, yaitu sistem yang mengintegrasikan manusia, organisasi, AI Agent, pekerjaan, informasi, dan sumber daya perusahaan dalam satu lingkungan operasional.

Di dalam sistem ini, setiap divisi memiliki AI Division Head dan Specialist Agents yang menjalankan tanggung jawab sesuai bidang masing-masing.

Director AI bertindak sebagai pusat koordinasi operasional yang menerjemahkan arahan Owner menjadi rencana, membagi pekerjaan, mengatur prioritas, mengoordinasikan divisi, memantau pelaksanaan, dan menyampaikan hasil kepada Owner.

Perusahaan tetap dapat menjalankan aktivitas pengembangan bisnis meskipun sedang tidak menerima proyek pelanggan.

Karena itu, sistem mempunyai dua jalur pekerjaan utama:

1. Project Delivery — pekerjaan berdasarkan permintaan, kontrak, proyek, dan pelanggan.
2. Business Growth & Internal Development — pekerjaan berkelanjutan untuk promosi, riset, pengembangan produk, pengembangan kemampuan, dan pertumbuhan perusahaan.

Seluruh aktivitas direpresentasikan melalui dashboard manajemen dan kompleks kantor virtual 3D.

## 2. Tujuan Sistem

1. Membangun sistem manajemen perusahaan berbasis AI.
2. Mengotomatisasi pekerjaan operasional berdasarkan struktur organisasi.
3. Membuat setiap divisi mempunyai AI Agent dengan tanggung jawab khusus.
4. Mengelola proyek pelanggan dan pekerjaan internal.
5. Menjalankan strategi pertumbuhan bisnis secara berkelanjutan.
6. Mengembangkan produk dan layanan perusahaan.
7. Mengelola pelanggan, prospek, dan kemitraan.
8. Memantau pekerjaan AI secara terpusat.
9. Menyediakan kontrol dan persetujuan oleh Owner.
10. Menghadirkan kantor virtual 3D yang menggambarkan aktivitas perusahaan secara aktual.
11. Menyediakan pengalaman publik yang interaktif tanpa membuka informasi internal.
12. Membangun infrastruktur yang dapat dikembangkan seiring pertumbuhan perusahaan.

## 3. Identitas Perusahaan

**MCE — Mitra Creative Environment**

Bidang usaha berdasarkan KBLI yang diberikan:

| KBLI | Bidang |
|---|---|
| 62900 | Aktivitas Jasa Teknologi Informasi dan Komputer Lainnya |
| 85592 | Pendidikan Komputer (Teknologi Informasi dan Komunikasi) Swasta |
| 95101 | Reparasi dan Pemeliharaan Komputer dan Peralatan Sejenisnya |
| 95400 | Jasa Intermediasi Reparasi dan Perawatan Komputer, Barang Pribadi dan Rumah Tangga, Mobil, serta Sepeda Motor |
| 62199 | Aktivitas Pemrograman Komputer Lainnya YTDL |
| 26210 | Industri Komputer dan Perakitan Komputer |
| 46511 | Perdagangan Besar Komputer dan Perlengkapan Komputer |
| 47401 | Perdagangan Eceran Komputer dan Perlengkapannya |
| 62209 | Aktivitas Konsultansi Komputer dan Manajemen Fasilitas Komputer Lainnya |

Pengelompokan KBLI ke dalam divisi merupakan struktur organisasi operasional, bukan perubahan klasifikasi kegiatan usaha resmi.

## 4. Struktur Unit Bisnis

MCE memiliki enam unit bisnis utama.

### 4.1 MCE Technology & Digital Solutions

KBLI: 62900, 62199, 62209.

Fokus:
- Pengembangan website.
- Pengembangan aplikasi.
- Pengembangan sistem informasi.
- Pengembangan aplikasi bisnis.
- Integrasi API.
- Konsultasi teknologi informasi.
- Infrastruktur dan manajemen sistem.
- Riset dan inovasi teknologi.

### 4.2 MCE Academy

KBLI: 85592.

Fokus:
- Pendidikan komputer.
- Pelatihan teknologi informasi.
- Kursus pemrograman.
- Pelatihan aplikasi.
- Pelatihan digital.
- Webinar dan workshop.
- Pengembangan materi pendidikan.

### 4.3 MCE Technical Services

KBLI: 95101, 95400.

Fokus:
- Perbaikan komputer.
- Pemeliharaan perangkat.
- Diagnostik perangkat keras.
- Layanan teknis.
- Paket maintenance.
- Koordinasi layanan reparasi.

### 4.4 MCE Hardware & Manufacturing

KBLI: 26210.

Fokus:
- Perakitan komputer.
- Riset perangkat keras.
- Desain konfigurasi komputer.
- Pengembangan produk hardware.
- Pengujian perangkat.
- Dokumentasi teknis.

### 4.5 MCE Commerce & Distribution

KBLI: 46511, 47401.

Fokus:
- Perdagangan komputer.
- Penjualan perangkat.
- Pengadaan perangkat.
- Katalog produk.
- Distribusi.
- Pengelolaan pemasok.
- Penjualan produk teknologi.

### 4.6 MCE Corporate Operations

Unit pendukung bersama seluruh perusahaan.

Fokus:
- Administrasi.
- Keuangan.
- Human Resources.
- Marketing.
- Branding.
- CRM.
- Pengadaan.
- Operasional.
- Business Intelligence.
- Legal and Compliance Coordination.
- Business Growth Center.

Corporate Operations bukan tambahan klasifikasi KBLI, melainkan fungsi manajemen perusahaan.

---

# BAGIAN II — ORGANISASI DAN STRUKTUR AI

## 5. Hierarki Organisasi

Struktur organisasi MCE:

Human Owner / Human Management  
↓  
Director AI  
↓  
Division Head AI  
↓  
Specialist AI Agents

### 5.1 Owner / Human Management

Owner merupakan pemegang kendali tertinggi perusahaan.

Tanggung jawab:
- Menentukan arah perusahaan.
- Menetapkan target bisnis.
- Memberikan instruksi strategis.
- Menyetujui tindakan penting.
- Mengendalikan anggaran.
- Mengatur kebijakan perusahaan.
- Menilai kinerja sistem.
- Mengelola akses dan kewenangan.

Owner dapat memberikan instruksi langsung kepada Director AI melalui dashboard.

### 5.2 Director AI

Director AI merupakan pusat koordinasi operasional AI.

Tanggung jawab:
- Menerjemahkan arahan Owner menjadi rencana.
- Menentukan prioritas.
- Membagi pekerjaan.
- Menugaskan divisi.
- Mengawasi Division Head.
- Mengatur kapasitas agent.
- Mengawasi pekerjaan proyek.
- Mengawasi Business Growth Center.
- Mengevaluasi hasil pekerjaan.
- Mengeskalasi keputusan kepada Owner.
- Menyusun laporan perusahaan.

Director AI tidak menggantikan kewenangan persetujuan strategis manusia.

### 5.3 Division Head AI

Setiap divisi mempunyai Division Head yang bertanggung jawab kepada Director AI.

Tanggung jawab:
- Mengelola pekerjaan divisi.
- Menentukan kebutuhan specialist.
- Mendelegasikan pekerjaan.
- Mengawasi kualitas.
- Memeriksa hasil pekerjaan.
- Melaporkan perkembangan kepada Director AI.

### 5.4 Specialist AI Agents

Specialist Agents menjalankan tugas sesuai spesialisasi.

Setiap agent memiliki atribut:

- Agent ID.
- Nama.
- Divisi.
- Jabatan.
- Tanggung jawab.
- Persona.
- System Instruction.
- Model AI.
- Memory Policy.
- Skills.
- Tools.
- Permissions.
- Runtime Status.
- Work History.
- Performance Metrics.

## 6. Model Otomatisasi

MCE menggunakan **Hybrid Automation**.

AI dapat mengerjakan pekerjaan yang telah mendapat izin tanpa harus menunggu persetujuan manusia pada setiap langkah.

Namun, tindakan strategis, finansial, kontraktual, dan berisiko tinggi harus melalui persetujuan manusia.

### 6.1 AI Autonomous Actions

Dapat berjalan otomatis sesuai kebijakan:
- Riset informasi publik.
- Analisis pasar.
- Penyusunan draft dokumen.
- Pengembangan kode dalam lingkungan yang diizinkan.
- Pengujian.
- Analisis data.
- Pengelolaan tugas.
- Pembuatan konten yang telah mendapat izin.
- Pemantauan sistem.
- Pelaporan internal.
- Pengelolaan jadwal rutin.

### 6.2 Human Approval Required

Memerlukan persetujuan:
- Pengeluaran dana.
- Pembelian.
- Kontrak.
- Penawaran harga khusus.
- Perubahan kebijakan perusahaan.
- Kemitraan strategis.
- Komunikasi sensitif.
- Publikasi konten sensitif.
- Penggunaan data pelanggan untuk tujuan baru.
- Tindakan yang memiliki dampak hukum atau reputasi signifikan.

### 6.3 Tingkat Otomatisasi

Setiap tindakan memiliki:
- Permission.
- Risk Level.
- Approval Policy.
- Budget Limit.
- Execution Policy.
- Audit Requirement.

---

# BAGIAN III — ARSITEKTUR TEKNIS

## 7. Arsitektur Utama

Sistem menggunakan arsitektur:

**React + Three.js → CI4 REST API → MySQL → Hermes → 9Router → AI Model Providers**

Pembagian tanggung jawab:

### 7.1 React + Three.js

Mengelola:
- Public Portal.
- Admin Dashboard.
- Virtual Headquarters.
- Visualisasi agent.
- Interaksi pengguna.
- Mission Board.
- Data visualization.

### 7.2 CodeIgniter 4

Menjadi pusat aplikasi dan system of record.

Mengelola:
- Authentication.
- Authorization.
- Organization.
- Divisions.
- Agents metadata.
- Tasks.
- Projects.
- CRM.
- Finance.
- Business Growth.
- Approvals.
- Campus configuration.
- API.
- Audit logs.

### 7.3 MySQL

Menjadi penyimpanan data utama perusahaan.

Data bisnis dan administrasi tidak boleh bergantung hanya pada memory agent.

### 7.4 Hermes

Mengelola:
- Agent runtime.
- Agent profiles.
- Memory.
- Skills.
- Tools.
- Delegation.
- Subagents.
- Execution.
- Runtime events.

Gunakan satu runtime Hermes terpusat dengan banyak profil dan subagent sebagai pendekatan awal, bukan satu server Hermes terpisah untuk setiap agent.

### 7.5 9Router

Menjadi gateway utama model AI.

Tanggung jawab:
- Pemilihan model.
- Routing.
- Provider management.
- Fallback.
- Model availability.
- Streaming.
- Pengelolaan koneksi model.

Integrasi Hermes harus menggunakan endpoint yang kompatibel.

### 7.6 Realtime

Gunakan SSE sebagai jalur utama untuk:
- Agent status.
- Task status.
- Dashboard activity.
- Notification.
- Approval update.
- Virtual Office event.

WebSocket hanya digunakan jika terdapat kebutuhan komunikasi dua arah yang tidak memadai dengan SSE.

## 8. Arsitektur Keamanan

- Frontend tidak boleh memanggil Hermes secara langsung.
- Frontend tidak boleh memanggil 9Router secara langsung.
- API menggunakan autentikasi dan otorisasi server-side.
- Secret dan API keys hanya berada di server.
- Gunakan RBAC dan permission policy.
- Terapkan audit log.
- Terapkan rate limiting.
- Pisahkan data publik dan internal.
- Gunakan validasi input.
- Terapkan backup dan pemulihan.
- Gunakan environment configuration.
- Terapkan least privilege.

---

# BAGIAN IV — MCE BUSINESS OPERATING SYSTEM

## 9. Dua Jalur Pekerjaan

MCE harus tetap aktif meskipun tidak sedang menerima proyek pelanggan.

### 9.1 Project Delivery

Pekerjaan yang berasal dari:
- Pelanggan.
- Kontrak.
- Proyek.
- Pesanan.
- Permintaan layanan.
- Instruksi Owner.

Alur:

Request → Qualification → Planning → Assignment → Execution → Review → Approval → Delivery → Closure.

### 9.2 Business Growth & Internal Development

Pekerjaan yang berasal dari kebutuhan pertumbuhan perusahaan.

Contoh:
- Riset peluang.
- Pembuatan konten.
- Promosi.
- SEO.
- Pengembangan produk.
- Pengembangan portofolio.
- Pelatihan internal.
- Riset teknologi.
- Analisis pesaing.
- Pengembangan kemitraan.

Director AI mengatur kapasitas kedua jalur berdasarkan:
- Prioritas.
- Deadline.
- Nilai bisnis.
- Kapasitas agent.
- Target perusahaan.
- Anggaran.
- Risiko.
- Persetujuan.

Project Delivery dapat memperoleh prioritas ketika terdapat deadline pelanggan yang mendesak, tetapi Business Growth tidak boleh selalu dihentikan.

## 10. Task & Workflow Management

Setiap pekerjaan memiliki:

- Task ID.
- Judul.
- Deskripsi.
- Sumber permintaan.
- Project ID.
- Division.
- Assigned Agent.
- Priority.
- Deadline.
- Status.
- Dependencies.
- Approval Requirements.
- Progress.
- Result.
- Audit Trail.

Status tugas:

- Draft.
- Pending.
- Assigned.
- In Progress.
- Reviewing.
- Waiting Approval.
- Completed.
- Failed.
- Paused.
- Cancelled.

Sistem harus mendukung:
- Task delegation.
- Task dependency.
- Recurring task.
- Task queue.
- Retry policy.
- Escalation.
- Human intervention.
- Workload balancing.

## 11. Project Management

Fitur:
- Project registration.
- Customer information.
- Scope.
- Requirements.
- Budget.
- Timeline.
- Milestones.
- Team assignment.
- Division assignment.
- Task board.
- Deliverables.
- Review.
- Approval.
- Change requests.
- Project documentation.
- Completion report.

Setiap proyek dapat memiliki beberapa divisi yang bekerja bersama.

## 12. Knowledge Management

Sistem pengetahuan perusahaan meliputi:
- Company Knowledge Base.
- SOP.
- Internal Documents.
- Technical Documentation.
- Project Knowledge.
- Product Knowledge.
- Customer Knowledge.
- Agent Knowledge.
- Lessons Learned.

Pisahkan:
- Official Company Records.
- Agent Memory.
- Temporary Working Context.
- Public Knowledge.

Memory agent tidak boleh menggantikan dokumen resmi atau catatan transaksi perusahaan.

---

# BAGIAN V — BUSINESS GROWTH CENTER

## 13. Tujuan

Business Growth Center merupakan pusat pengembangan bisnis MCE.

Modul ini bukan hanya alat pengelolaan media sosial, melainkan pusat strategi pertumbuhan yang mengoordinasikan seluruh divisi.

Prinsip:
- Balanced Marketing Automation.
- Organic First.
- Human Approval for High-Risk Actions.
- Continuous Business Development.

## 14. Modul Business Growth Center

### 14.1 Business Strategy & Goals

- Visi dan arah perusahaan.
- Target bisnis.
- Segmentasi pelanggan.
- Prioritas layanan.
- Target pendapatan.
- KPI.
- Rencana kuartalan.

### 14.2 Market Intelligence

- Riset pasar.
- Riset kebutuhan pelanggan.
- Analisis pesaing berdasarkan sumber publik.
- Analisis teknologi.
- Peluang regional.
- Tren harga.
- Peluang layanan baru.

### 14.3 Marketing Campaigns

- Campaign planning.
- Target audience.
- Campaign objectives.
- Key message.
- Channel.
- Content calendar.
- Approval.
- Performance evaluation.

### 14.4 Content Studio

Mendukung:
- Artikel.
- Caption.
- Infografis.
- Poster.
- Video pendek.
- Materi edukasi.
- Konten promosi.
- Konten produk.
- Materi presentasi.

Setiap konten memiliki status:
- Idea.
- Draft.
- Review.
- Approval.
- Scheduled.
- Published.
- Archived.

### 14.5 SEO Management

- Keyword research.
- Search trends.
- Content planning.
- Technical SEO.
- On-page SEO.
- Internal linking.
- Search performance.
- Traffic analytics.

### 14.6 Lead Generation

- Riset calon pelanggan dari sumber publik.
- Klasifikasi prospek.
- Scoring.
- Pencocokan layanan.
- Pembuatan lead record.
- Persiapan komunikasi.

Tidak boleh melakukan pengumpulan data pribadi tanpa dasar yang sah atau pengiriman spam.

### 14.7 CRM & Prospects

Pipeline:

New Lead → Researching → Qualified → Contact Preparation → Awaiting Approval → Contacted → Responded → Negotiation → Proposal → Won / Lost / Nurturing.

Fitur:
- Customer profile.
- Lead source.
- Segmentation.
- Contact history.
- Preferences.
- Follow-up.
- Opportunity.
- Conversion.

### 14.8 Product Development

- Product ideas.
- Market validation.
- Competitor analysis.
- Feasibility.
- MVP.
- Prototype.
- Testing.
- Documentation.
- Commercialization.

Contoh produk potensial:
- Website templates.
- Administration systems.
- Inventory applications.
- School information systems.
- UMKM applications.
- Service systems.
- Academy management.
- SaaS products.

### 14.9 Portfolio & Case Studies

- Project portfolio.
- Service portfolio.
- Screenshots.
- Demo.
- Case studies.
- Evidence.
- Publication permission.

Dilarang membuat klaim proyek, hasil, atau testimoni fiktif.

### 14.10 Partnership Management

- Partner research.
- Partnership fit.
- Proposal draft.
- Communication.
- Partnership monitoring.

Kontrak dan komitmen kemitraan memerlukan persetujuan manusia.

### 14.11 Performance Analytics

- Content performance.
- Traffic.
- SEO.
- Leads.
- Conversion.
- Customer retention.
- Revenue.
- Costs.
- Margin.
- ROI.

### 14.12 Autonomous Business Schedule

Mendukung jadwal:
- Harian.
- Mingguan.
- Bulanan.
- Kuartalan.

Trigger:
- Time.
- Event.
- Status change.
- Customer request.
- Data change.
- KPI alert.
- Director instruction.
- Human instruction.

### 14.13 Budget & Approval

- Budget allocation.
- Spending request.
- Approval.
- Expense history.
- Budget utilization.
- Financial reporting.

## 15. Kebijakan Marketing

MCE menggunakan Organic First.

Saluran utama:
- Website.
- Instagram.
- Facebook.
- TikTok.
- YouTube.
- LinkedIn.
- Kanal digital relevan lainnya.

AI dapat melakukan riset, membuat konten, analisis, SEO, dan publikasi yang telah mendapat izin.

Tidak ada pengeluaran iklan berbayar otomatis.

Paid advertising hanya boleh dilakukan setelah Owner mengaktifkan kebijakan tersebut, menetapkan batas anggaran, dan memberikan persetujuan.

Semua aktivitas harus mematuhi kebijakan platform, privasi, persetujuan, dan ketentuan komunikasi.

## 16. Kontribusi Divisi terhadap Pertumbuhan

- Technology: produk digital, demo, inovasi, portofolio, artikel teknis.
- Academy: konten pendidikan, kursus, webinar, materi pembelajaran.
- Technical Services: edukasi maintenance, paket layanan, promosi.
- Hardware: riset produk, spesifikasi, perakitan, dokumentasi.
- Commerce: katalog, riset produk, penjualan, pemasok.
- Corporate Operations: strategi, branding, SEO, CRM, leads, marketing, analytics.

---

# BAGIAN VI — PUBLIC PORTAL DAN PRIVATE DASHBOARD

## 17. Dual Interface Architecture

MCE memiliki dua lingkungan antarmuka:

1. Public Portal.
2. Private Management Dashboard.

Keduanya terhubung dengan backend yang sama tetapi memiliki API, permission, dan informasi yang berbeda.

### 17.1 Public Portal

Tujuan:
- Mengenalkan MCE.
- Menampilkan layanan.
- Menampilkan produk.
- Menampilkan portofolio.
- Memberikan informasi Academy.
- Menerima permintaan.
- Memberikan konsultasi awal.
- Menghadirkan Public Virtual Headquarters.

Fitur:
- Company profile.
- Services.
- Products.
- Portfolio.
- Academy.
- Articles.
- Contact.
- AI Receptionist.
- Customer Assistant.
- Project request.
- Consultation request.
- Customer account.
- Request tracking.

### 17.2 Public AI Agents

Agent publik:
- AI Receptionist.
- AI Customer Assistant.
- AI Service Consultant.

Mereka hanya memiliki akses pada informasi publik dan fungsi layanan pelanggan yang diizinkan.

Mereka tidak boleh:
- Memerintah Director AI.
- Mengakses internal agents secara bebas.
- Membuat tugas internal tanpa mekanisme penerimaan permintaan.
- Membaca data pelanggan lain.
- Mengakses informasi keuangan.
- Membaca instruksi dan memori internal.
- Mengubah konfigurasi perusahaan.

Permintaan publik diteruskan melalui Public Request Service, kemudian diproses sistem internal berdasarkan kebijakan MCE.

### 17.3 Private Management Dashboard

Dashboard menjadi pusat kendali perusahaan.

Akses:
- Owner.
- Internal Management.
- Division Head.
- Internal Staff sesuai permission.

Dashboard harus berfungsi tanpa harus menggunakan kantor 3D. Jika rendering 3D gagal, administrasi dan operasional perusahaan tetap berjalan.

---

# BAGIAN VII — MCE VIRTUAL HEADQUARTERS

## 18. Konsep Utama

MCE Virtual Headquarters merupakan kompleks perkantoran virtual berbasis 3D isometrik.

Karakteristik final:

- District Layout.
- Modern Tropical Architecture.
- Cutaway Miniature Buildings.
- Isometric Camera.
- Living Office Simulation.
- Public Observation Mode.
- Private Operations Mode.

Kawasan terdiri atas gedung terpisah, jalan utama, jalan penghubung, taman, plaza, parkir, gerbang, dan fasilitas pendukung.

## 19. Masterplan Kawasan

### DISTRICT A — EXECUTIVE & CORPORATE HEADQUARTERS

Gedung:
1. Executive Headquarters.
2. Corporate Operations Building.

Executive Headquarters:
- Owner Office.
- Director AI Office.
- Executive Reception.
- Strategic Planning Room.
- Executive Meeting Room.

Corporate Operations:
- Administration.
- Finance & Budget.
- HR.
- CRM.
- Marketing.
- Business Growth Center.
- General Operations.

### DISTRICT B — BUSINESS DIVISIONS

Gedung:
1. Technology & Digital Solutions.
2. MCE Academy.
3. Technical Services.
4. Hardware & Manufacturing.
5. Commerce & Distribution.

Setiap gedung memiliki ruang kerja, workstation, area koordinasi, dan fasilitas sesuai fungsi divisi.

### DISTRICT C — SHARED FACILITIES

Gedung dan fasilitas:
1. Meeting & Conference Center.
2. Innovation & Product Showroom.
3. Cafeteria & Lounge.
4. Central Park.
5. Outdoor Meeting Area.

### DISTRICT D — CAMPUS INFRASTRUCTURE

- Main Gate.
- Security Post.
- Main Road.
- Secondary Roads.
- Service Road.
- Pedestrian Paths.
- Visitor Parking.
- Staff Parking.
- Operational Vehicle Parking.
- Loading Area.
- Utility Building.
- Green Buffer.

## 20. Tata Letak Konseptual

Orientasi awal:

- Depan: Main Gate, Visitor Parking, Showroom.
- Tengah: Central Park, Meeting Center, Main Road.
- Sisi kawasan: gedung Business Divisions.
- Belakang: Executive Headquarters dan Corporate Operations.
- Area servis: Technical Services, Hardware, dan Logistics.

Hubungan spasial harus disusun melalui masterplan koordinat tetap.

Jalan utama menghubungkan gerbang ke pusat kawasan. Jalan sekunder menghubungkan setiap district. Jalur pedestrian menghubungkan gedung dan taman.

## 21. Arsitektur Gedung

Modern Tropical Architecture:
- Beton.
- Kaca.
- Baja.
- Material kayu dekoratif.
- Kanopi.
- Teras.
- Balkon.
- Atap tropis atau datar.
- Lanskap tropis.
- Pencahayaan alami.

### Cutaway Miniature

Sebagian dinding gedung dibuka atau dibuat transparan untuk memperlihatkan interior.

Interior menampilkan:
- Ruangan.
- Furnitur.
- Workstation.
- Komputer.
- Pintu.
- Koridor.
- Agent.

Cutaway tidak berarti publik memiliki akses terhadap data internal.

## 22. Living Office Simulation

Agent dapat:
- Berjalan.
- Bekerja.
- Mengetik.
- Membaca.
- Melakukan pemeriksaan.
- Berkoordinasi.
- Menghadiri rapat.
- Berpindah gedung.
- Menunggu.
- Beristirahat ketika idle.

Aktivitas harus merepresentasikan status aktual.

Status runtime:
- Idle.
- Assigned.
- Working.
- Delegating.
- Reviewing.
- Waiting Approval.
- Completed.
- Failed.
- Paused.
- Cancelled.

Animasi tidak boleh menjadi indikator palsu bahwa pekerjaan sedang berjalan.

## 23. Public Observation Mode

Publik dapat:
- Melihat kompleks.
- Melihat setiap gedung.
- Mengamati agent.
- Melihat agent berjalan.
- Melihat agent bekerja.
- Memilih gedung.
- Memilih karakter.
- Melihat identitas publik.
- Melihat divisi dan lokasi.
- Melihat status aktivitas umum.

Publik tidak dapat melihat:
- Judul tugas.
- Detail pekerjaan.
- Instruksi.
- Percakapan.
- Dokumen.
- Kode.
- Data pelanggan.
- Informasi proyek privat.
- Informasi finansial.
- Tools dan konfigurasi AI.

### Public Activity Projection

Backend menyediakan representasi publik yang telah disaring.

Contoh atribut:
- Public Agent ID.
- Public Name.
- Division.
- Building.
- Room.
- Presence.
- Activity State.
- Animation Type.
- Public Visibility.
- Updated At.

Data tersebut bukan objek runtime Hermes secara langsung.

## 24. Private Operations Mode

Owner dan personel berwenang dapat melihat:
- Seluruh gedung.
- Seluruh ruangan.
- Seluruh agent.
- Detail pekerjaan.
- Instruksi.
- Progres.
- Delegasi.
- Koordinasi.
- Hasil pekerjaan.
- Runtime status.
- Mission Board.
- Informasi operasional.

## 25. Kamera dan Navigasi

Gunakan Orthographic Camera.

Mode:
- Campus Overview.
- District Overview.
- Building Overview.
- Agent Observation.

Kontrol:
- Zoom.
- Pan.
- Orbit terbatas.
- Reset.
- Focus Building.
- Focus Agent.

## 26. Lingkungan Dinamis

- Day/night cycle.
- Pencahayaan.
- Lampu gedung.
- Lampu jalan.
- Vegetasi bergerak.
- Air mancur.
- Cuaca.
- Kendaraan operasional.
- Efek lingkungan.

Efek visual lingkungan boleh berjalan mandiri, tetapi aktivitas agent harus berdasarkan status aktual.

---

# BAGIAN VIII — ADMIN DASHBOARD: COMPANY MANAGEMENT CENTER

## 27. Tujuan Dashboard

Dashboard merupakan pusat administrasi dan kendali seluruh perusahaan MCE.

Dashboard harus memungkinkan Owner mengelola perusahaan tanpa harus mengubah kode program untuk setiap perubahan operasional.

Sistem harus mendukung pengaturan organisasi, agen, model AI, pekerjaan, proyek, keuangan, pelanggan, promosi, kompleks 3D, serta keamanan.

Dashboard memiliki navigasi dinamis berdasarkan role dan permission.

## 28. Struktur Navigasi Utama

### 28.1 OWNER OVERVIEW

- Executive Dashboard.
- Company Overview.
- Company Health.
- Business Performance.
- Financial Overview.
- AI Operations Overview.
- Approval Center.
- Critical Alerts.
- Executive Reports.

### 28.2 ORGANIZATION MANAGEMENT

- Company Profile.
- Company Structure.
- Business Units.
- Divisions.
- Departments.
- Positions.
- Organizational Hierarchy.
- Employees.
- Internal Users.
- Roles & Permissions.
- Organization Policies.

### 28.3 AI MANAGEMENT

- AI Director.
- Division Heads.
- Specialist Agents.
- Agent Profiles.
- Agent Instructions.
- Agent Personas.
- Agent Models.
- Agent Memory Policies.
- Agent Skills.
- Agent Tools.
- Agent Permissions.
- Agent Performance.
- Agent Runtime.
- Agent Activity Logs.
- Agent Work History.

### 28.4 AI INFRASTRUCTURE

- Hermes Runtime.
- Hermes Profiles.
- Runtime Health.
- Subagent Configuration.
- Delegation Rules.
- 9Router Gateway.
- Model Providers.
- Model Catalog.
- Model Routing.
- Fallback Policies.
- Model Availability.
- Streaming Test.
- Tool Calling Test.
- AI Usage.
- Token Consumption.
- AI Cost Monitoring.
- API Connections.
- Integration Health.

### 28.5 OPERATIONS CENTER

- Live Operations.
- Task Management.
- Task Queue.
- Workflow Management.
- Project Management.
- Recurring Jobs.
- Automation Schedule.
- Dependencies.
- Workload Distribution.
- Agent Capacity.
- Failed Jobs.
- Retry Management.
- Execution History.
- Mission Board.

### 28.6 BUSINESS GROWTH CENTER

- Business Strategy.
- Business Goals.
- Market Intelligence.
- Marketing Campaigns.
- Content Studio.
- Content Calendar.
- SEO Management.
- Lead Generation.
- CRM & Prospects.
- Product Development.
- Portfolio.
- Case Studies.
- Partnership Management.
- Performance Analytics.
- Growth Schedule.
- Budget & Approval.

### 28.7 CUSTOMER & SERVICE MANAGEMENT

- Customers.
- Customer Accounts.
- Customer Requests.
- Consultation Requests.
- Project Inquiries.
- Service Orders.
- Support Tickets.
- Customer Communication.
- Customer History.
- Feedback.
- Customer Satisfaction.

### 28.8 PROJECT & DELIVERY

- All Projects.
- Project Pipeline.
- Project Planning.
- Project Tasks.
- Milestones.
- Deliverables.
- Project Documents.
- Review & QA.
- Change Requests.
- Project Reports.

### 28.9 FINANCE & RESOURCE MANAGEMENT

- Finance Dashboard.
- Budget.
- Income.
- Expenses.
- Purchase Requests.
- Approval.
- Invoices.
- Receivables.
- Payables.
- Cost Centers.
- Project Costs.
- AI Usage Costs.
- Financial Reports.

Finance features must be implemented according to actual company accounting requirements and permissions. AI may assist with analysis and drafting but must not independently authorize financial transactions.

### 28.10 PRODUCT & INVENTORY MANAGEMENT

- Product Catalog.
- Product Development.
- Product Pricing.
- Product Categories.
- Inventory.
- Stock Movement.
- Suppliers.
- Procurement.
- Hardware Components.
- Sales Opportunities.

### 28.11 KNOWLEDGE & DOCUMENT MANAGEMENT

- Company Knowledge Base.
- SOP.
- Policies.
- Internal Documents.
- Technical Documentation.
- Project Knowledge.
- Product Documentation.
- Agent Knowledge.
- Document Approval.
- Document Versioning.

### 28.12 MCE VIRTUAL HEADQUARTERS

- Campus Overview.
- Campus Masterplan.
- District Management.
- Building Management.
- Room Management.
- Furniture & Assets.
- Agent Placement.
- Workstation Management.
- Navigation Network.
- Landscape Management.
- Public Scene Settings.
- Private Scene Settings.
- Agent Animation Settings.
- Campus Environment.
- Camera Settings.
- Public Visibility.
- Campus Analytics.

### 28.13 PUBLIC PORTAL MANAGEMENT

- Public Portal Settings.
- Homepage.
- Company Profile.
- Services.
- Products.
- Portfolio.
- Articles.
- Public AI Assistant.
- Public Agent Profiles.
- Public Information.
- Public Activity Projection.
- Customer Portal.
- Public Access Policy.
- Publication Approval.

### 28.14 COMMUNICATION CENTER

- Notifications.
- Internal Messages.
- Agent Communication Logs.
- Customer Messages.
- Announcements.
- Email Integration.
- Communication Templates.
- Alert Rules.

### 28.15 REPORTS & BUSINESS INTELLIGENCE

- Company Reports.
- Division Reports.
- Agent Reports.
- Project Reports.
- Growth Reports.
- Marketing Reports.
- Customer Reports.
- Finance Reports.
- AI Usage Reports.
- Campus Activity Reports.
- Custom Reports.
- Export Management.

### 28.16 SYSTEM ADMINISTRATION

- General Settings.
- Branding.
- Localization.
- Date & Time.
- Email Settings.
- API Settings.
- Database Status.
- Queue Management.
- Cache Management.
- Storage Management.
- Backup & Restore.
- Logs.
- Audit Trail.
- Security Center.
- Maintenance Mode.
- System Health.

## 29. Dashboard Executive Overview

Halaman awal Owner menampilkan ringkasan perusahaan.

Komponen:

### Company Health
- Kondisi sistem.
- Kondisi backend.
- Kondisi database.
- Kondisi Hermes.
- Kondisi 9Router.
- Ketersediaan model.
- Status realtime.

### Organization Overview
- Jumlah divisi.
- Jumlah agent terdaftar.
- Jumlah agent aktif.
- Agent bermasalah.
- Kapasitas kerja.

### Operations Overview
- Pekerjaan berjalan.
- Pekerjaan tertunda.
- Pekerjaan menunggu persetujuan.
- Pekerjaan selesai.
- Pekerjaan gagal.

### Business Overview
- Proyek aktif.
- Prospek baru.
- Peluang bisnis.
- Penawaran.
- Penjualan.
- Target pertumbuhan.

### Financial Overview
- Anggaran.
- Pengeluaran.
- Pendapatan.
- Komitmen biaya.
- Biaya AI.

Semua angka harus berasal dari data aktual dan definisi KPI yang terdokumentasi.

## 30. AI Operations Center

Halaman utama pemantauan AI harus memiliki:

- Live Agent Monitor.
- Agent Status.
- Division Activity.
- Active Tasks.
- Queue.
- Agent Delegation.
- Runtime Health.
- Failed Executions.
- Approval Queue.
- Recent Events.

Owner dapat memilih agent untuk melihat:
- Identitas.
- Divisi.
- Jabatan.
- Runtime status.
- Tugas.
- Model.
- Skills.
- Tools.
- Memory policy.
- Performance.
- Work history.

Akses terhadap system instruction, memory, dan data sensitif dibatasi kepada role yang mempunyai izin khusus.

## 31. Agent Management

Setiap agent dapat dibuat, diubah, diaktifkan, dinonaktifkan, atau dipindahkan berdasarkan kewenangan.

Konfigurasi:

- Agent identity.
- Division.
- Position.
- Persona.
- Responsibility.
- System instruction.
- Model.
- Model parameters.
- Skills.
- Tools.
- Permissions.
- Memory policy.
- Runtime profile.
- Scheduling policy.
- Approval policy.
- Performance target.

Perubahan konfigurasi harus tercatat dan dapat ditinjau.

## 32. AI Model & Provider Management

Dashboard mengelola konfigurasi model melalui 9Router.

Fitur:
- Provider list.
- Model list.
- Model availability.
- Model assignment.
- Model routing.
- Fallback.
- Usage.
- Token.
- Cost.
- Latency.
- Error rate.
- Streaming test.
- Tool calling test.

Pengaturan model per agent harus dapat dibedakan berdasarkan pekerjaan.

Tidak semua agent harus menggunakan model yang sama.

Perubahan provider dan credential memerlukan kontrol keamanan dan audit.

## 33. Automation Scheduler

Dashboard mengelola seluruh pekerjaan terjadwal.

Fitur:
- Daily schedule.
- Weekly schedule.
- Monthly schedule.
- Quarterly schedule.
- Event-based trigger.
- Condition-based trigger.
- KPI alert.
- Recurring task.
- Pause.
- Resume.
- Retry.
- Priority.
- Agent assignment.
- Capacity control.

Sistem harus mencegah duplikasi pekerjaan yang tidak diperlukan.

## 34. Approval Center

Seluruh tindakan yang memerlukan persetujuan masuk ke satu pusat persetujuan.

Informasi:
- Request.
- Requester.
- Division.
- Agent.
- Action.
- Risk.
- Budget impact.
- Supporting document.
- Recommendation.
- Decision history.

Pilihan:
- Approve.
- Reject.
- Request Revision.
- Escalate.

Keputusan Owner harus dicatat dalam audit trail.

## 35. Company Settings

Pengaturan perusahaan mencakup:

### General
- Nama.
- Identitas.
- Logo.
- Alamat.
- Kontak.
- Profil.
- Branding.

### Organization
- Divisi.
- Posisi.
- Jabatan.
- Struktur.
- Role.
- Permission.

### Business Policy
- Prioritas bisnis.
- Target.
- Approval.
- Risk policy.
- Budget policy.

### Automation
- Hybrid automation.
- Agent permission.
- Schedule.
- Trigger.
- Delegation.
- Retry.

### AI Infrastructure
- Hermes.
- 9Router.
- Model.
- Provider.
- Skills.
- Tools.

### Public Portal
- Homepage.
- Layanan.
- Konten.
- Public agents.
- Visibility.

### Virtual Headquarters
- Masterplan.
- Gedung.
- Ruangan.
- Public scene.
- Private scene.
- Agent placement.

### System
- Security.
- Database.
- Backup.
- Logs.
- API.
- Maintenance.

---

# BAGIAN IX — ROLE, PERMISSION, DAN AKSES

## 36. Role Utama

### Owner
Kendali penuh perusahaan.

### Internal Management
Pengelolaan sesuai kewenangan yang diberikan Owner.

### Division Head
Mengelola divisi dan agent di bawah tanggung jawabnya.

### Internal Staff
Mengakses fungsi pekerjaan sesuai role.

### Finance Staff
Mengakses fungsi keuangan sesuai permission.

### Marketing Staff
Mengakses Business Growth dan pemasaran sesuai permission.

### System Administrator
Mengelola infrastruktur dan sistem sesuai kewenangan teknis.

### Customer
Mengakses akun dan permintaan miliknya.

### Guest
Mengakses Public Portal.

Role dapat disesuaikan tanpa mengubah kode inti.

## 37. RBAC dan Permission

Gunakan kombinasi:
- Role-Based Access Control.
- Permission-Based Access.
- Resource Ownership.
- Division Scope.
- Approval Policy.

Setiap API harus melakukan pemeriksaan permission di backend.

Menyembunyikan menu pada frontend bukan mekanisme keamanan yang memadai.

---

# BAGIAN X — DATABASE DAN MODUL DATA

## 38. Kelompok Data Utama

### Company
- companies.
- company_settings.
- organization_units.
- divisions.
- departments.
- positions.
- users.
- roles.
- permissions.

### AI
- agents.
- agent_profiles.
- agent_instructions.
- agent_models.
- agent_skills.
- agent_tools.
- agent_permissions.
- agent_memory_policies.
- agent_runtime_events.
- agent_performance.

### Operations
- tasks.
- task_assignments.
- workflows.
- workflow_steps.
- schedules.
- triggers.
- projects.
- milestones.
- deliverables.
- approvals.

### Business Growth
- strategies.
- business_goals.
- campaigns.
- contents.
- keywords.
- leads.
- customers.
- opportunities.
- products.
- partnerships.
- analytics.

### Finance
- budgets.
- expenses.
- income_records.
- purchase_requests.
- invoices.
- financial_approvals.

### Campus
- campus_districts.
- campus_buildings.
- campus_rooms.
- campus_assets.
- campus_workstations.
- campus_navigation_nodes.
- campus_navigation_edges.
- campus_settings.
- campus_visibility_policies.
- agent_locations.
- agent_public_projections.

### System
- notifications.
- audit_logs.
- api_logs.
- system_events.
- backups.
- integrations.

Tabel harus mempunyai relasi, indeks, dan aturan retensi sesuai kebutuhan.

Jangan menyimpan setiap frame animasi sebagai transaksi database. Simpan perubahan lokasi atau checkpoint yang diperlukan, sedangkan interpolasi visual ditangani frontend.

---

# BAGIAN XI — KINERJA, MONITORING, DAN KEAMANAN

## 39. Performance Monitoring

Pantau:
- Backend response.
- Database performance.
- Queue.
- Hermes runtime.
- Model response.
- 9Router availability.
- SSE connections.
- Frontend performance.
- 3D rendering performance.
- Memory usage.
- Storage.

## 40. Security

- Authentication.
- RBAC.
- API authorization.
- Secret management.
- Rate limiting.
- Input validation.
- Audit logging.
- Backup.
- Recovery.
- Session security.
- Data isolation.
- Least privilege.

Public and Private API harus dipisahkan secara logis.

## 41. Data Privacy

- Data pelanggan hanya dapat diakses sesuai permission.
- Public Portal tidak menerima data internal.
- Public Activity Projection hanya menampilkan informasi yang diizinkan.
- Agent tidak boleh mengungkap data pelanggan kepada pengunjung lain.
- Data internal tidak boleh dikirim ke browser publik.
- Penggunaan data untuk tujuan baru harus mempunyai dasar yang sah.
- Retensi data harus dapat diatur.

---

# BAGIAN XII — DEPLOYMENT DAN OPERASIONAL

## 42. Infrastruktur

Target deployment tidak mengasumsikan shared hosting atau cPanel.

Komponen:
- Linux Server.
- Nginx.
- PHP untuk CI4.
- MySQL.
- Node build environment.
- Hermes runtime.
- 9Router.
- Supervisor atau systemd.
- SSL/TLS.
- Backup.
- Monitoring.

## 43. Proses Deployment

- Frontend dibangun sebagai static production assets.
- CI4 menyediakan REST API.
- Nginx melayani frontend dan melakukan reverse proxy API.
- SSE menggunakan konfigurasi proxy yang mendukung streaming dan buffering dinonaktifkan.
- Hermes berjalan sebagai service terkelola.
- 9Router berjalan atau terhubung melalui endpoint yang tersedia.
- Semua secret disimpan pada server.

## 44. Reliability

Sistem harus mendukung:
- Service restart.
- Queue recovery.
- Retry policy.
- Error logging.
- Database backup.
- Runtime health check.
- Model fallback.
- Reconnection.
- Maintenance mode.

Kegagalan visualisasi 3D tidak boleh menghentikan operasional dashboard dan backend.

---

# BAGIAN XIII — ROADMAP PEMBANGUNAN

## 45. Sepuluh Fase Pembangunan

### FASE 1 — COMPANY FOUNDATION

Membangun fondasi:
- Identitas MCE.
- Struktur organisasi.
- Enam divisi.
- Company settings.
- Role dan permission.
- Database dasar.

Output: fondasi organisasi dan administrasi perusahaan.

### FASE 2 — CORE SYSTEM & BACKEND

- CI4 REST API.
- Authentication.
- Authorization.
- Database.
- Audit.
- System configuration.
- API architecture.

Output: pusat aplikasi perusahaan.

### FASE 3 — AI INFRASTRUCTURE

- Hermes integration.
- 9Router integration.
- Provider configuration.
- Model routing.
- Runtime management.
- Skills.
- Tools.
- Memory policies.

Output: infrastruktur AI siap menjalankan agent.

### FASE 4 — ORGANIZATION & AGENT SYSTEM

- Director AI.
- Division Heads.
- Specialist Agents.
- Agent profiles.
- Agent permissions.
- Delegation.
- Agent monitoring.

Output: organisasi AI MCE.

### FASE 5 — OPERATIONS & PROJECT MANAGEMENT

- Task management.
- Workflow.
- Project management.
- Scheduling.
- Task queue.
- Approval.
- Reporting.

Output: sistem pelaksanaan pekerjaan perusahaan.

### FASE 6 — BUSINESS GROWTH CENTER

- Strategy.
- Market intelligence.
- Marketing.
- Content.
- SEO.
- Leads.
- CRM.
- Product development.
- Portfolio.
- Partnership.
- Analytics.
- Autonomous schedule.

Output: sistem pertumbuhan bisnis berkelanjutan.

### FASE 7 — PUBLIC PORTAL & CUSTOMER EXPERIENCE

- Public Portal.
- Service catalog.
- Product catalog.
- Portfolio.
- Customer account.
- Public AI Assistant.
- Public Request Service.
- Customer tracking.

Output: layanan digital untuk masyarakat dan pelanggan.

### FASE 8 — MANAGEMENT DASHBOARD

- Executive Dashboard.
- Organization Management.
- AI Management.
- AI Infrastructure.
- Operations Center.
- Business Growth.
- Customer Management.
- Finance.
- Product & Inventory.
- Knowledge.
- Reports.
- System Administration.

Output: pusat kendali perusahaan.

### FASE 9 — MCE VIRTUAL HEADQUARTERS

- Masterplan.
- Districts.
- Buildings.
- Roads.
- Landscape.
- Cutaway architecture.
- Interior.
- Agent characters.
- Pathfinding.
- Environment simulation.
- Public Observation Mode.
- Private Operations Mode.

Output: kompleks perkantoran virtual yang hidup.

### FASE 10 — FULL INTEGRATION & PRODUCTION

- Integrasi semua modul.
- Runtime event synchronization.
- Public Activity Projection.
- Dashboard realtime.
- Approval integration.
- Security testing.
- Performance optimization.
- Backup and recovery.
- Deployment.
- Documentation.
- Acceptance testing.

Output: MCE AI Company Operating System siap beroperasi.

Fase dapat dilaksanakan secara iteratif. Ketergantungan teknis harus dihormati dan modul yang belum siap tidak boleh dianggap telah selesai hanya karena tampilannya sudah tersedia.

---

# BAGIAN XIV — KRITERIA KEBERHASILAN

## 46. Company Operations

- Struktur organisasi tersedia.
- Setiap divisi memiliki AI Division Head.
- Specialist Agents dapat bekerja sesuai izin.
- Director AI dapat mengoordinasikan pekerjaan.
- Owner memiliki kendali strategis.

## 47. Business Growth

- Perusahaan memiliki aktivitas pengembangan bisnis berkelanjutan.
- Sistem dapat melakukan riset dan pemasaran organik.
- Prospek dan pelanggan dikelola melalui CRM.
- Produk dapat dikembangkan secara terstruktur.
- Pengeluaran berbayar tidak terjadi tanpa persetujuan.

## 48. Dashboard

- Owner dapat melihat kondisi seluruh perusahaan.
- Seluruh agent dapat dipantau.
- Pekerjaan dan proyek dapat dikelola.
- AI model dan runtime dapat dikonfigurasi.
- Approval dapat diproses.
- Keuangan dan laporan dapat dipantau.
- Virtual Headquarters dapat dikendalikan.

## 49. Virtual Headquarters

- Kompleks terdiri atas district dan gedung terpisah.
- Jalan dan fasilitas terhubung.
- Gedung menggunakan arsitektur tropis cutaway.
- Agent bergerak dan bekerja.
- Status visual mengikuti runtime.
- Public Observation tidak membocorkan informasi internal.
- Private Operations menampilkan informasi sesuai permission.

## 50. Production Readiness

- Keamanan backend diuji.
- API permission diuji.
- Public data isolation diuji.
- Runtime dapat dipulihkan.
- Database memiliki backup.
- Sistem dapat dipantau.
- Frontend dan dashboard tetap berfungsi ketika 3D tidak tersedia.

---

# BAGIAN XV — PRINSIP FINAL PEMBANGUNAN

1. MCE merupakan satu sistem perusahaan terintegrasi, bukan kumpulan aplikasi yang berdiri sendiri.
2. Owner tetap menjadi pemegang keputusan strategis.
3. Director AI mengoordinasikan pekerjaan dan kapasitas perusahaan.
4. Setiap divisi memiliki tanggung jawab dan AI Agent masing-masing.
5. Project Delivery dan Business Growth berjalan sebagai dua jalur pekerjaan.
6. Sistem menggunakan Hybrid Automation.
7. Organic First menjadi kebijakan promosi.
8. Pengeluaran, kontrak, dan tindakan berisiko tinggi memerlukan persetujuan manusia.
9. CI4 menjadi pusat aplikasi dan data perusahaan.
10. Hermes menjadi runtime AI Agent.
11. 9Router menjadi gateway model AI.
12. React dan Three.js menjadi teknologi visualisasi.
13. Dashboard merupakan pusat kendali seluruh perusahaan.
14. Virtual Headquarters merupakan representasi digital kawasan perkantoran MCE.
15. Publik dapat menyaksikan agent bekerja tanpa mengetahui detail pekerjaan.
16. Informasi internal hanya dapat diakses oleh pihak yang memiliki kewenangan.
17. Animasi agent harus merepresentasikan keadaan runtime aktual.
18. Sistem tetap dapat beroperasi ketika visualisasi 3D tidak tersedia.
19. Seluruh pengembangan harus modular, terdokumentasi, aman, dan dapat diperluas.
20. Implementasi harus mempertahankan teknologi dan struktur repository yang sudah dipilih kecuali ada keputusan perubahan secara eksplisit.

---

# PENUTUP

MCE dirancang sebagai perusahaan dengan sistem operasional AI yang terintegrasi.

Melalui organisasi AI, Business Growth Center, sistem manajemen perusahaan, dan MCE Virtual Headquarters, perusahaan memiliki kemampuan untuk mengerjakan proyek pelanggan sekaligus menjalankan kegiatan pengembangan bisnis secara berkelanjutan.

Kantor virtual bukan sekadar visual dekoratif, melainkan representasi digital dari organisasi dan aktivitas perusahaan.

Dashboard bukan sekadar panel administrasi, melainkan pusat kendali yang memungkinkan Owner mengawasi, mengatur, mengevaluasi, dan mengembangkan seluruh perusahaan.

**MCE — An AI-Driven Company, Operated Through One Integrated System.**