@extends('internal.layouts.app')

@section('title', 'Edit Knowledge Base Article')

@section('content')
    <div class="mb-6">
        <h2 class="text-xl font-bold text-white">Edit Knowledge Base Article</h2>
        <p class="text-sm text-slate-400 mt-1">{{ $article->title_en ?: $article->title_th }}</p>
    </div>

    <form method="POST" action="{{ route('internal.cms.knowledge-base.update', $article) }}" enctype="multipart/form-data">
        @method('PUT')
        @include('internal.cms.knowledge-base.form')
    </form>
@endsection
