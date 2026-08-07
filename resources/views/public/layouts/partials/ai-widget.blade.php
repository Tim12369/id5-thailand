{{--
    AI Immigration Assistant — floating chat widget
    Initialised with current locale and persona (auto-detected from URL if on a SmartGuide page).
--}}
@php
    $locale  = app()->getLocale();
    // Auto-detect persona from URL: /smart-guide/{persona}/...
    $urlPersona = 'general';
    $pathParts  = explode('/', trim(request()->path(), '/'));
    if (count($pathParts) >= 2 && $pathParts[0] === 'smart-guide') {
        $urlPersona = $pathParts[1] ?? 'general';
    }

    $personas = [
        'tourist'      => ['label_th' => 'นักท่องเที่ยว', 'label_en' => 'Tourist',      'icon' => 'ti-plane'],
        'student'      => ['label_th' => 'นักเรียน',      'label_en' => 'Student',       'icon' => 'ti-school'],
        'worker'       => ['label_th' => 'แรงงาน',        'label_en' => 'Worker',        'icon' => 'ti-briefcase'],
        'investor'     => ['label_th' => 'นักลงทุน',      'label_en' => 'Investor',      'icon' => 'ti-chart-bar'],
        'hotel'        => ['label_th' => 'โรงแรม',        'label_en' => 'Hotel',         'icon' => 'ti-building'],
        'employer'     => ['label_th' => 'นายจ้าง',       'label_en' => 'Employer',      'icon' => 'ti-users'],
        'thai_citizen' => ['label_th' => 'คนไทย',         'label_en' => 'Thai Citizen',  'icon' => 'ti-flag'],
        'retiree'      => ['label_th' => 'ผู้เกษียณ',     'label_en' => 'Retiree',       'icon' => 'ti-heart'],
        'family'       => ['label_th' => 'ครอบครัว',      'label_en' => 'Family',        'icon' => 'ti-home'],
        'officer'      => ['label_th' => 'เจ้าหน้าที่',   'label_en' => 'Officer',       'icon' => 'ti-shield'],
    ];
@endphp

<div
    x-data="aiWidget('{{ $urlPersona }}', '{{ $locale }}', '{{ csrf_token() }}')"
    x-init="init()"
    class="ai-widget"
    style="position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;font-family:inherit;"
