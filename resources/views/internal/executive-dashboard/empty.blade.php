@extends('internal.layouts.app')

@section('title', 'Executive Dashboard')

@section('content')
<div class="flex flex-col items-center justify-center py-20 text-center">
    <div class="w-20 h-20 rounded-2xl bg-brand-500/10 flex items-center justify-center mb-5">
        <i class="ti ti-chart-bar text-4xl text-brand-400"></i>
    </div>
    <h2 class="text-xl font-bold text-white mb-2">ยังไม่มีข้อมูล</h2>
    <p class="text-slate-400 text-sm mb-6 max-w-sm">
        ยังไม่มีข้อมูลแดชบอร์ด กรุณาเพิ่มข้อมูลในส่วนจัดการข้อมูลก่อน
    </p>
    <a href="{{ route('internal.executive.admin.create') }}" class="btn-portal-primary">
        <i class="ti ti-plus"></i> เพิ่มข้อมูลแรก
    </a>
</div>
@endsection
