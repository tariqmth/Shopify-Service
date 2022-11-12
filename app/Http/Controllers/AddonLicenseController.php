<?php

namespace App\Http\Controllers;

use App\Models\License\AddonLicense;
use App\Models\Syncer\RexOutletSyncer;
use Illuminate\Http\Request;
use App\Models\Client\Client;
use Illuminate\Validation\Rule;
use Validator;
use App\Http\Resources\AddonLicenseCollection;
use App\Http\Resources\AddonLicense as AddonLicenseResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;

class AddonLicenseController extends Controller
{
    private $rexOutletSyncer;

    public function __construct(RexOutletSyncer $rexOutletSyncer)
    {
        $this->rexOutletSyncer = $rexOutletSyncer;
    }

    public function all($clientId)
    {
        try {
            return new AddonLicenseCollection($this->findAddonLicenses($clientId));
        } catch (ModelNotFoundException $e) {
            return response('Client or license could not be found.', 404);
        }
    }

    public function get($clientId, $name)
    {
        try {
            return new AddonLicenseResource($this->findAddonLicense($clientId, $name));
        } catch (ModelNotFoundException $e) {
            return response('Client or license could not be found.', 404);
        }
    }

    public function post(Request $request, $clientId)
    {
        try {
            $client = Client::where('external_id', $clientId)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return response('Client could not be found.', 404);
        }

        $addonLicenseData = json_decode($request->getContent(), true);

        $validator = Validator::make($addonLicenseData, [
            'addon_license' => 'required',
            'addon_license.name' => [
                'required',
                'string',
                'max:255',
                Rule::in(AddonLicense::VALID_LICENSE_NAMES)
            ]
        ]);

        if (!isset($addonLicenseData) || $validator->fails()) {
            return response($validator->errors(), 400);
        }

        $objectData = $addonLicenseData['addon_license'];

        if (AddonLicense::where('client_id', $client->id)->where('name', $objectData['name'])->first() !== null) {
            return response('License already exists.', 400);
        }

        $addonLicense = new AddonLicense;
        $addonLicense->client()->associate($client);
        $addonLicense->name = $objectData['name'];
        $addonLicense->save();

        if ($addonLicense->name === 'click_and_collect') {
            foreach ($client->rexSalesChannels as $rexSalesChannel) {
                $this->rexOutletSyncer->syncAllOut($rexSalesChannel);
            }
        }

        return new AddonLicenseResource($addonLicense);
    }

    public function delete($clientId, $name)
    {
        try {
            $addonLicense = $this->findAddonLicense($clientId, $name);
        } catch (ModelNotFoundException $e) {
            return response('License could not be found.', 404);
        }

        $addonLicense->delete();

        return response('License deleted.', 200);
    }

    private function findAddonLicense($clientExternalId, $licenseName)
    {
        $client = Client::where('external_id', $clientExternalId)->firstOrFail();
        return AddonLicense::where('client_id', $client->id)->where('name', $licenseName)->firstOrFail();
    }

    private function findAddonLicenses($clientExternalId)
    {
        $client = Client::where('external_id', $clientExternalId)->firstOrFail();
        return AddonLicense::where('client_id', $client->id)->get();
    }
}
