<?php

namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'taskname' => $this->taskname,
            'description' => $this->description,
            'startdate' => $this->startdate,
            'enddate' => $this->enddate,
            'category' => $this->category,
            'image' => $this->image,
            'created_at' => $this->created_at->format('d/m/Y'),
            'updated_at' => $this->updated_at->format('d/m/Y'),
        ];
    }
}