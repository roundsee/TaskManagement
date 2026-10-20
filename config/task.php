<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SLA Benchmark (menit)
    |--------------------------------------------------------------------------
    |
    | Batas waktu standar penyelesaian satu berkas/aktivitas operasional.
    | Tugas dengan complexity_flag "complex" dibebaskan dari benchmark ini.
    |
    */

    'sla_benchmark_minutes' => (int) env('TASK_SLA_BENCHMARK_MINUTES', 20),

    /*
    |--------------------------------------------------------------------------
    | API Token Integrasi Sistem KSP
    |--------------------------------------------------------------------------
    |
    | Token Bearer yang dikirim sistem KSP utama pada endpoint
    | POST /api/v1/tasks/auto-log. Bandingkan secara timing-safe.
    |
    */

    'api_token' => env('KSP_API_TOKEN'),

];