>
    {{-- ── Floating trigger button ── --}}
    <button
        @click="open = !open"
        :aria-label="open ? '{{ __('ai.close') }}' : '{{ __('ai.open_chat') }}'"
        class="ai-fab"
        style="
            width:3.5rem;height:3.5rem;border-radius:50%;
            background:linear-gradient(135deg,#2f64e8,#1a3fa0);
            border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;
            box-shadow:0 4px 20px rgba(47,100,232,0.45);
            transition:transform .2s,box-shadow .2s;
            position:relative;
        "
        :style="open ? 'transform:scale(0.92)' : 'transform:scale(1)'"
    >
        <span x-show="!open" style="color:#fff;font-size:1.5rem;line-height:1;" aria-hidden="true">
            <i class="ti ti-message-circle-2"></i>
        </span>
        <span x-show="open" style="color:#fff;font-size:1.4rem;line-height:1;" aria-hidden="true">
            <i class="ti ti-x"></i>
        </span>
        {{-- "AI" badge --}}
        <span x-show="!open" style="
            position:absolute;top:-4px;right:-4px;
            background:#d9a437;color:#fff;font-size:.6rem;font-weight:700;
            border-radius:9999px;padding:1px 5px;letter-spacing:.05em;
        ">AI</span>
        {{-- Unread dot when closed --}}
        <span x-show="!open && unread > 0" style="
            position:absolute;top:-2px;left:-2px;
            background:#ef4444;border-radius:50%;width:10px;height:10px;
        "></span>
    </button>

    {{-- ── Chat panel ── --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-2 scale-95"
        class="ai-chat-panel"
        style="
            position:absolute;bottom:4.5rem;right:0;
            width:min(380px,calc(100vw - 2rem));
            height:min(600px,calc(100vh - 6rem));
            background:#0f172a;border-radius:1rem;
            border:1px solid rgba(47,100,232,.3);
            box-shadow:0 20px 60px rgba(0,0,0,.5);
            overflow:hidden;
        "
    >
        {{-- Header --}}
        <div style="
            background:linear-gradient(135deg,#2f64e8,#1a3fa0);
            padding:.85rem 1rem;flex-shrink:0;
        ">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <div style="display:flex;align-items:center;gap:.6rem;">
                    <div style="
                        background:rgba(255,255,255,.15);border-radius:.5rem;
                        width:2rem;height:2rem;display:flex;align-items:center;justify-content:center;
                    ">
                        <i class="ti ti-robot" style="color:#fff;font-size:1.1rem;"></i>
                    </div>
                    <div>
                        <div style="color:#fff;font-weight:700;font-size:.9rem;line-height:1.2;">{{ __('ai.title') }}</div>
                        <div style="color:rgba(255,255,255,.7);font-size:.72rem;">{{ __('ai.subtitle') }}</div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:.5rem;">
                    <button
                        @click="resetConversation()"
                        :title="'{{ __('ai.new_conversation') }}'"
                        style="background:rgba(255,255,255,.15);border:none;border-radius:.4rem;padding:.3rem .45rem;cursor:pointer;color:#fff;font-size:.85rem;"
                    ><i class="ti ti-refresh"></i></button>
                    <button
                        @click="open = false"
                        style="background:rgba(255,255,255,.15);border:none;border-radius:.4rem;padding:.3rem .45rem;cursor:pointer;color:#fff;font-size:.85rem;"
                    ><i class="ti ti-x"></i></button>
                </div>
            </div>

            {{-- Persona chips --}}
            <div style="display:flex;flex-wrap:wrap;gap:.35rem;margin-top:.65rem;">
                @foreach($personas as $key => $p)
                <button
                    @click="selectPersona('{{ $key }}')"
                    :style="persona === '{{ $key }}'
                        ? 'background:#d9a437;color:#fff;border-color:#d9a437;'
                        : 'background:rgba(255,255,255,.1);color:rgba(255,255,255,.8);border-color:rgba(255,255,255,.2);'"
                    style="border:1px solid;border-radius:9999px;padding:.18rem .55rem;font-size:.68rem;cursor:pointer;transition:all .15s;white-space:nowrap;"
                >
                    <i class="ti {{ $p['icon'] }}" style="margin-right:.15rem;font-size:.7rem;"></i>
                    {{ $locale === 'th' ? $p['label_th'] : $p['label_en'] }}
                </button>
                @endforeach
            </div>
        </div>

        {{-- Message list --}}
        <div
            x-ref="messageList"
            class="ai-msg-list"
            style="padding:.85rem 1rem;display:flex;flex-direction:column;gap:.75rem;"
        >
            {{-- Suggested questions (shown when no messages yet) --}}
            <template x-if="messages.length === 0">
                <div>
                    <p style="color:rgba(255,255,255,.5);font-size:.72rem;margin-bottom:.5rem;">
                        {{ __('ai.suggested') }}
                    </p>
                    <div style="display:flex;flex-direction:column;gap:.4rem;">
                        <template x-for="q in suggested" :key="q">
                            <button
                                @click="sendSuggested(q)"
                                style="
                                    text-align:left;background:rgba(47,100,232,.15);
                                    border:1px solid rgba(47,100,232,.3);border-radius:.5rem;
                                    padding:.5rem .75rem;color:rgba(255,255,255,.85);
                                    font-size:.78rem;cursor:pointer;transition:background .15s;
                                "
                                @mouseenter="$el.style.background='rgba(47,100,232,.3)'"
                                @mouseleave="$el.style.background='rgba(47,100,232,.15)'"
                                x-text="q"
                            ></button>
                        </template>
                    </div>
                </div>
            </template>

            {{-- Chat messages --}}
            <template x-for="(msg, idx) in messages" :key="idx">
                <div :style="msg.role === 'user' ? 'display:flex;justify-content:flex-end;' : 'display:flex;justify-content:flex-start;'">
                    {{-- User bubble --}}
                    <template x-if="msg.role === 'user'">
                        <div style="
                            max-width:80%;background:linear-gradient(135deg,#2f64e8,#1a3fa0);
                            border-radius:.75rem .75rem 0 .75rem;padding:.6rem .85rem;
                            color:#fff;font-size:.82rem;line-height:1.5;
                        " x-text="msg.content"></div>
                    </template>

                    {{-- Assistant bubble --}}
                    <template x-if="msg.role === 'assistant'">
                        <div style="max-width:92%;">
                            {{-- Summary --}}
                            <div style="
                                background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.1);
                                border-radius:.75rem .75rem .75rem 0;padding:.7rem .9rem;
                                color:rgba(255,255,255,.9);font-size:.82rem;line-height:1.6;margin-bottom:.4rem;
                            " x-text="msg.data.summary"></div>

                            {{-- Steps --}}
                            <template x-if="msg.data.steps && msg.data.steps.length > 0">
                                <div style="
                                    background:rgba(47,100,232,.12);border:1px solid rgba(47,100,232,.25);
                                    border-radius:.5rem;padding:.6rem .8rem;margin-bottom:.35rem;
                                ">
                                    <p style="color:#93c5fd;font-size:.72rem;font-weight:600;margin-bottom:.4rem;text-transform:uppercase;letter-spacing:.05em;">
                                        {{ __('ai.steps_label') }}
                                    </p>
                                    <ol style="margin:0;padding-left:1.1rem;color:rgba(255,255,255,.85);font-size:.78rem;line-height:1.6;">
                                        <template x-for="(step, si) in msg.data.steps" :key="si">
                                            <li x-text="step" style="margin-bottom:.2rem;"></li>
                                        </template>
                                    </ol>
                                </div>
                            </template>

                            {{-- Required documents --}}
                            <template x-if="msg.data.documents && msg.data.documents.length > 0">
                                <div style="
                                    background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.25);
                                    border-radius:.5rem;padding:.6rem .8rem;margin-bottom:.35rem;
                                ">
                                    <p style="color:#6ee7b7;font-size:.72rem;font-weight:600;margin-bottom:.4rem;text-transform:uppercase;letter-spacing:.05em;">
                                        {{ __('ai.documents_label') }}
                                    </p>
                                    <ul style="margin:0;padding-left:1.1rem;color:rgba(255,255,255,.85);font-size:.78rem;line-height:1.6;">
                                        <template x-for="(doc, di) in msg.data.documents" :key="di">
                                            <li x-text="doc" style="margin-bottom:.15rem;"></li>
                                        </template>
                                    </ul>
                                </div>
                            </template>

                            {{-- Forms --}}
                            <template x-if="msg.data.forms && msg.data.forms.length > 0">
                                <div style="
                                    background:rgba(217,164,55,.1);border:1px solid rgba(217,164,55,.25);
                                    border-radius:.5rem;padding:.6rem .8rem;margin-bottom:.35rem;
                                ">
                                    <p style="color:#fcd34d;font-size:.72rem;font-weight:600;margin-bottom:.4rem;text-transform:uppercase;letter-spacing:.05em;">
                                        {{ __('ai.forms_label') }}
                                    </p>
                                    <div style="display:flex;flex-wrap:wrap;gap:.3rem;">
                                        <template x-for="(form, fi) in msg.data.forms" :key="fi">
                                            <span style="
                                                background:rgba(217,164,55,.2);border:1px solid rgba(217,164,55,.4);
                                                border-radius:9999px;padding:.15rem .55rem;
                                                color:#fcd34d;font-size:.72rem;font-weight:600;
                                            " x-text="form.name + (form.description ? ' — ' + form.description : '')"></span>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            {{-- Links --}}
                            <template x-if="msg.data.links && msg.data.links.length > 0">
                                <div style="
                                    background:rgba(99,102,241,.1);border:1px solid rgba(99,102,241,.25);
                                    border-radius:.5rem;padding:.6rem .8rem;margin-bottom:.35rem;
                                ">
                                    <p style="color:#a5b4fc;font-size:.72rem;font-weight:600;margin-bottom:.4rem;text-transform:uppercase;letter-spacing:.05em;">
                                        {{ __('ai.links_label') }}
                                    </p>
                                    <div style="display:flex;flex-direction:column;gap:.2rem;">
                                        <template x-for="(link, li) in msg.data.links" :key="li">
                                            <a
                                                :href="link.path"
                                                style="color:#818cf8;font-size:.78rem;text-decoration:none;"
                                                @mouseenter="$el.style.textDecoration='underline'"
                                                @mouseleave="$el.style.textDecoration='none'"
                                            >
                                                <i class="ti ti-arrow-right" style="font-size:.7rem;margin-right:.2rem;"></i>
                                                <span x-text="link.label"></span>
                                            </a>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            {{-- Office tip --}}
                            <template x-if="msg.data.office_tip">
                                <div style="
                                    display:flex;align-items:flex-start;gap:.4rem;
                                    color:rgba(255,255,255,.5);font-size:.72rem;
                                    margin-bottom:.3rem;
                                ">
                                    <i class="ti ti-map-pin" style="margin-top:1px;flex-shrink:0;"></i>
                                    <span x-text="msg.data.office_tip"></span>
                                </div>
                            </template>

                            {{-- Disclaimer --}}
                            <template x-if="msg.data.disclaimer">
                                <p style="
                                    color:rgba(255,255,255,.35);font-size:.68rem;font-style:italic;
                                    border-top:1px solid rgba(255,255,255,.08);padding-top:.35rem;margin-top:.1rem;
                                " x-text="msg.data.disclaimer"></p>
                            </template>
                        </div>
                    </template>
                </div>
            </template>

            {{-- Loading indicator --}}
            <template x-if="loading">
                <div style="display:flex;justify-content:flex-start;">
                    <div style="
                        background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.1);
                        border-radius:.75rem .75rem .75rem 0;padding:.6rem .9rem;
                        display:flex;align-items:center;gap:.4rem;
                    ">
                        <span style="display:flex;gap:.25rem;">
                            <span class="ai-dot" style="width:6px;height:6px;border-radius:50%;background:#6b7280;animation:ai-bounce .9s ease-in-out infinite;"></span>
                            <span class="ai-dot" style="width:6px;height:6px;border-radius:50%;background:#6b7280;animation:ai-bounce .9s ease-in-out .2s infinite;"></span>
                            <span class="ai-dot" style="width:6px;height:6px;border-radius:50%;background:#6b7280;animation:ai-bounce .9s ease-in-out .4s infinite;"></span>
                        </span>
                        <span style="color:rgba(255,255,255,.4);font-size:.72rem;">{{ __('ai.loading') }}</span>
                    </div>
                </div>
            </template>

            {{-- Error message --}}
            <template x-if="errorMsg">
                <div style="
                    background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);
                    border-radius:.5rem;padding:.5rem .75rem;color:#fca5a5;font-size:.78rem;
                " x-text="errorMsg"></div>
            </template>
        </div>

        {{-- Input area --}}
        <div style="
            border-top:1px solid rgba(255,255,255,.1);
            padding:.75rem 1rem;flex-shrink:0;
            background:rgba(255,255,255,.03);
        ">
            <div style="display:flex;gap:.5rem;align-items:flex-end;">
                <div style="flex:1;position:relative;">
                    <textarea
                        x-model="input"
                        @keydown.enter.prevent="if(!$event.shiftKey) sendMessage()"
                        :disabled="loading"
                        :placeholder="'{{ __('ai.placeholder') }}'"
                        maxlength="500"
                        rows="1"
                        x-ref="inputArea"
                        @input="$el.style.height='auto';$el.style.height=Math.min($el.scrollHeight,120)+'px'"
                        style="
                            width:100%;background:rgba(255,255,255,.07);
                            border:1px solid rgba(255,255,255,.15);border-radius:.6rem;
                            color:#fff;font-size:.82rem;padding:.55rem .75rem;resize:none;
                            line-height:1.5;outline:none;transition:border-color .15s;
                            font-family:inherit;
                        "
                        @focus="$el.style.borderColor='rgba(47,100,232,.6)'"
                        @blur="$el.style.borderColor='rgba(255,255,255,.15)'"
                    ></textarea>
                    <span style="
                        position:absolute;bottom:.35rem;right:.5rem;
                        color:rgba(255,255,255,.25);font-size:.62rem;
                    " x-text="input.length + '/500'"></span>
                </div>
                <button
                    @click="sendMessage()"
                    :disabled="loading || input.trim().length === 0"
                    style="
                        background:linear-gradient(135deg,#2f64e8,#1a3fa0);
                        border:none;border-radius:.6rem;
                        width:2.4rem;height:2.4rem;flex-shrink:0;
                        display:flex;align-items:center;justify-content:center;
                        cursor:pointer;transition:opacity .15s;
                        color:#fff;font-size:1rem;
                    "
                    :style="(loading || input.trim().length === 0) ? 'opacity:.4;cursor:not-allowed;' : 'opacity:1;'"
                >
                    <i class="ti ti-send-2"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes ai-bounce {
    0%,80%,100% { transform:translateY(0); }
    40%          { transform:translateY(-5px); }
}
.ai-chat-panel {
    display: grid;
    grid-template-rows: auto 1fr auto;
}
.ai-chat-panel .ai-msg-list {
    overflow-y: auto;
    overflow-x: hidden;
    min-height: 0;
}
</style>

<script>
function aiWidget(initialPersona, locale, csrfToken) {
    return {
        open:           false,
        persona:        initialPersona || 'general',
        locale:         locale || 'th',
        csrfToken:      csrfToken,
        conversationId: null,
        messages:       [],
        suggested:      [],
        input:          '',
        loading:        false,
        errorMsg:       '',
        unread:         0,

        async init() {
            // Auto-start conversation and load suggestions
            await this.startConversation();
        },

        async startConversation() {
            try {
                const res = await fetch('/ai/conversation', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ persona: this.persona, locale: this.locale }),
                });
                if (!res.ok) throw new Error('Failed to start');
                const data = await res.json();
                this.conversationId = data.conversation_id;
                this.suggested      = data.suggested_questions || [];
            } catch (e) {
                console.error('AI widget: could not start conversation', e);
            }
        },

        async selectPersona(p) {
            this.persona = p;
            // Reload suggestions for new persona
            try {
                const res = await fetch('/ai/suggested/' + p, {
                    headers: { 'Accept': 'application/json' }
                });
                if (res.ok) {
                    const data = await res.json();
                    this.suggested = data.questions || [];
                }
            } catch(e) {}
        },

        async sendSuggested(q) {
            this.input = q;
            await this.sendMessage();
        },

        async sendMessage() {
            const text = this.input.trim();
            if (!text || this.loading || !this.conversationId) return;

            this.input    = '';
            this.errorMsg = '';
            this.loading  = true;

            // Push user message immediately
            this.messages.push({ role: 'user', content: text });
            this.$nextTick(() => this.scrollToBottom());

            try {
                const res = await fetch('/ai/chat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        conversation_id: this.conversationId,
                        message:         text,
                        persona:         this.persona,
                        locale:          this.locale,
                    }),
                });

                const data = await res.json();

                if (!res.ok) {
                    this.errorMsg = data.message || data.error || '{{ __('ai.error_generic') }}';
                } else {
                    this.messages.push({ role: 'assistant', data });
                    if (!this.open) this.unread++;
                }
            } catch (e) {
                this.errorMsg = '{{ __('ai.error_generic') }}';
            } finally {
                this.loading = false;
                this.$nextTick(() => {
                    this.scrollToBottom();
                    this.$refs.inputArea?.focus();
                });
            }
        },

        scrollToBottom() {
            const el = this.$refs.messageList;
            if (el) el.scrollTop = el.scrollHeight;
        },

        async resetConversation() {
            this.messages       = [];
            this.conversationId = null;
            this.errorMsg       = '';
            this.unread         = 0;
            await this.startConversation();
        },
    };
}
</script>
