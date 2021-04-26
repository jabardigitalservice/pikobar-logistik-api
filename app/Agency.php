<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;

class Agency extends Model
{
    protected $table = 'agency';

    protected $fillable = [
        'master_faskes_id',
        'agency_type',
        'agency_name',
        'phone_number',
        'location_district_code',
        'location_subdistrict_code',
        'location_village_code',
        'location_address',
        'total_covid_patients',
        'total_isolation_room',
        'total_bedroom',
        'total_health_worker'
    ];

    protected $appends = ['total_qty', 'type_item_count'];

    public function getTotalQtyAttribute()
    {
        return $this->logisticRealizationItems()->whereNotIn('final_status', [
            LogisticRealizationItems::STATUS_NOT_AVAILABLE,
            LogisticRealizationItems::STATUS_NOT_YET_FULFILLED
        ])->sum('final_quantity');
    }

    public function getTypeItemCountAttribute()
    {
        return $this->logisticRealizationItems()->whereNotIn('final_status', [
            LogisticRealizationItems::STATUS_NOT_AVAILABLE,
            LogisticRealizationItems::STATUS_NOT_YET_FULFILLED
        ])->count('material_group');
    }

    static function getList($request, $defaultOnly)
    {
        $data = self::query();
        $data = self::getDefaultWith($data);

        if (!$defaultOnly) {
            $data = self::withLogisticRequestData($data);
            $data = self::whereHasApplicant($data, $request);
            $data = self::whereStatusCondition($data, $request);
            $data = self::whereHasFaskes($data, $request);
            $data = self::whereHasAgency($data, $request);
        }

        return $data;
    }

    static function getDefaultWith($data)
    {
        return $data->with([
            'masterFaskes',
            'masterFaskesType',
            'city',
            'subDistrict',
            'village',
            'applicant' => function ($query) {
                $query->select([ 'id', 'agency_id', 'applicant_name', 'applicants_office', 'file', 'email', 'primary_phone_number', 'secondary_phone_number', 'verification_status', 'note', 'approval_status', 'approval_note', 'stock_checking_status', 'application_letter_number', 'verified_by', 'verified_at', 'approved_by', 'approved_at', DB::raw('concat(approval_status, "-", verification_status) as status'), DB::raw('concat(approval_status, "-", verification_status) as statusDetail'), 'finalized_by', 'finalized_at', 'is_urgency', 'is_integrated' ]);
                $query->where('is_deleted', '!=' , 1);
                $query->with('letter');
                $query->with('verifiedBy:id,name,agency_name,handphone');
                $query->with('approvedBy:id,name,agency_name,handphone');
                $query->with('finalizedBy:id,name,agency_name,handphone');
            }
        ]);
    }

    static function withLogisticRequestData($data)
    {
        return $data->with('logisticRequestItems:agency_id,product_id,brand,quantity,unit,usage,priority')
            ->with('logisticRequestItems.product:id,name,material_group_status,material_group')
            ->with('logisticRequestItems.masterUnit:id,unit as name')
            ->with([
                'recommendationItems' => function ($query) {
                    $query->whereNotIn('status', [
                        LogisticRealizationItems::STATUS_NOT_AVAILABLE,
                        LogisticRealizationItems::STATUS_NOT_YET_FULFILLED
                    ]);
                },
                'finalizationItems' => function ($query) {
                    $query->whereNotIn('final_status', [
                        LogisticRealizationItems::STATUS_NOT_AVAILABLE,
                        LogisticRealizationItems::STATUS_NOT_YET_FULFILLED
                    ]);
                }
            ]);
    }

    static function whereHasApplicant($data, $request)
    {
        return $data->whereHas('applicant', function ($query) use ($request) {
            $query->where('is_deleted', '!=' , 1);

            $query->when($request->input('source_data'), function ($query) use ($request) {
                $query->where('source_data', $request->input('source_data'));
            });

            $query->when($request->input('stock_checking_status'), function ($query) use ($request) {
                $query->where('stock_checking_status', $request->input('stock_checking_status'));
            });

            $query->when($request->input('start_date') && $request->input('end_date'), function ($query) use ($request) {
                $query->whereBetween('created_at', [$request->input('start_date'), $request->input('end_date')]);
            });

            $query->when($request->has('is_urgency'), function ($query) use ($request) {
                $query->where('is_urgency', $request->input('is_urgency'));
            });

            $query->when($request->has('is_integrated'), function ($query) use ($request) {
                $query->where('is_integrated', $request->input('is_integrated'));
            });

            $query->when($request->has('finalized_by'), function ($query) use ($request) {
                $query->when($request->input('finalized_by'), function ($query) {
                    $query->whereNotNull('finalized_by');
                }, function ($query) {
                    $query->whereNull('finalized_by');
                });
            });
        });
    }

