{{-- Cookie consent banner — PDPA Thailand compliance --}}
<div id="cookie-banner"
     x-data="cookieConsent()"
     x-show="!accepted"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 translate-y-4"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-cloak
     class="fixed bottom-4 left-4 right-4 sm:left-auto sm:right-4 sm:max-w-sm z-50
            bg-white border border-slate-200 rounded-2xl shadow-xl p-4">

    <div class="flex items-start gap-3">
        <i class="ti ti-cookie text-amber-500 text-xl shrink-0 mt-0.5"></i>
        <div class="flex-1 min-w-0">
            <p class="text-xs font-semibold text-slate-800 mb-1">{{ __('cookie.title') }}</p>
            <p class="text-xs text-slate-500 leading-relaxed">
                {{ __('cookie.description') }}
                <a href="{{ route('public.about') }}" class="text-blue-700 hover:underline">{{ __('cookie.learn_more') }}</a>
            </p>
            <div class="flex gap-2 mt-3">
                <button @click="accept()"
                        class="flex-1 text-xs font-medium bg-blue-700 hover:bg-blue-800
                               text-white py-2 rounded-lg transition-colors">
                    {{ __('cookie.accept') }}
                </button>
                <button @click="decline()"
                        class="flex-1 text-xs font-medium border border-slate-300 text-slate-600
                               hover:bg-slate-50 py-2 rounded-lg transition-colors">
                    {{ __('cookie.decline') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function cookieConsent() {
    return {
        accepted: localStorage.getItem('cookie_consent') !== null,
        accept() {
            localStorage.setItem('cookie_consent', 'accepted');
            this.accepted = true;
        },
        decline() {
            localStorage.setItem('cookie_consent', 'declined');
            this.accepted = true;
        },
    };
}
</script>
