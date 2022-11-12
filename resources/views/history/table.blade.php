<div class="row justify-content-center mb-3">
    <div class="col">
        <table class="table table-responsive-lg history-table rounded">
            <thead>
                <tr>
                    <th scope="col">Status</th>
                    <th scope="col">Parent</th>
                    <th scope="col">Unique ID</th>
                    <th scope="col">Source</th>
                    <th scope="col">Queue</th>
                    <th scope="col">Entity ID</th>
                    <th scope="col">External ID</th>
                    <th scope="col">Direction</th>
                    <th scope="col">Client</th>
                    <th scope="col">Subdomain</th>
                    <th scope="col">Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sync_job_histories as $history)
                <tr>
                    <td class="status-cell status-cell--{{ $history->status_label }}" data-th="Status">
                        {{ $history->status_label }}
                    </td>
                    <td class="history-id-cell" style="background-color: {{ $history->parent_background }};" data-th="Parent">
                        <a href="{{ route('history.item.show', ['uniqueId' => $history->parent_unique_id]) }}" style="color: {{ $history->parent_color }};">
                            {{ $history->parent_short_id ? '...' . $history->parent_short_id : null }}
                        </a>
                    </td>
                    <td class="history-id-cell" style="background-color: {{ $history->background }};" data-th="Unique ID">
                        <a href="{{ route('history.item.show', ['uniqueId' => $history->unique_id]) }}" style="color: {{ $history->color }};">
                            {{ $history->unique_id }}
                        </a>
                    </td>
                    <td data-th="Source">{{ $history->source }}</td>
                    <td data-th="Queue">{{ $history->queue }}</td>
                    <td data-th="Entity ID">{{ $history->entity_id }}</td>
                    <td data-th="External ID">{{ $history->entity_external_id }}</td>
                    <td data-th="Direction">{{ $history->direction }}</td>
                    <td data-th="Client">{{ $history->client_name }}</td>
                    <td data-th="Shopify Subdomain">{{ $history->shopify_store_subdomain }}</td>
                    <td data-th="Date">{{ $history->update_created_at }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>