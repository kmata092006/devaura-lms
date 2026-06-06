<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReturnService;
use Illuminate\Http\Request;

class ReturnController extends Controller
{
    private ReturnService $returnService;

    public function __construct(ReturnService $returnService)
    {
        $this->returnService = $returnService;
    }

    public function index(Request $request)
    {
        $data = $this->returnService->getReturnPageData(
            $request->query('search')
        );

        return view('admin.returning.index', $data);
    }

    public function returnNormal(int $transactionId)
    {
        $this->returnService->returnBookNormally($transactionId);

        return redirect()
            ->route('admin.return.index')
            ->with('success', 'Book returned successfully. The book status is now Available.');
    }

    public function markLost(int $transactionId)
    {
        $this->returnService->markBookAsLost($transactionId);

        return redirect()
            ->route('admin.return.index')
            ->with('success', 'Book marked as Lost. The transaction has been closed.');
    }

    public function markDamaged(int $transactionId)
    {
        $this->returnService->markBookAsDamaged($transactionId);

        return redirect()
            ->route('admin.return.index')
            ->with('success', 'Book marked as Damaged. The transaction has been closed.');
    }
}