@extends('internal.layouts.app')

@section('title', 'FAQ')

@section('content')
    <div class="flex items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-xl font-bold text-white">FAQ</h2>
            <p class="text-sm text-slate-400 mt-1">Manage the questions and answers shown on the public FAQ page.</p>
        </div>
        <a href="{{ route('internal.faq.create') }}" class="btn-portal-primary">
            <i class="ti ti-plus"></i> Add FAQ
        </a>
    </div>

    @if (session('success'))
        <div class="badge-success inline-flex mb-4"><i class="ti ti-check mr-1"></i> {{ session('success') }}</div>
    @endif

    <div class="space-y-6">
        @forelse ($faqs as $categoryName => $categoryFaqs)
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">{{ $categoryName }}</h3>
                <div class="portal-card overflow-hidden">
                    <div class="divide-y divide-white/5">
                        @foreach ($categoryFaqs as $faq)
                            <div class="flex items-center gap-4 p-4">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-slate-100 truncate">{{ $faq->question_th }}</p>
                                    <p class="text-xs text-slate-400 truncate">{{ $faq->question_en }}</p>
                                </div>

                                @unless ($faq->is_public)
                                    <span class="badge-neutral shrink-0">Hidden</span>
                                @endunless
                                @unless ($faq->is_active)
                                    <span class="badge-danger shrink-0">Inactive</span>
                                @endunless

                                <a href="{{ route('internal.faq.edit', $faq) }}" class="btn-secondary btn-sm shrink-0">
                                    <i class="ti ti-edit"></i>
                                </a>
                                <form method="POST" action="{{ route('internal.faq.destroy', $faq) }}" onsubmit="return confirm('Delete this FAQ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-danger-ghost btn-sm">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @empty
            <div class="portal-card p-8 text-center text-sm text-slate-400">No FAQs yet.</div>
        @endforelse
    </div>
@endsection
