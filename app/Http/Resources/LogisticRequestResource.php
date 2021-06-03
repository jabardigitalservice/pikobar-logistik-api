<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LogisticRequestResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return array
   */
  public function toArray($request)
  {
    $need = [];
    foreach ($this['need'] as $key => $value) {
      $need[] = [
        'product_id' => $value->product_id,
        'brand' => $value->brand,
        'quantity' => $value->quantity,
        'unit' => $value->unit,
        'usage' => $value->usage,
        'priority' => $value->priority,
      ];
    }

    return [
      'agency_type' => $this['agency']->agency_type,
      'agency_name' => $this['agency']->agency_name,
      'phone_number' => $this['agency']->phone_number,
      'location_district_code' => $this['agency']->location_district_code,
      'location_subdistrict_code' => $this['agency']->location_subdistrict_code,
      'location_village_code' => $this['agency']->location_village_code,
      'location_address' => $this['agency']->location_address,
      'applicant_name' => $this['applicant']->applicant_name,
      'applicant_office' => $this['applicant']->applicants_office,
      'email' => $this['applicant']->email,
      'primary_phone_number' => $this['applicant']->primary_phone_number,
      'secondary_phone_number' => $this['applicant']->secondary_phone_number,
      'need' => $need
    ];
  }
}
