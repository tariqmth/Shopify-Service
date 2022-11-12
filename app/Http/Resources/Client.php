<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Client extends JsonResource
{
    /**
     * The "data" wrapper that should be applied.
     *
     * @var string
     */
    public static $wrap = 'client';

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'client_id' => $this->external_id,
            'name' => $this->name,
            'eds_credentials' => [
                'username' => $this->username,
                'password' => $this->password
            ],
            'license_type' => $this->license ? $this->license : 'none',
            'licensed_stores' => $this->licensed_stores
        ];
    }
}
