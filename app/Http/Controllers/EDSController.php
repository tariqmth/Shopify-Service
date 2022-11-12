<?php

namespace App\Http\Controllers;

use App\Queues\Jobs\ProcessRexEDSNotification;
use Illuminate\Http\Request;

class EDSController extends Controller
{
    const MAX_JOBS = 1000;

    public function put(Request $request)
    {
        $clientExternalId = $request->input('client_id');
        $rexSalesChannelExternalId = $request->input('channel_id');

        $notification = json_decode($request->getContent());

        foreach ($notification->Changesets as $changeset) {
            foreach ($changeset->UpdatedEntities as $entity) {
                $count = count($entity->List);
                if ($count > self::MAX_JOBS) {
                    for ($i = 0; $i < $count; $i += self::MAX_JOBS) {
                        $partialEntity = clone $entity;
                        $partialEntity->List = [];
                        for ($id = $i; $id < $count && $id < $i + self::MAX_JOBS; $id++) {
                            $partialEntity->List[] = $entity->List[$id];
                        }
                        ProcessRexEDSNotification::dispatch($clientExternalId, $partialEntity, $rexSalesChannelExternalId)
                            ->onConnection('database_sync')
                            ->onQueue('notification')
                            ->delay(now()->addMinutes(1));
                    }
                } else {
                    ProcessRexEDSNotification::dispatch($clientExternalId, $entity, $rexSalesChannelExternalId)
                        ->onConnection('database_sync')
                        ->onQueue('notification')
                        ->delay(now()->addMinutes(1));
                }
            }
        }

        return response()->json();
    }
}
