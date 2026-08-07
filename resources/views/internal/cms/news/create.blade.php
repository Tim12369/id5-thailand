@extends('internal.layouts.app')

@section('title', 'Create News')

@section('content')
    <div class="mb-6">
        <h2 class="text-xl font-bold text-white">Create News</h2>
        <p class="text-sm text-slate-400 mt-1">This will appear on the public homepage once published.</p>
    </div>

    <form method="POST" action="{{ route('internal.cms.news.store') }}" enctype="multipart/form-data">
        @include('internal.cms.news.form', ['article' => null])
    </form>
@endsection
