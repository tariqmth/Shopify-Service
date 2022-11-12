<?php

namespace App\Http\Controllers;

use App\Http\Resources\ClientCollection;
use Illuminate\Http\Request;
use App\Models\Product\RexProductRepository;
use App\Models\Syncer\RexProductSyncer;
use App\Models\Client\Client;
use Validator;
use App\Http\Resources\Client as ClientResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ClientController extends Controller
{
    private $productSyncer;
    private $productRepository;

    public function __construct(
        RexProductSyncer $productSyncer,
        RexProductRepository $productRepository
    ) {
        $this->productSyncer = $productSyncer;
        $this->productRepository = $productRepository;
    }

    public function all(Request $request)
    {
        return new ClientCollection(Client::all());
    }

    public function get(Request $request, $clientId)
    {
        try {
            return new ClientResource(Client::where('external_id', $clientId)->firstOrFail());
        } catch (ModelNotFoundException $e) {
            return response('Client could not be found.', 404);
        }
    }

    public function post(Request $request)
    {
        $rexClientData = json_decode($request->getContent(), true);

        $validator = Validator::make($rexClientData, [
            'client' => 'required',
            'client.client_id' => 'required'
        ]);

        if (!isset($rexClientData) || $validator->fails()) {
            return response('Invalid JSON or missing required fields.', 400);
        }

        $clientData = $rexClientData['client'];

        $client = Client::firstOrNew(['external_id' => $clientData['client_id']]);

        $client->licensed_stores = 0;

        $this->updateClient($client, $clientData);

        return new ClientResource($client);
    }

    public function put(Request $request, $clientId)
    {
        $data = json_decode($request->getContent(), true);
        $clientData = $data['client'];

        try {
            $client = Client::where('external_id', $clientId)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return response('Client could not be found.', 404);
        }

        $this->updateClient($client, $clientData);

        return new ClientResource($client);
    }

    public function delete(Request $request, $clientId)
    {
        try {
            $client = Client::where('external_id', $clientId)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return response('Client could not be found.', 404);
        }

        $client->delete();

        return response('Client deleted.', 200);
    }

    private function updateClient(Client $client, $clientData)
    {
        $client->name             = array_get($clientData, 'name', $client->name);
        $client->username         = array_get($clientData, 'eds_credentials.username', $client->username);
        $client->password         = array_get($clientData, 'eds_credentials.password', $client->password);
        $client->license          = array_get($clientData, 'license_type', $client->license);
        $client->licensed_stores  = array_get($clientData, 'licensed_stores', $client->licensed_stores);
        $client->save();

        if ($client->license === 'none' || is_null($client->license)) {
            foreach ($client->shopifyStores as $shopifyStore) {
                if ($shopifyStore->enabled) {
                    $shopifyStore->enabled = false;
                    $shopifyStore->save();
                }
            }
        }

        return $client;
    }
}
