@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1 class="mb-4 text-center">Sync History</h1>
    <div class="row justify-content-center mb-5">
        <form class="col" method="GET" action="/history">
            @csrf
            <div class="form-row">
                <div class="form-group col-6 col-md-4 col-xl">
                    <label for="search_status">Status</label>
                    <select class="form-control" id="search_status" name="status">
                        <option></option>
                        <option value="queued" {{ old('status') === 'queued' ? 'selected' : null }}>queued</option>
                        <option value="processing" {{ old('status') === 'processing' ? 'selected' : null }}>processing</option>
                        <option value="failed" {{ old('status') === 'failed' ? 'selected' : null }}>failed</option>
                        <option value="complete" {{ old('status') === 'complete' ? 'selected' : null }}>complete</option>
                    </select>
                </div>
                <div class="form-group col-6 col-md-4 col-xl">
                    <label for="search_unique_id">Unique ID</label>
                    <input type="text" class="form-control" id="search_unique_id" name="unique_id" placeholder="Unique ID" value="{{ old('unique_id') }}">
                </div>
                <div class="form-group col-6 col-md-4 col-xl">
                    <label for="search_parent_unique_id">Parent Unique ID</label>
                    <input type="text" class="form-control" id="search_parent_unique_id" name="parent_unique_id" placeholder="Parent Unique ID" value="{{ old('parent_unique_id') }}">
                </div>
                <div class="form-group col-6 col-md-4 col-xl">
                    <label for="search_source">Source</label>
                    <select class="form-control" id="search_source" name="source">
                        <option></option>
                        <option value="rex" {{ old('source') === 'rex' ? 'selected' : null }}>rex</option>
                        <option value="shopify" {{ old('source') === 'shopify' ? 'selected' : null }}>shopify</option>
                    </select>
                </div>
                <div class="form-group col-6 col-md-4 col-xl">
                    <label for="search_queue">Queue</label>
                    <select class="form-control" id="search_queue" name="queue">
                        <option></option>
                        <option value="store" {{ old('queue') === 'store' ? 'selected' : null }}>store</option>
                        <option value="notification" {{ old('queue') === 'notification' ? 'selected' : null }}>notification</option>
                        <option value="customer" {{ old('queue') === 'customer' ? 'selected' : null }}>customer</option>
                        <option value="order" {{ old('queue') === 'order' ? 'selected' : null }}>order</option>
                        <option value="payment" {{ old('queue') === 'payment' ? 'selected' : null }}>payment</option>
                        <option value="product_enabler" {{ old('queue') === 'product_enabler' ? 'selected' : null }}>product_enabler</option>
                        <option value="product" {{ old('queue') === 'product' ? 'selected' : null }}>product</option>
                        <option value="product_option" {{ old('queue') === 'product_option' ? 'selected' : null }}>product_option</option>
                        <option value="product_inventory" {{ old('queue') === 'product_inventory' ? 'selected' : null }}>product_inventory</option>
                        <option value="fulfillment" {{ old('queue') === 'fulfillment' ? 'selected' : null }}>fulfillment</option>
                        <option value="voucher" {{ old('queue') === 'voucher' ? 'selected' : null }}>voucher</option>
                        <option value="all_customers" {{ old('queue') === 'all_customers' ? 'selected' : null }}>all_customers</option>
                        <option value="all_products" {{ old('queue') === 'all_products' ? 'selected' : null }}>all_products</option>
                        <option value="fulfillment_service" {{ old('queue') === 'fulfillment_service' ? 'selected' : null }}>fulfillment_service</option>
                        <option value="notification_service" {{ old('queue') === 'notification_service' ? 'selected' : null }}>notification_service</option>
                    </select>
                </div>
                <div class="form-group col-6 col-md-4 col-xl">
                    <label for="search_entity_id">Entity ID</label>
                    <input type="text" class="form-control" id="search_entity_id" name="entity_id" placeholder="Entity ID" value="{{ old('entity_id') }}">
                </div>
                <div class="form-group col-6 col-md-4 col-xl">
                    <label for="search_entity_external_id">Entity External ID</label>
                    <input type="text" class="form-control" id="search_entity_external_id" name="entity_external_id" placeholder="Entity External ID" value="{{ old('entity_external_id') }}">
                </div>
                <div class="form-group col-6 col-md-4 col-xl">
                    <label for="search_direction">Direction</label>
                    <select class="form-control" id="search_direction" name="direction">
                        <option></option>
                        <option value="in" {{ old('direction') === 'in' ? 'selected' : null }}>in</option>
                        <option value="out" {{ old('direction') === 'out' ? 'selected' : null }}>out</option>
                    </select>
                </div>
                <div class="form-group col-6 col-md-4 col-xl">
                    <label for="search_client_name">Client Name</label>
                    <input type="text" class="form-control" id="search_client_name" name="client_name" placeholder="Client Name" value="{{ old('client_name') }}">
                </div>
                <div class="form-group col-6 col-md-4 col-xl">
                    <label for="search_client_external_id">Client Ext. ID</label>
                    <input type="text" class="form-control" id="search_client_external_id" name="client_external_id" placeholder="Entity External ID" value="{{ old('client_external_id') }}">
                </div>
                <div class="form-group col-6 col-md-4 col-xl">
                    <label for="search_shopify_store_subdomain">Subdomain</label>
                    <input type="text" class="form-control" id="search_shopify_store_subdomain" name="shopify_store_subdomain" placeholder="Shopify Subdomain" value="{{ old('shopify_store_subdomain') }}">
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
    @if (isset($meta))
    <div class="row justify-content-center mb-4">
        <div class="col-12 col-sm-6 col-xl-3 mb-3">
            <div class="card large-card">
                <div class="card-body">
                    {{ $meta['success_rate'] }}%
                </div>
                <div class="card-footer">
                    Success rate
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3 mb-3">
            <div class="card large-card">
                <div class="card-body">
                    {{ $meta['duration_formatted'] }}
                </div>
                <div class="card-footer">
                    Duration
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3 mb-3">
            <div class="card large-card">
                <div class="card-body">
                    {{ $meta['completed'] }}
                </div>
                <div class="card-footer">
                    Completed
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3 mb-3">
            <div class="card large-card">
                <div class="card-body">
                    {{ $meta['failed'] }}
                </div>
                <div class="card-footer">
                    Failed
                </div>
            </div>
        </div>
    </div>
    @endif
    @include('history.table')
    <div class="row justify-content-center">
        {{ $sync_job_histories->appends(Session::getOldInput())->links() }}
    </div>
</div>
@endsection
