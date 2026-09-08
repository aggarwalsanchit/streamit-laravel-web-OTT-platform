@extends('backend.layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">File Manager</h4>
                        @if ($folder)
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a href="{{ route('backend.media-library.browse') }}">Root</a>
                                    </li>
                                    @foreach (explode('/', $folder) as $segment)
                                        <li class="breadcrumb-item active" aria-current="page">{{ $segment }}</li>
                                    @endforeach
                                </ol>
                            </nav>
                        @endif
                    </div>
                    <div class="card-body">
                        @include('filemanager::backend.filemanager.partial')
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
