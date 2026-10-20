<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $dev = User::where('email', 'dev@koperasi.test')->first();
        $ba = User::where('email', 'ba@koperasi.test')->first();
        $siti = User::where('email', 'siti.kredit@koperasi.test')->first();
        $agus = User::where('email', 'agus.operasional@koperasi.test')->first();

        // --- IT Workflow samples (satu di tiap tahap) ---
        Task::create([
            'task_type' => 'it_dev',
            'title' => 'Fitur ekspor laporan simpanan ke Excel',
            'description' => 'Halaman laporan simpanan perlu tombol export XLSX.',
            'user_id' => $dev->id,
            'ba_id' => $ba->id,
            'business_requirement' => 'Admin dapat mengunduh rekap simpanan bulanan dalam format Excel.',
            'test_case_narration' => 'Buka laporan simpanan, pilih periode, klik Export, pastikan file XLSX terunduh dan kolom lengkap.',
            'status' => 'new_request',
        ]);

        Task::create([
            'task_type' => 'it_dev',
            'title' => 'Perbaikan bug saldo tidak sinkron',
            'description' => 'Saldo di dashboard berbeda dengan mutasi terakhir.',
            'user_id' => $dev->id,
            'ba_id' => $ba->id,
            'business_requirement' => 'Saldo dashboard konsisten dengan mutasi terakhir maksimal 1 menit setelah transaksi.',
            'test_case_narration' => 'Lakukan transaksi, cek dashboard dalam 1 menit, pastikan saldo sama dengan mutasi.',
            'status' => 'in_progress',
            'started_at' => now()->subHours(2),
        ]);

        $testing = Task::create([
            'task_type' => 'it_dev',
            'title' => 'Halaman profil anggota',
            'description' => 'Menampilkan data diri anggota koperasi.',
            'user_id' => $dev->id,
            'ba_id' => $ba->id,
            'business_requirement' => 'Anggota dapat melihat dan memperbarui data diri.',
            'test_case_narration' => 'Login sebagai anggota, buka profil, ubah no. HP, simpan, pastikan data tersimpan.',
            'status' => 'qa_testing',
            'started_at' => now()->subDay(),
            'repository_url' => 'https://git.koperasi.test/feature/profil-anggota',
        ]);

        Task::create([
            'task_type' => 'it_dev',
            'title' => 'Notifikasi email jatuh tempo',
            'description' => 'Pengingat otomatis H-3 jatuh tempo angsuran.',
            'user_id' => $dev->id,
            'ba_id' => $ba->id,
            'business_requirement' => 'Sistem mengirim email pengingat 3 hari sebelum jatuh tempo.',
            'test_case_narration' => 'Atur tanggal jatuh tempo H-3, pastikan email terkirim dan isi notifikasi benar.',
            'status' => 'waiting_approval',
            'started_at' => now()->subDays(2),
            'repository_url' => 'https://git.koperasi.test/feature/notifikasi-jatuh-tempo',
            'is_test_passed_100' => true,
        ]);

        Task::create([
            'task_type' => 'it_dev',
            'title' => 'Modul pencatatan kas harian',
            'description' => 'Input pemasukan/pengeluaran kas harian teller.',
            'user_id' => $dev->id,
            'ba_id' => $ba->id,
            'business_requirement' => 'Teller dapat mencatat kas harian dan melihat rekap otomatis.',
            'test_case_narration' => 'Input kas masuk & keluar, cek rekap harian, pastikan total benar.',
            'status' => 'approved',
            'started_at' => now()->subDays(3),
            'completed_at' => now()->subDay(),
            'repository_url' => 'https://git.koperasi.test/feature/kas-harian',
            'is_test_passed_100' => true,
            'actual_duration_minutes' => 1440,
        ]);

        Task::create([
            'task_type' => 'it_dev',
            'title' => 'Perbaikan cetak struk angsuran',
            'description' => 'Format struk tidak rapi pada printer tertentu.',
            'user_id' => $dev->id,
            'ba_id' => $ba->id,
            'business_requirement' => 'Struk angsuran tercetak rapi di semua tipe printer thermal.',
            'test_case_narration' => 'Cetak struk di 3 tipe printer, pastikan layout konsisten.',
            'status' => 'deployed',
            'started_at' => now()->subDays(5),
            'completed_at' => now()->subDays(2),
            'repository_url' => 'https://git.koperasi.test/fix/struk-angsuran',
            'is_test_passed_100' => true,
            'actual_duration_minutes' => 2880,
        ]);

        // --- KSP auto-log samples (staf Kredit) ---
        $autoTasks = [
            ['title' => 'Validasi Berkas Agunan', 'description' => 'Validasi dokumen sertifikat tanah atas nama Budi Santoso', 'loan' => 'LOAN-2026-0089', 'mins' => 18, 'complex' => false],
            ['title' => 'Verifikasi Slip Gaji', 'description' => 'Cek slip gaji 3 bulan terakhir pemohon', 'loan' => 'LOAN-2026-0090', 'mins' => 12, 'complex' => false],
            ['title' => 'Survey Lokasi Agunan', 'description' => 'Investigasi extra: agunan sengketa waris', 'loan' => 'LOAN-2026-0091', 'mins' => 95, 'complex' => true],
        ];

        foreach ($autoTasks as $t) {
            $task = Task::create([
                'task_type' => 'ksp_auto',
                'title' => $t['title'],
                'description' => $t['description'],
                'user_id' => $siti->id,
                'status' => 'completed',
                'loan_reference_no' => $t['loan'],
                'complexity_flag' => $t['complex'] ? 'complex' : 'normal',
                'complexity_note' => $t['complex'] ? 'Berkas waris perlu penelusuran data asset.' : null,
                'estimated_duration_minutes' => $t['complex'] ? 0 : 20,
                'actual_duration_minutes' => $t['mins'],
                'started_at' => now()->subHours(4),
                'completed_at' => now()->subHours(4)->addMinutes($t['mins']),
            ]);
            $task->logs()->create(['user_id' => $siti->id, 'action' => 'Auto-created by System KSP', 'created_at' => now()]);
        }

        // --- KSP manual samples ---
        Task::create([
            'task_type' => 'ksp_manual',
            'title' => 'Survey Lapangan Calon Anggota',
            'description' => 'Kunjungan rumah calon anggota di wilayah Ciputat.',
            'user_id' => $siti->id,
            'status' => 'completed',
            'started_at' => now()->subHours(6),
            'completed_at' => now()->subHours(6)->addMinutes(75),
            'actual_duration_minutes' => 75,
        ]);

        Task::create([
            'task_type' => 'ksp_manual',
            'title' => 'Rapat Koordinasi Penanganan Macet',
            'description' => 'Rapat tim kredit membahas strategi penanganan kredit macet.',
            'user_id' => $agus->id,
            'status' => 'completed',
            'started_at' => now()->subHours(3),
            'completed_at' => now()->subHours(3)->addMinutes(60),
            'actual_duration_minutes' => 60,
        ]);

        Task::create([
            'task_type' => 'ksp_manual',
            'title' => 'Mediasi Anggota Tunggakan',
            'description' => 'Pertemuan dengan anggota terkait tunggakan angsuran.',
            'user_id' => $agus->id,
            'status' => 'in_progress',
            'complexity_flag' => 'complex',
            'complexity_note' => 'Anggota mengajukan restrukturisasi, perlu analisis mendalam.',
            'started_at' => now()->subMinutes(30),
        ]);
    }
}
