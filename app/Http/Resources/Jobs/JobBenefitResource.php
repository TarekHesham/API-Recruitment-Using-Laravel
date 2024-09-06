<?php

namespace App\Http\Resources\Jobs;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Dependency\BenefitResource;

class JobBenefitResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): object
    {
        // return parent::toArray($request);
        return new BenefitResource($this->benefit);
    }
}
