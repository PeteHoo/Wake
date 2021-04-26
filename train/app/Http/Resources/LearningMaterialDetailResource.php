<?php


namespace App\Http\Resources;


use Illuminate\Http\Resources\Json\JsonResource;

class LearningMaterialDetailResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'mechanism' => $this->mechanism->name??'',
            'industry' => $this->industry->name??'',
            'occupation' => $this->occupation->name??'',
            'picture' =>  getImageUrl($this->picture),
            'status' => $this->status,
            'sort' => $this->sort,
            'chapter'=>$this->chapter,
        ];
    }
}