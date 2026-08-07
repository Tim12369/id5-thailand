@extends('internal.layouts.app')

@section('title', 'Edit Dashboard Data — ' . $period->label())

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-bold text-white">Edit Dashboard Data</h2>
        <p class="text-sm text-slate-400 mt-0.5">
            Period: <span class="text-gold-500 font-medium">{{ $period->label() }}</span>
            ({{ $period->period_date->format('d/m/Y') }})
        </p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('internal.executive.index', ['period_id' => $period->id]) }}"
           class="btn-secondary text-sm flex items-center gap-1.5">
            <i class="ti ti-eye"></i> Preview
        </a>
        <a href="{{ route('internal.executive.admin.index') }}"
           class="btn-secondary text-sm flex items-center gap-1.5">
            <i class="ti ti-arrow-left"></i> Back
        </a>
    </div>
</div>

{{-- ═══ SECTION 1: KPIs ════════════════════════════════════════════ --}}
<div class="portal-card mb-5" x-data="{ open: true }">
    <button type="button" @click="open = !open"
            class="w-full flex items-center justify-between px-5 py-4 border-b border-white/10 hover:bg-white/3 transition-colors">
        <h3 class="text-sm font-semibold text-white flex items-center gap-2">
            <i class="ti ti-chart-bar text-brand-400"></i> 1. Key Performance Indicators (KPIs)
        </h3>
        <i class="ti ti-chevron-down text-slate-400 transition-transform" :class="{ 'rotate-180': open }"></i>
    </button>
    <div x-show="open" x-transition>
        <form method="POST" action="{{ route('internal.executive.admin.update-kpis', $period) }}" class="p-5">
            @csrf @method('PATCH')
            @php
                $kpiMeta = [
                    'total_foreigners'     => ['ไทย' => 'จำนวนคนต่างด้าวทั้งหมด', 'en' => 'Total Foreigners', 'icon' => 'ti-users'],
                    'overstay_count'       => ['ไทย' => 'Overstay', 'en' => 'Overstay Cases', 'icon' => 'ti-alert-triangle'],
                    'tm30_compliance_rate' => ['ไทย' => 'TM30 Compliance (%)', 'en' => 'TM30 Compliance Rate', 'icon' => 'ti-circle-check'],
                    'risk_alert_count'     => ['ไทย' => 'Risk / Alert', 'en' => 'Risk Alert Count', 'icon' => 'ti-shield-exclamation'],
                ];
            @endphp
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                @foreach($kpiMeta as $key => $meta)
                    @php $kpi = $period->kpis->firstWhere('metric_key', $key); @endphp
                    <div class="rounded-xl bg-white/5 border border-white/10 p-4 space-y-3">
                        <div class="flex items-center gap-2">
                            <i class="ti {{ $meta['icon'] }} text-brand-400"></i>
                            <span class="text-sm font-medium text-slate-200">{{ $meta['ไทย'] }}</span>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="portal-label text-[11px]">ค่าปัจจุบัน *</label>
                                <input type="number" name="kpis[{{ $key }}][value]" step="0.01" min="0"
                                       value="{{ old("kpis.{$key}.value", $kpi?->value ?? 0) }}"
                                       class="portal-input" required>
                                @error("kpis.{$key}.value") <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="portal-label text-[11px]">ค่าก่อนหน้า (สำหรับ %)</label>
                                <input type="number" name="kpis[{{ $key }}][previous_value]" step="0.01" min="0"
                                       value="{{ old("kpis.{$key}.previous_value", $kpi?->previous_value) }}"
                                       class="portal-input" placeholder="ว่าง = ไม่แสดง %">
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-4">
                <button type="submit" class="btn-portal-primary text-sm">
                    <i class="ti ti-check"></i> Save KPIs
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ═══ SECTION 2: Nationality Stats ════════════════════════════════ --}}
<div class="portal-card mb-5" x-data="{ open: false }">
    <button type="button" @click="open = !open"
            class="w-full flex items-center justify-between px-5 py-4 border-b border-white/10 hover:bg-white/3 transition-colors">
        <h3 class="text-sm font-semibold text-white flex items-center gap-2">
            <i class="ti ti-flag text-gold-500"></i> 2. Nationality Statistics (Top 10)
        </h3>
        <i class="ti ti-chevron-down text-slate-400 transition-transform" :class="{ 'rotate-180': open }"></i>
    </button>
    <div x-show="open" x-transition>
        <form method="POST" action="{{ route('internal.executive.admin.update-nationalities', $period) }}" class="p-5"
              x-data="nationalitiesForm({{ json_encode($period->nationalityStats->toArray()) }})">
            @csrf @method('PATCH')
            <div class="space-y-3" id="nationalities-list">
                <template x-for="(row, idx) in rows" :key="idx">
                    <div class="grid grid-cols-12 gap-2 items-start">
                        <div class="col-span-1 flex items-center justify-center pt-7">
                            <span class="text-xs text-slate-500 tabular-nums" x-text="idx + 1"></span>
                        </div>
                        <div class="col-span-2">
                            <label class="portal-label text-[11px]">Code</label>
                            <input type="text" :name="`nationalities[${idx}][nationality_code]`"
                                   x-model="row.nationality_code" class="portal-input" placeholder="CN" maxlength="10" required>
                        </div>
                        <div class="col-span-3">
                            <label class="portal-label text-[11px]">ชื่อ (ไทย) *</label>
                            <input type="text" :name="`nationalities[${idx}][nationality_name_th]`"
                                   x-model="row.nationality_name_th" class="portal-input" placeholder="จีน" required>
                        </div>
                        <div class="col-span-3">
                            <label class="portal-label text-[11px]">Name (EN) *</label>
                            <input type="text" :name="`nationalities[${idx}][nationality_name_en]`"
                                   x-model="row.nationality_name_en" class="portal-input" placeholder="China" required>
                        </div>
                        <div class="col-span-2">
                            <label class="portal-label text-[11px]">Count *</label>
                            <input type="number" :name="`nationalities[${idx}][count]`"
                                   x-model="row.count" class="portal-input" min="0" required>
                        </div>
                        <div class="col-span-1 flex items-end pb-1">
                            <button type="button" @click="rows.splice(idx, 1)"
                                    class="w-8 h-9 flex items-center justify-center text-red-400 hover:text-red-300">
                                <i class="ti ti-trash text-sm"></i>
                            </button>
                        </div>
                        {{-- Hidden percentage (auto-calculated server-side) --}}
                        <input type="hidden" :name="`nationalities[${idx}][percentage]`" x-model="row.percentage">
                    </div>
                </template>
            </div>
            <div class="flex items-center gap-3 mt-4">
                <button type="button" @click="addRow()"
                        class="btn-secondary text-xs flex items-center gap-1.5">
                    <i class="ti ti-plus"></i> Add Nationality
                </button>
                <button type="submit" class="btn-portal-primary text-sm">
                    <i class="ti ti-check"></i> Save Nationalities
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ═══ SECTION 3: Category Stats ════════════════════════════════════ --}}
<div class="portal-card mb-5" x-data="{ open: false }">
    <button type="button" @click="open = !open"
            class="w-full flex items-center justify-between px-5 py-4 border-b border-white/10 hover:bg-white/3 transition-colors">
        <h3 class="text-sm font-semibold text-white flex items-center gap-2">
            <i class="ti ti-chart-donut text-emerald-400"></i> 3. Immigration Category Breakdown
        </h3>
        <i class="ti ti-chevron-down text-slate-400 transition-transform" :class="{ 'rotate-180': open }"></i>
    </button>
    <div x-show="open" x-transition>
        <form method="POST" action="{{ route('internal.executive.admin.update-categories', $period) }}" class="p-5"
              x-data="categoriesForm({{ json_encode($period->categoryStats->toArray()) }})">
            @csrf @method('PATCH')
            <div class="space-y-3">
                <template x-for="(row, idx) in rows" :key="idx">
                    <div class="grid grid-cols-12 gap-2 items-start">
                        <div class="col-span-2">
                            <label class="portal-label text-[11px]">Key</label>
                            <input type="text" :name="`categories[${idx}][category]`"
                                   x-model="row.category" class="portal-input" placeholder="tourist" required>
                        </div>
                        <div class="col-span-3">
                            <label class="portal-label text-[11px]">ประเภท (ไทย) *</label>
                            <input type="text" :name="`categories[${idx}][label_th]`"
                                   x-model="row.label_th" class="portal-input" placeholder="นักท่องเที่ยว" required>
                        </div>
                        <div class="col-span-3">
                            <label class="portal-label text-[11px]">Category (EN) *</label>
                            <input type="text" :name="`categories[${idx}][label_en]`"
                                   x-model="row.label_en" class="portal-input" placeholder="Tourist" required>
                        </div>
                        <div class="col-span-2">
                            <label class="portal-label text-[11px]">Count *</label>
                            <input type="number" :name="`categories[${idx}][count]`"
                                   x-model="row.count" class="portal-input" min="0" required>
                        </div>
                        <div class="col-span-1">
                            <label class="portal-label text-[11px]">% *</label>
                            <input type="number" :name="`categories[${idx}][percentage]`"
                                   x-model="row.percentage" class="portal-input" min="0" max="100" step="0.1" required>
                        </div>
                        <div class="col-span-1 flex items-end pb-1">
                            <button type="button" @click="rows.splice(idx, 1)"
                                    class="w-8 h-9 flex items-center justify-center text-red-400 hover:text-red-300">
                                <i class="ti ti-trash text-sm"></i>
                            </button>
                        </div>
                        <input type="hidden" :name="`categories[${idx}][label_zh]`" x-model="row.label_zh || ''">
                    </div>
                </template>
            </div>
            <div class="flex items-center gap-3 mt-4">
                <button type="button" @click="addRow()"
                        class="btn-secondary text-xs flex items-center gap-1.5">
                    <i class="ti ti-plus"></i> Add Category
                </button>
                <button type="submit" class="btn-portal-primary text-sm">
                    <i class="ti ti-check"></i> Save Categories
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ═══ SECTION 4: Monthly Trends ════════════════════════════════════ --}}
<div class="portal-card mb-5" x-data="{ open: false }">
    <button type="button" @click="open = !open"
            class="w-full flex items-center justify-between px-5 py-4 border-b border-white/10 hover:bg-white/3 transition-colors">
        <h3 class="text-sm font-semibold text-white flex items-center gap-2">
            <i class="ti ti-trending-up text-purple-400"></i> 4. Monthly Trends ({{ $period->period_date->year }})
        </h3>
        <i class="ti ti-chevron-down text-slate-400 transition-transform" :class="{ 'rotate-180': open }"></i>
    </button>
    <div x-show="open" x-transition>
        <form method="POST" action="{{ route('internal.executive.admin.update-trends', $period) }}" class="p-5"
              x-data="trendsForm({{ json_encode($trends->toArray()) }}, {{ $period->period_date->year }})">
            @csrf @method('PATCH')
            <div class="overflow-x-auto">
                <table class="w-full text-xs min-w-[800px]">
                    <thead>
                        <tr class="border-b border-white/10">
                            <th class="py-2 px-2 text-left text-slate-400">เดือน</th>
                            <th class="py-2 px-2 text-left text-slate-400">Label TH</th>
                            <th class="py-2 px-2 text-left text-slate-400">เข้า</th>
                            <th class="py-2 px-2 text-left text-slate-400">ออก</th>
                            <th class="py-2 px-2 text-left text-slate-400">Overstay</th>
                            <th class="py-2 px-2 text-left text-slate-400">TM30</th>
                            <th class="py-2 px-2 text-left text-slate-400">TM30 ผ่าน</th>
                            <th class="py-2 px-2 text-left text-slate-400">Risk</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, idx) in rows" :key="idx">
                            <tr class="border-b border-white/5">
                                <td class="px-2 py-1.5">
                                    <input type="hidden" :name="`trends[${idx}][year]`" x-model="row.year">
                                    <input type="hidden" :name="`trends[${idx}][month]`" x-model="row.month">
                                    <input type="hidden" :name="`trends[${idx}][month_label_en]`" x-model="row.month_label_en">
                                    <span class="text-slate-300 font-medium" x-text="row.month_label_en"></span>
                                </td>
                                <td class="px-2 py-1.5">
                                    <input type="text" :name="`trends[${idx}][month_label_th]`"
                                           x-model="row.month_label_th" class="portal-input py-1 text-xs w-16" required>
                                </td>
                                <td class="px-2 py-1.5">
                                    <input type="number" :name="`trends[${idx}][total_arrivals]`"
                                           x-model="row.total_arrivals" class="portal-input py-1 text-xs w-24" min="0" required>
                                </td>
                                <td class="px-2 py-1.5">
                                    <input type="number" :name="`trends[${idx}][total_departures]`"
                                           x-model="row.total_departures" class="portal-input py-1 text-xs w-24" min="0" required>
                                </td>
                                <td class="px-2 py-1.5">
                                    <input type="number" :name="`trends[${idx}][overstay_count]`"
                                           x-model="row.overstay_count" class="portal-input py-1 text-xs w-20" min="0" required>
                                </td>
                                <td class="px-2 py-1.5">
                                    <input type="number" :name="`trends[${idx}][tm30_count]`"
                                           x-model="row.tm30_count" class="portal-input py-1 text-xs w-20" min="0" required>
                                </td>
                                <td class="px-2 py-1.5">
                                    <input type="number" :name="`trends[${idx}][tm30_compliant]`"
                                           x-model="row.tm30_compliant" class="portal-input py-1 text-xs w-20" min="0" required>
                                </td>
                                <td class="px-2 py-1.5">
                                    <input type="number" :name="`trends[${idx}][risk_alert_count]`"
                                           x-model="row.risk_alert_count" class="portal-input py-1 text-xs w-20" min="0" required>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn-portal-primary text-sm">
                    <i class="ti ti-check"></i> Save Monthly Trends
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ═══ SECTION 5: Provincial Stats ══════════════════════════════════ --}}
<div class="portal-card mb-5" x-data="{ open: false }">
    <button type="button" @click="open = !open"
            class="w-full flex items-center justify-between px-5 py-4 border-b border-white/10 hover:bg-white/3 transition-colors">
        <h3 class="text-sm font-semibold text-white flex items-center gap-2">
            <i class="ti ti-map-pin text-red-400"></i> 5. Area Responsibility / Provincial Data
        </h3>
        <i class="ti ti-chevron-down text-slate-400 transition-transform" :class="{ 'rotate-180': open }"></i>
    </button>
    <div x-show="open" x-transition>
        <form method="POST" action="{{ route('internal.executive.admin.update-provinces', $period) }}" class="p-5"
              x-data="provincesForm({{ json_encode($period->provincialStats->toArray()) }})">
            @csrf @method('PATCH')
            <div class="overflow-x-auto">
                <table class="w-full text-xs min-w-[900px]">
                    <thead>
                        <tr class="border-b border-white/10">
                            <th class="py-2 px-2 text-left text-slate-400">Code</th>
                            <th class="py-2 px-2 text-left text-slate-400">จังหวัด (ไทย)</th>
                            <th class="py-2 px-2 text-left text-slate-400">Province (EN)</th>
                            <th class="py-2 px-2 text-left text-slate-400">ภาค (TH/EN)</th>
                            <th class="py-2 px-2 text-left text-slate-400">คนต่างด้าว</th>
                            <th class="py-2 px-2 text-left text-slate-400">Overstay</th>
                            <th class="py-2 px-2 text-left text-slate-400">TM30</th>
                            <th class="py-2 px-2 text-left text-slate-400">TM30 %</th>
                            <th class="py-2 px-2 text-left text-slate-400">Risk</th>
                            <th class="py-2 px-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, idx) in rows" :key="idx">
                            <tr class="border-b border-white/5">
                                <td class="px-2 py-1.5">
                                    <input type="text" :name="`provinces[${idx}][province_code]`"
                                           x-model="row.province_code" class="portal-input py-1 text-xs w-14" required>
                                </td>
                                <td class="px-2 py-1.5">
                                    <input type="text" :name="`provinces[${idx}][province_name_th]`"
                                           x-model="row.province_name_th" class="portal-input py-1 text-xs w-28" required>
                                </td>
                                <td class="px-2 py-1.5">
                                    <input type="text" :name="`provinces[${idx}][province_name_en]`"
                                           x-model="row.province_name_en" class="portal-input py-1 text-xs w-28" required>
                                </td>
                                <td class="px-2 py-1.5 space-y-1">
                                    <input type="text" :name="`provinces[${idx}][region_th]`"
                                           x-model="row.region_th" class="portal-input py-1 text-xs w-20" placeholder="ภาค">
                                    <input type="text" :name="`provinces[${idx}][region_en]`"
                                           x-model="row.region_en" class="portal-input py-1 text-xs w-20" placeholder="Region">
                                </td>
                                <td class="px-2 py-1.5">
                                    <input type="number" :name="`provinces[${idx}][total_foreigners]`"
                                           x-model="row.total_foreigners" class="portal-input py-1 text-xs w-20" min="0" required>
                                </td>
                                <td class="px-2 py-1.5">
                                    <input type="number" :name="`provinces[${idx}][overstay_count]`"
                                           x-model="row.overstay_count" class="portal-input py-1 text-xs w-16" min="0" required>
                                </td>
                                <td class="px-2 py-1.5">
                                    <input type="number" :name="`provinces[${idx}][tm30_count]`"
                                           x-model="row.tm30_count" class="portal-input py-1 text-xs w-16" min="0" required>
                                </td>
                                <td class="px-2 py-1.5">
                                    <input type="number" :name="`provinces[${idx}][tm30_compliance_rate]`"
                                           x-model="row.tm30_compliance_rate" class="portal-input py-1 text-xs w-16"
                                           min="0" max="100" step="0.1" required>
                                </td>
                                <td class="px-2 py-1.5">
                                    <select :name="`provinces[${idx}][risk_level]`" x-model="row.risk_level" class="portal-input py-1 text-xs w-24">
                                        <option value="low">Low</option>
                                        <option value="medium">Medium</option>
                                        <option value="high">High</option>
                                        <option value="critical">Critical</option>
                                    </select>
                                </td>
                                <td class="px-2 py-1.5">
                                    <button type="button" @click="rows.splice(idx, 1)"
                                            class="text-red-400 hover:text-red-300">
                                        <i class="ti ti-trash text-sm"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div class="flex items-center gap-3 mt-4">
                <button type="button" @click="addRow()"
                        class="btn-secondary text-xs flex items-center gap-1.5">
                    <i class="ti ti-plus"></i> Add Province
                </button>
                <button type="submit" class="btn-portal-primary text-sm">
                    <i class="ti ti-check"></i> Save Province Data
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function nationalitiesForm(initial) {
    return {
        rows: initial.length ? initial : [{ nationality_code: '', nationality_name_th: '', nationality_name_en: '', nationality_name_zh: '', count: 0, percentage: 0 }],
        addRow() {
            this.rows.push({ nationality_code: '', nationality_name_th: '', nationality_name_en: '', nationality_name_zh: '', count: 0, percentage: 0 });
        }
    };
}

