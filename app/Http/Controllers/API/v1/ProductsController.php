<?php

namespace App\Http\Controllers\API\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Http\Controllers\Controller;
use App\Product;
use App\Applicant;
use App\Needs;
use DB;

class ProductsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $query = Product::where('products.is_imported', false)
            ->where('products.material_group_status', 1)
            ->where(function ($query) use ($request) {
                if ($request->filled('limit')) {
                    $query->paginate($request->input('limit'));
                }

                if ($request->filled('name')) {
                    $query->where('products.name', 'LIKE', "%{$request->input('name')}%");
                }

                if ($request->filled('user_filter')) {
                    $query->where('products.user_filter', '=', $request->input('user_filter'));
                }
            })
            ->orderBy('products.sort', 'ASC')->orderBy('products.name', 'ASC');
        } catch (\Exception $exception) {
            return response()->format(400, $exception->getMessage());
        }

        return response()->format(200, 'success', $query->get());
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return Product::where('id', $id)->firstOrFail();
    }

    public function productUnit($id)
    {
        $data = Product::select(
                'products.id',
                'products.name',
                DB::raw('IFNULL(product_unit.unit_id, 1) as unit_id'),
                DB::raw('IFNULL(master_unit.unit, "PCS") as unit')
            )
            ->leftJoin('product_unit', 'product_unit.product_id', '=', 'products.id')
            ->leftJoin('master_unit', function ($join) {
                $join->on('product_unit.unit_id', '=', 'master_unit.id')
                    ->where('master_unit.is_imported', false);
            })
            ->where('products.id', $id)
            ->get();

        return $data;
    }

    public function productRequest(Request $request)
    {
        $query = Product::query()
                ->withCount(['need as total_request' => function($query) use ($request) {
                    $query->select(DB::raw('sum(quantity)'))->filterByApplicant($request);
                }])
                ->orderBy('total_request', $request->input('sort', 'desc'));

        if ($request->has('limit')) {
            $data = $query->paginate($request->input('limit'));
        } else {
            $data = [
                'data' => $query->get(),
                'total' => $query->get()->count()
            ];
        }

        return response()->format(Response::HTTP_OK, 'success', $data);
    }

    /**
     * productTopRequest function
     *
     * to get top 1 requested product
     *
     * @param Request $request
     * @return void
     */
    public function productTopRequest(Request $request)
    {
        $data = [
            'total_items' => Needs::filterByApplicant($request)->sum('quantity'),
            'total_max' => Product::query()
                                    ->withCount(['need as total' => function($query) use ($request) {
                                        $query->select(DB::raw('sum(quantity)'))->filterByApplicant($request);
                                    }])
                                    ->orderBy('total', 'desc')
                                    ->orderBy('name')
                                    ->first()
        ];
        return response()->format(Response::HTTP_OK, 'success', $data);
    }
}
