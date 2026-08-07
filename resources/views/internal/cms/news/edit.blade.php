@extends('internal.layouts.app')

@section('title', 'Edit News')

@section('content')
    <div class="mb-6">
        <h2 class="text-xl font-bold text-white">Edit News</h2>
        <p class="text-sm text-slate-400 mt-1">{{ $article->title_en ?: $article->title_th }}</p>
    </div>

    <form method="POST" action="{{ route('internal.cms.news.update', $article) }}" enctype="multipart/form-data">
        @method('PUT')
        @include('internal.cms.news.form')
    </form>
@endsection
