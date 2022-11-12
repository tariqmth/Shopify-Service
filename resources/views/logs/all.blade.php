@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <h1 class="mb-4 text-center">Logs</h1>
    <div class="row justify-content-center mb-5">
        <form class="col" method="GET" action="/logs">
            @csrf
            <div class="form-row">
                <div class="form-group col-6 col-md-4 col-xl">
                    <label for="search_id">ID</label>
                    <input type="text" class="form-control" id="search_id" name="id" placeholder="ID" value="{{ old('id') }}">
                </div>
                <div class="form-group col-6 col-md-4 col-xl">
                    <label for="job_unique_id">Job Unique ID</label>
                    <input type="text" class="form-control" id="job_unique_id" name="sync_jobs_history_unique_id" placeholder="Unique ID" value="{{ old('sync_jobs_history_unique_id') }}">
                </div>
                <div class="form-group col-6 col-md-4 col-xl">
                    <label for="search_level">Level</label>
                    <select class="form-control" id="search_level" name="level">
                        <option></option>
                        <option value="debug" {{ old('level') === 'debug' ? 'selected' : null }}>debug</option>
                        <option value="warning" {{ old('level') === 'warning' ? 'selected' : null }}>warning</option>
                        <option value="error" {{ old('level') === 'error' ? 'selected' : null }}>error</option>
                    </select>
                </div>
                <div class="form-group col-6 col-md-4 col-xl">
                    <label for="search_message">Message</label>
                    <input type="text" class="form-control" id="search_message" name="message" placeholder="Message" value="{{ old('message') }}">
                </div>
                <div class="form-group col-6 col-md-4 col-xl">
                    <label for="search_context">Context</label>
                    <input type="text" class="form-control" id="search_context" name="context" placeholder="Context" value="{{ old('context') }}">
                </div>
            </div>
            <div class="form-row justify-content-md-center mb-3">
                <div class="form-group col-6 col-md-4 col-xl-3">
                    <label for="search_start_time">Start Time</label>
                    <input type="datetime-local" class="form-control" id="search_start_time" name="start_time" placeholder="Start time" value="{{ old('start_time') }}">
                </div>
                <div class="form-group col-6 col-md-4 col-xl-3">
                    <label for="search_end_time">End time</label>
                    <input type="datetime-local" class="form-control" id="search_end_time" name="end_time" placeholder="End time" value="{{ old('end_time') }}">
                </div>
                <div class="form-group col-6 col-md-4 col-xl-3">
                    <label for="search_aest">Timestamps in AEST</label>
                    <div class="form-check timezone-checkbox">
                        <input class="form-check-input" type="checkbox" id="search_aest" name="aest" {{ old('aest') ? 'checked' : null }}>
                        <label class="form-check-label">Enabled</label>
                    </div>
                </div>
            </div>
            <div class="text-center">
                <button type="submit" class="btn btn-primary btn-lg search-button">Search</button>
            </div>
        </form>
    </div>
    <div class="justify-content-center mb-3">
        @foreach ($logs as $log)
            <div class="card log-entry mb-2">
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-1 log-entry__text">
                            <span>{{ $log->id }}</span>
                        </div>
                        <div class="col-lg-3 col-xl-2 log-entry__text log-entry__history-unique-id" style="background-color: {{ $log->background }};">
                            <a href="{{ route('history.item.show', ['uniqueId' => $log->sync_jobs_history_unique_id]) }}" style="color: {{ $log->color }};">
                                {{ $log->sync_jobs_history_unique_id }}
                            </a>
                        </div>
                        <div class="col-lg-1 log-entry__text log-entry__level log-entry__level--{{ strtolower($log->level) }}">
                            <span>{{ $log->level }}</span>
                        </div>
                        <div class="col-lg log-entry__text log-entry__message">
                            <span>{{ $log->message }}</span>
                        </div>
                        <div class="col-lg-2 log-entry__text">
                            <span>{{ $log->created_at }}</span>
                        </div>
                        <div class="col-lg-2 col-xl-1 text-right">
                            <button class="btn btn-primary" type="button" data-toggle="collapse" data-target="#logDetails{{ $log->id }}" aria-expanded="false" aria-controls="collapseExample">
                                Details
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-footer collapse log-entry__details" id="logDetails{{ $log->id }}">
                    <h4>Message</h4>
                    <p>{{ $log->message }}</p>
                    <h4>Context</h4>
                    <p>{{ $log->context }}</p>
                </div>
            </div>
        @endforeach
    </div>
    <div class="row justify-content-center">
        {{ $logs->appends(Session::getOldInput())->links() }}
    </div>
</div>

@endsection