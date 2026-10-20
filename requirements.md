DOKUMEN KEBUTUHAN SISTEM (REQUIREMENT SPECIFICATION)
Aplikasi Web Task Management & Productivity System (Koperasi Simpan Pinjam)
1. RINGKASAN EKSEKUTIF
Sistem ini dirancang untuk mencatat, mengawasi, dan mengukur produktivitas kerja harian seluruh staf di Koperasi Simpan Pinjam secara efisien. Sistem membedakan dua jenis alur kerja:
1.	Tim IT / Developer: Alur pengembangan lunak yang ketat (BA Input Request & Test Case $\rightarrow$ Dev Develop & Test $\rightarrow$ BA Approve $\rightarrow$ Deploy).
2.	Staf Operasional / Non-IT: Alur pencatatan otomatis yang terintegrasi dengan Core Banking / Sistem KSP (berbasis nomor pengajuan pinjaman) dipadu dengan input manual untuk kegiatan non-sistem.
2. ARSITEKTUR TEKNOLOGI
•	Framework Backend/Frontend: PHP (Laravel 10 / 11)
•	Database Engine: MySQL 8.0+/Mariadb (nama database: db_task)
•	Autentikasi & Otorisasi: Laravel Breeze/Spatie Role-Permission

3. ROLE & HAK AKSES PENGGUNA
Role	Deskripsi Hak Akses
Owner / Manajemen	View Only (Dashboard Eksekutif): Memantau seluruh rekap harian, produktivitas per divisi, dan status deployment IT.
HRD	View & Audit: Melihat keaktifan pengisian tugas harian, keterlambatan, dan rekap jam kerja/SLA per staf.
Business Analyst (BA)	IT Workflow Manager: Membuat Change Request, menyusun narasi Test Case, serta memberikan Approval/Reject untuk rilis ke Production.
Developer (IT)	IT Executor: Mengakses tugas IT, menginput URL repository, mengisi status hasil test script (100% Passed), dan mengeksekusi Deploy.
Staf Operasional / Kredit	Operational User: Menerima pencatatan otomatis dari sistem KSP, serta mengisi Task Manual (Non-Sistem) dan menyesuaikan kategori tugas (Normal/Kompleks).
4. SPESIFIKASI FITUR KHUSUS
A. Alur Kerja Modul IT & Developer
1.	New Request & Test Case: BA membuat tiket baru dengan mengisi judul, business requirement, dan narasi test case.
2.	Development & Scripting: Developer mengerjakan koding dan membuat test script otomatis berdasarkan narasi BA.
3.	Execution & QA Check: Sistem mengunci tombol Submit for Approval. Tombol hanya aktif jika Developer mencentang/mengonfirmasi status "100% Test Passed".
4.	BA Approval: BA menerima notifikasi tiket siap diuji/di-approve. BA dapat menekan [Approve] atau [Reject / Revisi].
5.	Deployment: Setelah di-approve BA, Developer menekan tombol [Deploy to Production] untuk menutup tiket (Closed).
B. Alur Kerja Modul Staf Operasional (KSP)
1.	Integrasi Auto-Generate (API / Event Trigger):
o	Setiap kali staf melakukan pengubahan status pada sistem utama KSP (misal: validasi berkas pengajuan #LOAN-xxx), sistem KSP mengirim payload ke API Laravel untuk membuat log tugas harian secara otomatis.
2.	Task Manual (Ad-Hoc / Non-Sistem):
o	Staf menginput pekerjaan luar sistem (misal: Survey Lapangan, Rapat, Mediasi) dengan mengisi form sederhana (Judul, Deskripsi, Jam Mulai - Selesai/Durasi).
3.	Mekanisme Durasi & Penanganan Kasus Khusus (Flagging):
o	Pengajuan Normal: Menggunakan kalkulasi Benchmark / SLA Standard (misal: 15–20 menit/berkas).
o	Pengajuan Kompleks / Bermasalah: Staf dapat mengaktifkan flag [Kompleks / Investigasi Extra] + mencantumkan Catatan Kendala. Sistem akan menyesuaikan batas waktu/alokasi durasi agar penilaian kinerja tetap adil.
5. RANCANGAN DATABASE (MYSQL SCHEMA)
SQL
-- 1. Tabel Users & Role
CREATE TABLE `users` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) UNIQUE NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('owner', 'hrd', 'ba', 'developer', 'staff') NOT NULL,
  `department` VARCHAR(100) NOT NULL, -- e.g., 'IT', 'Kredit', 'Operasional'
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL
);

-- 2. Tabel Utama Task Management
CREATE TABLE `tasks` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `task_type` ENUM('it_dev', 'ksp_auto', 'ksp_manual') NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL, -- Pengerja/Assignee
  `status` ENUM('new_request', 'in_progress', 'qa_testing', 'waiting_approval', 'approved', 'deployed', 'completed') DEFAULT 'new_request',
  
  -- Integrasi KSP Operasional
  `loan_reference_no` VARCHAR(100) NULL, -- Contoh: LOAN-2026-001
  `complexity_flag` ENUM('normal', 'complex') DEFAULT 'normal',
  `complexity_note` TEXT NULL,
  
  -- Integrasi IT Workflow
  `ba_id` BIGINT UNSIGNED NULL, -- BA Penanggung Jawab
  `test_case_narration` TEXT NULL,
  `repository_url` VARCHAR(255) NULL,
  `is_test_passed_100` BOOLEAN DEFAULT FALSE,
  
  -- Tracking Waktu
  `estimated_duration_minutes` INT DEFAULT 0,
  `actual_duration_minutes` INT DEFAULT 0,
  `started_at` TIMESTAMP NULL,
  `completed_at` TIMESTAMP NULL,
  
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`ba_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

-- 3. Tabel Log Activity / Audit Trail (Optional)
CREATE TABLE `task_logs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `task_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `action` VARCHAR(255) NOT NULL, -- e.g., 'Status changed to Approved', 'Auto-created by System KSP'
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`task_id`) REFERENCES `tasks`(`id`) ON DELETE CASCADE
);
6. INTEGRASI API (UNTUK SISTEM KSP UTAMA)
Aplikasi Laravel ini harus menyediakan Endpoint API khusus agar sistem KSP bisa mengirimkan task otomatis secara instan:
•	Endpoint: POST /api/v1/tasks/auto-log
•	Headers: Authorization: Bearer <API_TOKEN_KSP>
•	Payload Request (JSON):
JSON
{
  "user_email": "siti.kredit@koperasi.com",
  "loan_reference_no": "LOAN-2026-0089",
  "action_title": "Validasi Berkas Agunan",
  "description": "Validasi dokumen sertifikat tanah atas nama Budi Santoso",
  "is_complex": false,
  "complexity_note": ""
}
7. RANCANGAN TAMPILAN (DASHBOARD & MONITORING)
1.	View Staf Operasional: Tampilan simpel berisi 2 bagian utama:
o	Aktivitas Otomatis Hari Ini (Data yang terisi otomatis saat staf bekerja di sistem KSP).
o	Form Input Tugas Manual (Untuk kegiatan di luar komputer/sistem).
2.	View IT Board: Kanban board/Tabel ringkas dengan 5 tahapan khusus (New Request $\rightarrow$ In Dev $\rightarrow$ Testing $\rightarrow$ Waiting BA Approval $\rightarrow$ Deployed).
3.	View HRD & Owner (Executive Board):
o	Rekapitulasi persentase keterisian laporan harian.
o	Ringkasan rasio penanganan kasus Normal vs Kompleks.
o	Daftar fitur IT yang baru saja di-deploy ke Production.

