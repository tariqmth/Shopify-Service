@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <h1 class="mb-4 text-center">Job {{ Request::segment(2) }}</h1>
    <div class="row justify-content-center mb-3">
        @include('history.table')
    </div>
    @if (count($sync_job_logs))
    <h2 class="mb-4 text-center">Logs</h2>
    <div class="justify-content-center mb-5">
        @foreach ($sync_job_logs as $log)
            <div class="card log-entry mb-2">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-1 log-entry__text">
                            <span>{{ $log->id }}</span>
                        </div>
                        <div class="col-md-1 log-entry__text log-entry__level log-entry__level--{{ strtolower($log->level) }}">
                            <span>{{ $log->level }}</span>
                        </div>
                        <div class="col-md log-entry__text log-entry__message">
                            <span>{{ $log->message }}</span>
                        </div>
                        <div class="col-md-2 log-entry__text">
                            <span>{{ $log->created_at }}</span>
                        </div>
                        <div class="col-md-2 col-xl-1 text-right">
                            <button class="btn btn-primary" type="button" data-toggle="collapse" data-target="#logDetails{{ $log->id }}" aria-expanded="false" aria-controls="collapseExample">
                                Details
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-footer collapse log-entry__details" id="logDetails{{ $log->id }}">
                    <h5 class="card-title">Message</h5>
                    <p>{{ $log->message }}</p>
                    <h5 class="card-title">Context</h5>
                    <p>{{ $log->context }}</p>
                </div>
            </div>
        @endforeach
    </div>
    @endif
    @if (isset($failed_job))
    <h2 class="mb-4 text-center">Failed job payload</h2>
    <div class="justify-content-center mb-5">
        <div class="card">
            <div class="card-body">
                {{ $failed_job->payload }}
            </div>
        </div>
    </div>
    @endif
</div>

@endsection