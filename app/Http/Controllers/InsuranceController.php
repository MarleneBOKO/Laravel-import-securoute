<?php

namespace App\Http\Controllers;

use App\Exports\InsuranceExport;
use App\Imports\InsuranceImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use App\Models\Insurance;
use App\Services\SecurouteApiService;

class InsuranceController extends Controller
{
    public function index(Request $request)
    {
        $query = Insurance::query();

        // Filtrage par assuré ou immatriculation
        if ($request->has('search') && $request->search != '') {
            $query->where(function ($q) use ($request) {
                $q->where('assure', 'like', '%' . $request->search . '%')
                    ->orWhere('immatriculation', 'like', '%' . $request->search . '%');
            });
        }

        $insurances = $query->latest()->paginate(10);
        $importErrors = session('import_errors', []);
        return view('insurances.index', compact('insurances', 'importErrors'));
    }


    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        try {
            $import = new InsuranceImport();
            Excel::import($import, $request->file('file'));

            $errors = $import->getErrors();
            if (count($errors) > 0) {
                return redirect()->route('insurances.index')
                    ->with('warning', 'Import terminé avec des erreurs')
                    ->with('import_errors', $errors);
            }

            return redirect()->route('insurances.index')
                ->with('success', 'Import réussi');
        } catch (\Exception $e) {
            return redirect()->route('insurances.index')
                ->with('error', 'Erreur lors de l\'import : ' . $e->getMessage());
        }
    }

    public function exportSingle($id)
    {
        $insurance = Insurance::where('id', $id)->get();

        return Excel::download(new InsuranceExport($insurance), 'assurance_' . $insurance->first()->immatriculation . '.xlsx');
    }

    public function sync(SecurouteApiService $apiService)
    {
        try {
            $result = $apiService->syncPendingInsurances();

            $message = "Synchronisation terminée. ";
            $message .= "Total: {$result['total']}, ";
            $message .= "Succès: {$result['success']}, ";
            $message .= "Échecs: {$result['failed']}, ";
            $message .= "Déjà assurés: {$result['already_insured']}";

            if ($result['total'] > ($result['success'] + $result['failed'])) {
                $message .= "\nLa synchronisation n'est pas complète. Veuillez la relancer.";
                return redirect()->route('insurances.index')
                    ->with('warning', $message);
            }

            return redirect()->route('insurances.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            return redirect()->route('insurances.index')
                ->with('error', 'Erreur lors de la synchronisation: ' . $e->getMessage());
        }
    }

}
