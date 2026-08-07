@extends('internal.layouts.app')

@section('title', 'Executive Dashboard — Data Management')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-bold text-white">Dashboard Data Management</h2>
        <p class="text-sm text-slate-400 mt-0.5">จัดการข้อมูลสำหรับ Executive Intelligence Dashboard</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('internal.executive.index') }}" class="btn-secondary text-sm flex items-center gap-1.5">
            <i class="ti ti-layout-dashboard"></i> View Dashboard
        </a>
        <a href="{{ route('internal.executive.admin.create') }}" class="btn-portal-primary text-sm flex items-center gap-1.5">
            <i class="ti ti-plus"></i> New Period
        </a>
    </div>
</div>

{{-- Periods table --}}
<div class="portal-card mb-6">
    <div class="px-5 py-4 border-b border-white/10">
        <h3 class="text-sm font-semibold text-white">Periods</h3>
    </div>
    @if($periods->isEmpty())
        <div class="py-12 text-center text-slate-500 text-sm">ยังไม่มีข้อมูล — <a href="{{ route('internal.executive.admin.create') }}" class="text-brand-400 hover:underline">สร้างข้อมูลแรก</a></div>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-white/10">
                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-400 uppercase">Period</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-400 uppercase">Type</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-400 uppercase">Label</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-400 uppercase">Status</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-400 uppercase">Created By</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-400 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @foreach($periods as $p)
                <tr class="hover:bg-white/3 transition-colors">
                    <td class="px-5 py-3 text-white font-medium">{{ $p->period_date->format('d/m/Y') }}</td>
                    <td class="px-5 py-3">
                        <span class="badge badge-info capitalize">{{ $p->period_type }}</span>
                    </td>
                    <td class="px-5 py-3 text-slate-300">{{ $p->label() }}</td>
                    <td class="px-5 py-3">
                        @if($p->is_latest)
                            <span class="badge badge-success">Active / Latest</span>
                        @else
                            <span class="badge badge-neutral">Archived</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-slate-400">{{ $p->creator?->name ?? '—' }}</td>
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('internal.executive.admin.edit', $p) }}"
                               class="text-xs text-brand-400 hover:text-brand-300 flex items-center gap-1">
                                <i class="ti ti-edit"></i> Edit Data
                            </a>
                            @if(!$p->is_latest)
                            <form method="POST" action="{{ route('internal.executive.admin.set-latest', $p) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="text-xs text-emerald-400 hover:text-emerald-300">
                                    Set Active
                                </button>
                            </form>
                            <form method="POST" action="{{ route('internal.executive.admin.destroy', $p) }}"
                                  onsubmit="return confirm('Delete this period and all its data?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs text-red-400 hover:text-red-300">Delete</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="px-5 py-3 border-t border-white/10">
        {{ $periods->links() }}
    </div>
    @endif
</div>

{{-- Audit Log --}}
<div class="portal-card">
    <div class="px-5 py-4 border-b border-white/10">
        <h3 class="text-sm font-semibold text-white">Audit Log <span class="text-slate-500 font-normal text-xs">(ล่าสุด 20 รายการ)</span></h3>
    </div>
    @if($auditLogs->isEmpty())
        <p class="py-8 text-center text-slate-500 text-sm">ยังไม่มีประวัติ</p>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-xs">
            <thead>
                <tr class="border-b border-white/10">
                    <th class="px-5 py-2 text-left text-slate-400 uppercase">Time</th>
                    <th class="px-5 py-2 text-left text-slate-400 uppercase">User</th>
                    <th class="px-5 py-2 text-left text-slate-400 uppercase">Action</th>
                    <th class="px-5 py-2 text-left text-slate-400 uppercase">Description</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @foreach($auditLogs as $log)
                <tr>
                    <td class="px-5 py-2 text-slate-500 whitespace-nowrap">{{ $log->created_at->format('d/m H:i') }}</td>
                    <td class="px-5 py-2 text-slate-300">{{ $log->user?->name ?? '—' }}</td>
                    <td class="px-5 py-2"><code class="text-gold-500 text-[10px]">{{ $log->action }}</code></td>
                    <td class="px-5 py-2 text-slate-400">{{ $log->description }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection
