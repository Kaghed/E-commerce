<?php

namespace App\Http\Controllers;
use App\Http\Requests\CreateReportRequest;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    
    public function __construct(private ReportService $reportService) {}



   // admin _ reports _ product
   
    public function productReports(Request $request){
              $request->validate([
            'status' => 'required|string|in:pending,accepted',
        ]);

        return response()->json($this->reportService->ProductReports($request));
    }

    

    public function dismissProductReport($reportId) {

        return $this->reportService->dismissProductReport($reportId);
    }

  

    public function deleteReportedProduct($reportId) {

        return $this->reportService->deleteReportedProduct($reportId);
    }

  

// admin _ reports _ order

    public function OrderReports(Request $request){
              $request->validate([
            'status' => 'required|string|in:pending,accepted',
        ]);
        return response()->json($this->reportService->OrderReports($request));
    }


    public function accept($reportId) {

        $result = $this->reportService->acceptReport($reportId);

        return response()->json([
            'message' => 'Report accepted. Order cancelled and buyer refunded.',
            'data'    => $result,
        ]);
    }

   

    public function reject($reportId) {

        $result = $this->reportService->rejectReport($reportId);
    
        return response()->json([
            'message' => 'Report rejected. Order completed and seller paid.',
            'data'    => $result,
        ]);
    }



// customer _ reports 

    public function reportOrder(CreateReportRequest $request, $orderId) {

        $result = $this->reportService->reportOrder($request, $orderId);

        return response()->json([
            'message' => 'Report submitted successfully.',
            'data'    => $result,
        ], 201);
    }

  

    public function reportProduct(CreateReportRequest $request, $productId) {

        $result = $this->reportService->reportProduct($request, $productId);

        return response()->json([
            'message' => 'Report submitted successfully.',
            'data'    => $result,
        ], 201);
    }


  
}
