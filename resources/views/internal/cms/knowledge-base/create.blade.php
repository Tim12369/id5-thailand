@extends('internal.layouts.app')

@section('title', 'Create Knowledge Base Article')

@section('content')
    <div class="mb-6">
        <h2 class="text-xl font-bold text-white">Create Knowledge Base Article</h2>
        <p class="text-sm text-slate-400 mt-1">This will appear on the public Knowledge Base once published.</p>
    </div>

    <form method="POST" action="{{ route('internal.cms.knowledge-base.store') }}" enctype="multipart/form-data">
        @include('internal.cms.knowledge-base.form', ['article' => null])
    </form>
@endsection
