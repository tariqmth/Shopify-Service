@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1 class="mb-4 text-center">Queue</h1>
    @include('queue.table')
</div>
@endsection
