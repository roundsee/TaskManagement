@props(['status'])

@php
    $styles = [
        'new_request' => 'bg-gray-100 text-gray-700',
        'in_progress' => 'bg-blue-100 text-blue-700',
        'qa_testing' => 'bg-indigo-100 text-indigo-700',
        'waiting_approval' => 'bg-amber-100 text-amber-800',
        'approved' => 'bg-cyan-100 text-cyan-800',
        'deployed' => 'bg-green-100 text-green-800',
        'completed' => 'bg-green-100 text-green-800',
    ];
    $labels = [
        'new_request' => 'New Request',
        'in_progress' => 'In Dev',
        'qa_testing' => 'Testing',
        'waiting_approval' => 'Waiting Approval',
        'approved' => 'Approved',
        'deployed' => 'Deployed',
        'completed' => 'Selesai',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium '.($styles[$status] ?? 'bg-gray-100 text-gray-700')]) }}>
    {{ $labels[$status] ?? $status }}
</span>
