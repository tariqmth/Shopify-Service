<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ClickAndCollectSetting extends JsonResource
{
    /**
     * The "data" wrapper that should be applied.
     *
     * @var string
     */
    public static $wrap = 'click_and_collect_setting';

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'map_enabled' => intval(isset($this->google_api_key) && strlen($this->google_api_key)>0),
            'google_api_key' => $this->google_api_key
        ];
    }
}
