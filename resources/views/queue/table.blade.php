<div class="row justify-content-center mb-3">
    <div class="col">
        <table class="table table-responsive-lg history-table rounded">
            <thead>
                <tr>
                    <th scope="col">Client</th>
                    <th scope="col">Store</th>
                    <th scope="col">Queue</th>
                    <th scope="col">Direction</th>
                    <th scope="col">Source</th>
                    <th scope="col">Attempts</th>
                    <th scope="col">Jobs</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sync_job_queue as $queue)
                <tr>
                    <td data-th="Source">{{ $queue->client }}</td>
                    <td data-th="Source">{{ $queue->store }}</td>
                    <td data-th="Source">{{ $queue->queue }}</td>
                    <td data-th="Source">{{ $queue->direction }}</td>
                    <td data-th="Source">{{ $queue->source }}</td>
                    <td data-th="Source">{{ $queue->attempts }}</td>
                    <td data-th="Source">{{ $queue->jobs }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>