function categoriesForm(initial) {
    const defaults = [
        { category: 'tourist',  label_th: 'นักท่องเที่ยว', label_en: 'Tourist',  label_zh: '旅游', count: 0, percentage: 0 },
        { category: 'student',  label_th: 'นักเรียน',      label_en: 'Student',  label_zh: '学生', count: 0, percentage: 0 },
        { category: 'worker',   label_th: 'แรงงาน',        label_en: 'Worker',   label_zh: '劳工', count: 0, percentage: 0 },
        { category: 'investor', label_th: 'นักลงทุน',      label_en: 'Investor', label_zh: '投资者', count: 0, percentage: 0 },
        { category: 'overstay', label_th: 'อยู่เกินกำหนด', label_en: 'Overstay', label_zh: '逾期居留', count: 0, percentage: 0 },
        { category: 'other',    label_th: 'อื่น ๆ',        label_en: 'Other',    label_zh: '其他', count: 0, percentage: 0 },
    ];
    return {
        rows: initial.length ? initial : defaults,
        addRow() { this.rows.push({ category: '', label_th: '', label_en: '', label_zh: '', count: 0, percentage: 0 }); }
    };
}

function trendsForm(initial, year) {
    const monthsTH = ['', 'ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
    const monthsEN = ['', 'Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

    // Build 12 months, merging with existing data
    const merged = Array.from({ length: 12 }, (_, i) => {
        const m = i + 1;
        const existing = initial.find(r => r.month === m);
        return existing || {
            year, month: m,
            month_label_th: monthsTH[m],
            month_label_en: monthsEN[m],
            total_arrivals: 0, total_departures: 0, overstay_count: 0,
            tm30_count: 0, tm30_compliant: 0, risk_alert_count: 0,
        };
    });

    return { rows: merged };
}

function provincesForm(initial) {
    return {
        rows: initial.length ? initial : [{ province_code: '', province_name_th: '', province_name_en: '', region_th: '', region_en: '', total_foreigners: 0, overstay_count: 0, tm30_count: 0, tm30_compliance_rate: 0, risk_level: 'low' }],
        addRow() {
            this.rows.push({ province_code: '', province_name_th: '', province_name_en: '', region_th: '', region_en: '', total_foreigners: 0, overstay_count: 0, tm30_count: 0, tm30_compliance_rate: 0, risk_level: 'low' });
        }
    };
}
</script>
@endpush
