@extends('internal.layouts.app')

@section('title', 'Command Center')

@section('content')
<div class="p-6 max-w-400 mx-auto space-y-6"
     x-data="commandCenter(@js([
        'summary'   => $summary,
        'cameras'   => $cameras,
        'alerts'    => $alerts,
        'incidents' => $incidents,
        'offices'   => $offices,
        'canManage' => $canManage,
        'dataUrl'   => route('internal.command-center.data'),
        'updateUrlBase' => url('internal/command-center/incidents'),
     ]))"
     x-init="init()">

    {{-- ── Header ──────────────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-white flex items-center gap-2">
                    <i class="ti ti-command text-gold-500"></i> Command Center
                </h1>
                @if ($isScoped)
                <span class="badge-info">My Office Only</span>
                @endif
            </div>
            <p class="text-slate-400 text-sm mt-0.5">
                Real-time operational monitoring — Immigration Division 5
            </p>
        </div>
        <div class="flex items-center gap-3 text-xs text-slate-400">
            <span class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                Live — refreshes every 30s
            </span>
            <span x-text="'Updated ' + lastUpdatedLabel"></span>
            <button @click="refresh()" type="button" class="btn-portal-primary text-xs py-1.5 px-3">
                <i class="ti ti-refresh" :class="{ 'animate-spin': loading }"></i> Refresh
            </button>
        </div>
    </div>

    {{-- ── Top Summary Bar ─────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <template x-for="card in summaryCards" :key="card.key">
            <div class="portal-card p-4 flex flex-col gap-2">
                <div class="flex items-center justify-between">
                    <span class="w-8 h-8 rounded-lg flex items-center justify-center" :class="card.iconBg">
                        <i :class="['ti', card.icon, card.iconColor]"></i>
                    </span>
                    <span class="w-1.5 h-1.5 rounded-full" :class="card.dot"></span>
                </div>
                <span class="text-2xl font-bold text-white tabular-nums" x-text="card.value"></span>
                <span class="text-[11px] text-slate-400 uppercase tracking-wider" x-text="card.label"></span>
            </div>
        </template>
    </div>

    {{-- ── Main Grid: 70 / 30 ──────────────────────────────────────── --}}
    <div class="grid lg:grid-cols-10 gap-6">

        {{-- ══════════════ LEFT COLUMN (70%) ══════════════ --}}
        <div class="lg:col-span-7 space-y-6">

            {{-- Section 1: Live Camera Grid --}}
            <div class="portal-card p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-white flex items-center gap-2">
                        <i class="ti ti-video text-gold-500"></i> Live Camera Grid
                    </h2>
                    <span class="text-xs text-slate-400" x-text="filteredCameras.length + ' of ' + cameras.length + ' cameras'"></span>
                </div>

                {{-- Search + filters --}}
                <div class="flex flex-wrap gap-2 mb-4">
                    <div class="relative flex-1 min-w-40">
                        <i class="ti ti-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm"></i>
                        <input type="text" x-model="cameraSearch" placeholder="Search camera or office..."
                               class="portal-input text-xs py-2 pl-8 w-full">
                    </div>
                    <select x-model="cameraOfficeFilter" class="portal-input text-xs py-2 w-auto">
                        <option value="">All offices</option>
                        <template x-for="office in cameraOfficeOptions" :key="office">
                            <option :value="office" x-text="office"></option>
                        </template>
                    </select>
                    <select x-model="cameraStatusFilter" class="portal-input text-xs py-2 w-auto">
                        <option value="">All statuses</option>
                        <option value="online">Online</option>
                        <option value="offline">Offline</option>
                        <option value="warning">Warning</option>
                    </select>
                </div>

                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <template x-for="camera in filteredCameras" :key="camera.id">
                        <div class="rounded-xl border overflow-hidden"
                             :class="camera.live_status === 'online' ? 'border-emerald-500/30' : (camera.live_status === 'warning' ? 'border-amber-500/30' : 'border-red-500/30')">
                            <button type="button" class="aspect-video bg-black/40 flex items-center justify-center relative w-full text-left"
                                    @click="openFullscreen(camera)">
                                {{-- A verified RTSP/ONVIF source exists and has been marked ready --}}
                                <template x-if="camera.is_integration_ready && camera.live_status === 'online' && camera.live_stream_url">
                                    <video :src="camera.live_stream_url" class="w-full h-full object-cover" autoplay muted loop playsinline></video>
                                </template>
                                {{-- No verified live source yet, but a snapshot URL exists --}}
                                <template x-if="!(camera.is_integration_ready && camera.live_status === 'online' && camera.live_stream_url) && camera.snapshot_url">
                                    <img :src="camera.snapshot_url" :alt="camera.name + ' snapshot'" class="w-full h-full object-cover opacity-80">
                                </template>
                                {{-- Nothing to show -- say so plainly instead of a silently-broken player --}}
                                <template x-if="!(camera.is_integration_ready && camera.live_status === 'online' && camera.live_stream_url) && !camera.snapshot_url">
                                    <div class="flex flex-col items-center gap-1.5 px-4 text-center">
                                        <i :class="['ti', camera.live_status === 'offline' ? 'ti-camera-off' : 'ti-video-off', 'text-2xl text-slate-600']"></i>
                                        <span class="text-[10px] text-slate-500 leading-tight" x-text="camera.live_status === 'offline' ? 'Camera offline' : 'Streaming unavailable — pending network/credential setup'"></span>
                                    </div>
                                </template>

                                <span x-show="camera.is_integration_ready && camera.live_status === 'online' && camera.live_stream_url"
                                      class="absolute top-2 left-2 flex items-center gap-1 bg-red-600 text-white text-[10px] font-bold px-1.5 py-0.5 rounded">
                                    <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span> LIVE
                                </span>
                                <span class="absolute top-2 right-2 text-[10px] font-semibold px-1.5 py-0.5 rounded uppercase"
                                      :class="camera.live_status === 'online' ? 'bg-emerald-500/20 text-emerald-300' : (camera.live_status === 'warning' ? 'bg-amber-500/20 text-amber-300' : 'bg-red-500/20 text-red-300')"
                                      x-text="camera.live_status"></span>
                                <span class="absolute bottom-2 right-2 text-slate-400">
                                    <i class="ti ti-maximize text-sm"></i>
                                </span>
                            </button>
                            <div class="p-3">
                                <p class="text-sm font-medium text-white truncate" x-text="camera.name"></p>
                                <p class="text-xs text-slate-400 truncate" x-text="camera.location"></p>
                                <p class="text-[11px] text-slate-500 mt-1" x-text="'Last heartbeat: ' + formatRelative(camera.last_seen)"></p>
                            </div>
                        </div>
                    </template>
                    <p x-show="cameras.length > 0 && filteredCameras.length === 0" class="text-sm text-slate-500 col-span-full text-center py-8">No cameras match your search/filters.</p>
                    <p x-show="cameras.length === 0" class="text-sm text-slate-500 col-span-full text-center py-8">No cameras configured.</p>
                </div>
            </div>

            {{-- Section 2: Thailand Operations Map --}}
            <div class="portal-card p-5">
                <h2 class="text-sm font-semibold text-white flex items-center gap-2 mb-4">
                    <i class="ti ti-map-2 text-gold-500"></i> Thailand Operations Map
                </h2>
                @include('internal.command-center.partials.ops-map', ['offices' => $offices])
            </div>

            {{-- Section 3: Active Incidents Panel --}}
            <div class="portal-card overflow-hidden">
                <div class="p-5 pb-0 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-white flex items-center gap-2">
                        <i class="ti ti-alert-octagon text-gold-500"></i> Active Incidents
                    </h2>
                    <span class="text-xs text-slate-400" x-text="incidents.length + ' total'"></span>
                </div>
                <div class="overflow-x-auto mt-4">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-white/10 text-left text-slate-400 text-xs uppercase tracking-wider">
                                <th class="px-5 py-3">ID</th>
                                <th class="px-5 py-3">Type</th>
                                <th class="px-5 py-3">Location</th>
                                <th class="px-5 py-3">Priority</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3">Created</th>
                                <th class="px-5 py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="incident in incidents" :key="incident.id">
                                <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                                    <td class="px-5 py-3 text-slate-300 font-mono text-xs" x-text="incident.incident_code"></td>
                                    <td class="px-5 py-3 text-slate-200" x-text="incident.type"></td>
                                    <td class="px-5 py-3 text-slate-400" x-text="incident.location || '—'"></td>
                                    <td class="px-5 py-3">
                                        <span class="badge"
                                              :class="{
                                                'badge-neutral': incident.priority === 'low',
                                                'badge-info': incident.priority === 'medium',
                                                'badge-warning': incident.priority === 'high',
                                                'badge-danger': incident.priority === 'critical',
                                              }"
                                              x-text="incident.priority"></span>
                                    </td>
                                    <td class="px-5 py-3">
                                        <template x-if="canManage">
                                            <select class="portal-input text-xs py-1 px-2 w-auto"
                                                    :value="incident.status"
                                                    @change="updateIncidentStatus(incident, $event.target.value)">
                                                <option value="open">Open</option>
                                                <option value="investigating">Investigating</option>
                                                <option value="resolved">Resolved</option>
                                            </select>
                                        </template>
                                        <template x-if="!canManage">
                                            <span class="badge"
                                                  :class="{
                                                    'badge-danger': incident.status === 'open',
                                                    'badge-warning': incident.status === 'investigating',
                                                    'badge-success': incident.status === 'resolved',
                                                  }"
                                                  x-text="incident.status"></span>
                                        </template>
                                    </td>
                                    <td class="px-5 py-3 text-slate-500 text-xs" x-text="formatDate(incident.created_at)"></td>
                                    <td class="px-5 py-3 text-right">
                                        <button type="button" class="text-brand-400 hover:text-brand-300 text-xs font-medium"
                                                @click="selectedIncident = incident">View</button>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="incidents.length === 0">
                                <td colspan="7" class="px-5 py-10 text-center text-slate-500">No active incidents.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ══════════════ RIGHT COLUMN (30%) ══════════════ --}}
        <div class="lg:col-span-3 space-y-6">

            {{-- Section 1: Live Alert Feed --}}
            <div class="portal-card p-5">
                <h2 class="text-sm font-semibold text-white flex items-center gap-2 mb-4">
                    <i class="ti ti-bell-ringing text-gold-500"></i> Live Alert Feed
                </h2>
                <div class="space-y-2.5 max-h-105 overflow-y-auto pr-1">
                    <template x-for="alert in alerts" :key="alert.id">
                        <div class="rounded-xl border p-3"
                             :class="alert.severity === 'critical' ? 'border-red-500/30 bg-red-500/5' : (alert.severity === 'warning' ? 'border-amber-500/30 bg-amber-500/5' : 'border-white/10 bg-white/5')">
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-sm font-medium text-white" x-text="alert.title"></p>
                                <span class="badge shrink-0"
                                      :class="{
                                        'badge-neutral': alert.severity === 'info',
                                        'badge-warning': alert.severity === 'warning',
                                        'badge-danger': alert.severity === 'critical',
                                      }"
                                      x-text="alert.severity"></span>
                            </div>
                            <p class="text-xs text-slate-400 mt-1 capitalize" x-text="alert.type + (alert.location ? ' — ' + alert.location : '')"></p>
                            <p class="text-[11px] text-slate-500 mt-1" x-text="formatRelative(alert.created_at)"></p>
                        </div>
                    </template>
                    <p x-show="alerts.length === 0" class="text-sm text-slate-500 text-center py-8">No alerts.</p>
                </div>
            </div>

            {{-- Section 2: Office Status --}}
            <div class="portal-card p-5">
                <h2 class="text-sm font-semibold text-white flex items-center gap-2 mb-4">
                    <i class="ti ti-building-fortress text-gold-500"></i> Office Status
                </h2>
                <div class="space-y-2 max-h-90 overflow-y-auto pr-1">
                    <template x-for="office in offices" :key="office.id">
                        <div class="flex items-center justify-between rounded-xl border border-white/10 px-3 py-2.5">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span class="w-2 h-2 rounded-full shrink-0"
                                      :class="office.ops_status === 'incident' ? 'bg-red-500' : (office.ops_status === 'warning' ? 'bg-amber-500' : 'bg-emerald-500')"></span>
                                <span class="text-sm text-slate-200 truncate" x-text="office.name_en"></span>
                            </div>
                            <div class="flex items-center gap-3 text-[11px] text-slate-400 shrink-0">
                                <span class="flex items-center gap-1"><i class="ti ti-users text-[13px]"></i><span x-text="office.active_officers"></span></span>
                                <span class="flex items-center gap-1"><i class="ti ti-video text-[13px]"></i><span x-text="office.online_cameras"></span></span>
                                <span class="flex items-center gap-1"><i class="ti ti-alert-triangle text-[13px]"></i><span x-text="office.active_incidents"></span></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Section 3: Weather & Emergency Panel --}}
            <div class="portal-card p-5">
                <h2 class="text-sm font-semibold text-white flex items-center gap-2 mb-4">
                    <i class="ti ti-cloud-storm text-gold-500"></i> Weather &amp; Emergency
                </h2>

                <div class="flex items-center justify-between rounded-xl border border-white/10 px-3 py-3 mb-3">
                    <div>
                        <p class="text-xs text-slate-400">Weather feed</p>
                        <p class="text-sm text-slate-300 mt-0.5">Not yet connected</p>
                    </div>
                    <i class="ti ti-cloud-off text-2xl text-slate-500"></i>
                </div>
                <p class="text-[11px] text-slate-500 mb-4">
                    Architecture supports plugging in a weather/disaster API (e.g. TMD, OpenWeather) later — no UI changes required.
                </p>

                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Critical Emergency Notices</p>
                <div class="space-y-2">
                    <template x-for="alert in criticalAlerts" :key="'crit-' + alert.id">
                        <div class="flex items-start gap-2 text-xs text-red-300 bg-red-500/10 border border-red-500/20 rounded-lg px-3 py-2">
                            <i class="ti ti-alert-triangle shrink-0 mt-0.5"></i>
                            <span x-text="alert.title"></span>
                        </div>
                    </template>
                    <p x-show="criticalAlerts.length === 0" class="text-xs text-slate-500">No active critical notices.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Camera Fullscreen Modal ─────────────────────────────────── --}}
    <div x-show="fullscreenCamera" x-cloak
         class="fixed inset-0 bg-black/85 backdrop-blur-sm z-50 flex items-center justify-center p-4"
         @click.self="fullscreenCamera = null" @keydown.escape.window="fullscreenCamera = null">
        <template x-if="fullscreenCamera">
            <div class="w-full max-w-4xl" @click.stop x-transition>
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <p class="text-white font-semibold" x-text="fullscreenCamera.name"></p>
                        <p class="text-slate-400 text-xs" x-text="fullscreenCamera.location"></p>
                    </div>
                    <button @click="fullscreenCamera = null" class="text-slate-400 hover:text-white">
                        <i class="ti ti-x text-xl"></i>
                    </button>
                </div>
                <div class="aspect-video bg-black rounded-xl overflow-hidden flex items-center justify-center relative border border-white/10">
                    <template x-if="fullscreenCamera.is_integration_ready && fullscreenCamera.live_status === 'online' && fullscreenCamera.live_stream_url">
                        <video :src="fullscreenCamera.live_stream_url" class="w-full h-full object-contain" autoplay muted controls playsinline></video>
                    </template>
                    <template x-if="!(fullscreenCamera.is_integration_ready && fullscreenCamera.live_status === 'online' && fullscreenCamera.live_stream_url) && fullscreenCamera.snapshot_url">
                        <img :src="fullscreenCamera.snapshot_url" :alt="fullscreenCamera.name + ' snapshot'" class="w-full h-full object-contain">
                    </template>
                    <template x-if="!(fullscreenCamera.is_integration_ready && fullscreenCamera.live_status === 'online' && fullscreenCamera.live_stream_url) && !fullscreenCamera.snapshot_url">
                        <div class="flex flex-col items-center gap-2 px-6 text-center">
                            <i class="ti ti-video-off text-4xl text-slate-600"></i>
                            <p class="text-sm text-slate-400" x-text="fullscreenCamera.live_status === 'offline' ? 'This camera is currently offline.' : 'Live streaming is not yet available for this camera — network and credential setup is pending.'"></p>
                        </div>
                    </template>
                </div>
                <div class="mt-3 flex items-center justify-between text-xs text-slate-500">
                    <span x-text="'Last heartbeat: ' + formatRelative(fullscreenCamera.last_seen)"></span>
                    <span class="italic">Recording playback: planned, not yet available</span>
                </div>
            </div>
        </template>
    </div>

    {{-- ── Incident Detail Modal ───────────────────────────────────── --}}
    <div x-show="selectedIncident" x-cloak
         class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4"
         @click.self="selectedIncident = null">
        <div class="portal-card p-6 max-w-md w-full" @click.stop x-show="selectedIncident" x-transition>
            <template x-if="selectedIncident">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-white" x-text="selectedIncident.incident_code"></h3>
                        <button @click="selectedIncident = null" class="text-slate-400 hover:text-white">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <dl class="space-y-2.5 text-sm">
                        <div class="flex justify-between"><dt class="text-slate-400">Type</dt><dd class="text-white" x-text="selectedIncident.type"></dd></div>
                        <div class="flex justify-between"><dt class="text-slate-400">Priority</dt><dd class="text-white capitalize" x-text="selectedIncident.priority"></dd></div>
                        <div class="flex justify-between"><dt class="text-slate-400">Status</dt><dd class="text-white capitalize" x-text="selectedIncident.status"></dd></div>
                        <div class="flex justify-between"><dt class="text-slate-400">Location</dt><dd class="text-white" x-text="selectedIncident.location || '—'"></dd></div>
                        <div class="flex justify-between"><dt class="text-slate-400">Reported</dt><dd class="text-white" x-text="formatDate(selectedIncident.created_at)"></dd></div>
                        <div class="pt-2 border-t border-white/10">
                            <dt class="text-slate-400 mb-1">Description</dt>
                            <dd class="text-slate-300" x-text="selectedIncident.description"></dd>
                        </div>
                    </dl>
                </div>
            </template>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('commandCenter', (initial) => ({
        summary: initial.summary,
        cameras: initial.cameras,
        alerts: initial.alerts,
        incidents: initial.incidents,
        offices: initial.offices,
        canManage: initial.canManage,
        dataUrl: initial.dataUrl,
        updateUrlBase: initial.updateUrlBase,
        loading: false,
        lastUpdated: new Date(),
        selectedIncident: null,
        pollTimer: null,
        cameraSearch: '',
        cameraOfficeFilter: '',
        cameraStatusFilter: '',
        fullscreenCamera: null,

        init() {
            this.pollTimer = setInterval(() => this.refresh(), 30000);
        },

        get cameraOfficeOptions() {
            return [...new Set(this.cameras.map(c => c.location).filter(Boolean))].sort();
        },

        get filteredCameras() {
            const term = this.cameraSearch.trim().toLowerCase();
            return this.cameras.filter(c => {
                if (this.cameraOfficeFilter && c.location !== this.cameraOfficeFilter) return false;
                if (this.cameraStatusFilter && c.live_status !== this.cameraStatusFilter) return false;
                if (term && !(c.name.toLowerCase().includes(term) || (c.location || '').toLowerCase().includes(term))) return false;
                return true;
            });
        },

        openFullscreen(camera) {
            this.fullscreenCamera = camera;
        },

        get lastUpdatedLabel() {
            return this.formatRelative(this.lastUpdated.toISOString());
        },

        get criticalAlerts() {
            return this.alerts.filter(a => a.severity === 'critical' && a.status !== 'resolved');
        },

        get summaryCards() {
            const s = this.summary;
            return [
                { key: 'cameras_active',  label: 'Active Cameras',      value: s.cameras_active.value,  icon: 'ti-video',           iconBg: 'bg-emerald-500/20', iconColor: 'text-emerald-400', dot: 'bg-emerald-400' },
                { key: 'cameras_offline', label: 'Cameras Offline',     value: s.cameras_offline.value, icon: 'ti-camera-off',      iconBg: 'bg-red-500/20',     iconColor: 'text-red-400',     dot: s.cameras_offline.value > 0 ? 'bg-red-400' : 'bg-slate-500' },
                { key: 'active_alerts',   label: 'Active Alerts',       value: s.active_alerts.value,   icon: 'ti-bell-ringing',    iconBg: 'bg-amber-500/20',   iconColor: 'text-amber-400',   dot: s.active_alerts.value > 0 ? 'bg-amber-400' : 'bg-slate-500' },
                { key: 'offices_online',  label: 'Offices Online',      value: s.offices_online.value,  icon: 'ti-building-fortress', iconBg: 'bg-brand-500/20', iconColor: 'text-brand-400', dot: 'bg-emerald-400' },
                { key: 'officers_online', label: 'Officers Logged In',  value: s.officers_online.value, icon: 'ti-users',           iconBg: 'bg-brand-500/20',   iconColor: 'text-brand-400',   dot: 'bg-emerald-400' },
                { key: 'open_incidents',  label: 'Open Incidents',      value: s.open_incidents.value,  icon: 'ti-alert-octagon',   iconBg: 'bg-red-500/20',     iconColor: 'text-red-400',     dot: s.open_incidents.value > 0 ? 'bg-red-400' : 'bg-slate-500' },
            ];
        },

        async refresh() {
            this.loading = true;
            try {
                const res = await fetch(this.dataUrl, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) throw new Error('Request failed: ' + res.status);
                const data = await res.json();
                this.summary = data.summary;
                this.cameras = data.cameras;
                this.alerts = data.alerts;
                this.incidents = data.incidents;
                this.offices = data.offices;
                this.lastUpdated = new Date();

                if (this.fullscreenCamera) {
                    const refreshed = this.cameras.find(c => c.id === this.fullscreenCamera.id);
                    if (refreshed) this.fullscreenCamera = refreshed;
                }
            } catch (e) {
                console.error('Command Center refresh failed', e);
            } finally {
                this.loading = false;
            }
        },

        async updateIncidentStatus(incident, status) {
            try {
                const res = await fetch(`${this.updateUrlBase}/${incident.id}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ status }),
                });
                if (!res.ok) throw new Error('Update failed: ' + res.status);
                const data = await res.json();
                const idx = this.incidents.findIndex(i => i.id === incident.id);
                if (idx !== -1) this.incidents[idx] = data.incident;
            } catch (e) {
                console.error('Failed to update incident', e);
                alert('Could not update incident status.');
            }
        },

        formatRelative(iso) {
            if (!iso) return '—';
            const diffMs = Date.now() - new Date(iso).getTime();
            const mins = Math.round(diffMs / 60000);
            if (mins < 1) return 'just now';
            if (mins < 60) return mins + 'm ago';
            const hrs = Math.round(mins / 60);
            if (hrs < 24) return hrs + 'h ago';
            return Math.round(hrs / 24) + 'd ago';
        },

        formatDate(iso) {
            if (!iso) return '—';
            const d = new Date(iso);
            return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' }) + ' ' +
                   d.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
        },
    }));
});
</script>
@endpush
