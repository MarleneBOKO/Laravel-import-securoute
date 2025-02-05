<?php

namespace App\Http\Controllers;

use App\Imports\InsuranceImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use App\Models\Insurance;

class InsuranceController extends Controller
{
    public function index()
    {
        $insurances = Insurance::latest()->paginate(10);
        return view('insurances.index', compact('insurances'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        try {
            Excel::import(new InsuranceImport, $request->file('file'));
            return redirect()->route('insurances.index')
                ->with('success', 'Import réussi');
        } catch (\Exception $e) {
            return redirect()->route('insurances.index')
                ->with('error', 'Erreur lors de l\'import : ' . $e->getMessage());
        }
    }
}