    public function scopeFinal($query)
    {
        return $query->whereHas('applicant', function ($query) {
            $query->whereNotNull('finalized_by')->where('is_deleted', '!=' , 1);
        });
    }

    /**
     * Search Report Scope function
     *
     * Data search can be done by entering "search" in the form of the agency ID or Applicant Name
     *
     * @param $query
     * @param $request
     * @return $query
     */
    public function scopeSearchReport($query, $request)
    {
        $query->when($request->has('search'), function ($query) use ($request) {
            $query->where(function ($query) use ($request) {
                $query->where('agency.id', 'LIKE', "%{$request->input('search')}%")
                      ->orWhereHas('applicant', function ($query) use ($request) {
                        $query->where('applicant_name', 'LIKE', "%{$request->input('search')}%");
                });
            });
        });

        $query->when($request->input('start_date') && $request->input('end_date'), function ($query) use ($request) {
            $query->whereBetween('acceptance_reports.date', [$request->input('start_date'), $request->input('end_date')]);
        });

        $query->when($request->has('status'), function ($query) use ($request) {
            $query->when($request->input('status') == 1, function ($query) use ($request) {
                $query->has('acceptanceReport');
            }, function ($query) {
                $query->doesntHave('acceptanceReport');
            });
        });

        return $query;
    }

    static function whereStatusCondition($data, $request)
    {
        return $data->whereHas('applicant', function ($query) use ($request) {
            $query->when($request->input('is_rejected'), function ($query) {
                $query->where('verification_status', Applicant::STATUS_REJECTED)
                    ->orWhere('approval_status', Applicant::STATUS_REJECTED);
            }, function ($query) use ($request) {
                $query->when($request->input('verification_status'), function ($query) use ($request) {
                    $query->where('verification_status', $request->input('verification_status'));
                });

                $query->when($request->input('approval_status'), function ($query) use ($request) {
                    $query->where('approval_status', $request->input('approval_status'));
                });
            });
        });
    }

    static function whereHasFaskes($data, $request)
    {
        return $data->whereHas('masterFaskes', function ($query) use ($request) {
            $query->when($request->has('is_reference'), function ($query) use ($request) {
                $query->where('is_reference', '=', $request->is_reference);
            });
        });
    }

    static function whereHasAgency($data, $request)
    {
        $data->where(function ($query) use ($request) {
            $query->when($request->input('agency_name'), function ($query) use ($request) {
                $query->where('agency_name', 'LIKE', "%{$request->input('agency_name')}%");
            });

            $query->when($request->input('city_code'), function ($query) use ($request) {
                $query->where('location_district_code', $request->input('city_code'));
            });

            $query->when($request->input('faskes_type'), function ($query) use ($request) {
                $query->where('agency_type', $request->input('faskes_type'));
            });

            $query->when($request->has('completeness'), function ($query) use ($request) {
                $query->where('completeness', $request->input('completeness'));
            });
        });

        return $data;
    }

    public function masterFaskesType()
    {
        return $this->hasOne('App\MasterFaskesType', 'id', 'agency_type');
    }

    public function masterFaskes()
    {
        return $this->hasOne('App\MasterFaskes', 'id', 'master_faskes_id');
    }

    public function applicant()
    {
        return $this->hasOne('App\Applicant', 'agency_id', 'id');
    }

    public function AcceptanceReport()
    {
        return $this->hasOne('App\AcceptanceReport', 'agency_id', 'id');
    }

    public function letter()
    {
        return $this->hasOne('App\Letter', 'agency_id', 'id');
    }

    public function need()
    {
        return $this->hasMany('App\Needs', 'agency_id', 'id');
    }

    public function city()
    {
        return $this->belongsTo('App\City', 'location_district_code', 'kemendagri_kabupaten_kode');
    }

    public function village()
    {
        return $this->belongsTo('App\Village', 'location_village_code', 'kemendagri_desa_kode');
    }

    public function subDistrict()
    {
        return $this->belongsTo('App\Subdistrict', 'location_subdistrict_code', 'kemendagri_kecamatan_kode');
    }

    public function logisticRequestItems()
    {
        return $this->need();
    }

    public function logisticRealizationItems()
    {
        return $this->hasMany('App\LogisticRealizationItems', 'agency_id', 'id');
    }

    public function recommendationItems()
    {
        return $this->logisticRealizationItems();
    }

    public function finalizationItems()
    {
        return $this->logisticRealizationItems();
    }

    public function tracking()
    {
        return $this->hasOne('App\Applicant', 'agency_id', 'id');
    }
}
