@extends('internal.layouts.app')

@section('title', 'Contact Information')

@section('content')
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-white">Contact Information</h2>
            <p class="text-sm text-slate-400 mt-1">Office directory shown on the public Contact and Office Locator pages.</p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('internal.website-contact.import') }}" class="btn-secondary">
                <i class="ti ti-upload mr-1"></i> Bulk Import
            </a>
            <a href="{{ route('internal.website-contact.create') }}" class="btn-portal-primary">
                <i class="ti ti-plus mr-1"></i> Add Office
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="badge-success inline-flex mb-4"><i class="ti ti-check mr-1"></i> {{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="rounded-xl border border-red-500/30 bg-red-500/10 text-red-300 text-sm px-4 py-3 mb-4">
            <i class="ti ti-alert-circle mr-1"></i> {{ session('error') }}
        </div>
    @endif

    @if (session('importErrors') && count(session('importErrors')))
        <div class="rounded-xl border border-amber-500/30 bg-amber-500/10 text-amber-300 text-xs px-4 py-3 mb-4 space-y-1">
            <p class="font-semibold">Import notes:</p>
            @foreach (session('importErrors') as $err)
                <p>Row {{ $err['row'] }}: {{ $err['message'] }}</p>
            @endforeach
        </div>
    @endif

    <div x-data="contactSearch({{ Illuminate\Support\Js::from($stations) }})">
        <div class="flex flex-col sm:flex-row gap-3 mb-4">
            <div class="relative flex-1">
                <i class="ti ti-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500"></i>
                <input type="text" x-model="search"
                       placeholder="Search by office name, code, phone, email, province, or region..."
                       class="portal-input w-full pl-10">
            </div>
            <select x-model="region" class="portal-input sm:w-56">
                <option value="">All regions</option>
                <template x-for="r in regions" :key="r">
                    <option :value="r" x-text="r"></option>
                </template>
            </select>
        </div>

        <p class="text-xs text-slate-500 mb-3">
            <span x-text="filtered.length"></span> of {{ $stations->count() }} offices
        </p>

        <div class="portal-card overflow-hidden">
            <div class="divide-y divide-white/5">
                <template x-for="station in filtered" :key="station.id">
                    <div class="flex items-center gap-4 p-4">
                        <span class="shrink-0 text-xs font-bold text-blue-300 bg-blue-500/10 border border-blue-400/20 rounded-lg px-2 py-1" x-text="station.code"></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-slate-100 truncate" x-text="station.name_th"></p>
                            <p class="text-xs text-slate-400 truncate">
                                <span x-text="station.phone"></span>
                                <template x-if="station.email"><span> &middot; <span x-text="station.email"></span></span></template>
                                <template x-if="station.region"><span> &middot; <span x-text="station.region"></span></span></template>
                            </p>
                        </div>

                        <template x-if="station.has_website">
                            <span class="badge-success shrink-0"><i class="ti ti-world mr-1"></i>Website</span>
                        </template>
                        <template x-if="!station.has_website">
                            <span class="badge-danger shrink-0"><i class="ti ti-world-off mr-1"></i>No website</span>
                        </template>

                        <template x-if="!station.has_pin">
                            <span class="badge-neutral shrink-0" title="Address could not be auto-located on the map">
                                <i class="ti ti-map-pin-off mr-1"></i>No map pin
                            </span>
                        </template>

                        <span class="badge-neutral shrink-0" x-show="!station.is_active">Hidden</span>
                        <a :href="station.editUrl" class="btn-secondary btn-sm shrink-0">
                            <i class="ti ti-edit"></i>
                        </a>
                        <button type="button" @click="confirmDelete(station)"
                                class="btn-danger-ghost btn-sm shrink-0"
                                :title="station.canDelete ? 'Delete office' : 'Linked to Command Center data — deletion will be blocked'">
                            <i class="ti ti-trash"></i>
                        </button>
                    </div>
                </template>

                <div class="p-8 text-center text-sm text-slate-400" x-show="filtered.length === 0">
                    No offices match your search.
                </div>
            </div>
        </div>

        {{-- Hidden form reused for whichever row's delete button was clicked --}}
        <form method="POST" x-ref="deleteForm" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    </div>
@endsection

@push('scripts')
<script>
    function contactSearch(stations) {
        return {
            search: '',
            region: '',
            stations,

            get regions() {
                return [...new Set(stations.map((s) => s.region).filter(Boolean))].sort();
            },

            get filtered() {
                let list = this.stations;

                if (this.region) {
                    list = list.filter((s) => s.region === this.region);
                }

                const term = this.search.trim().toLowerCase();
                if (term) {
                    list = list.filter((s) => [s.code, s.name_th, s.name_en, s.phone, s.email, s.province, s.region]
                        .filter(Boolean)
                        .some((field) => field.toLowerCase().includes(term)));
                }

                return list;
            },

            confirmDelete(station) {
                const message = station.canDelete
                    ? `Delete "${station.name_th}"? This cannot be undone.`
                    : `"${station.name_th}" is linked to Command Center data (users, cameras, alerts, or incidents) and can't be deleted — deactivate it instead. Try anyway?`;

                if (!confirm(message)) return;
                this.$refs.deleteForm.action = station.deleteUrl;
                this.$refs.deleteForm.submit();
            },
        };
    }
</script>
@endpush
