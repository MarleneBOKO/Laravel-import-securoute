<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use App\Models\Insurance;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;
class InsuranceImport implements ToCollection
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
        //
    }

    public function model(array $row)
    {
        return new Insurance([
            'assure' => $row['assure'],
            'telephone' => $this->formatPhoneNumber($row['telephone']),
            'echeance' => $this->transformDate($row['echeance']),
            'immatriculation' => $row['immatriculation'],
            'sync_status' => 'pending'
        ]);
    }

    private function formatPhoneNumber($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) === 8) {
            return '+229 ' . substr($phone, 0, 2) . ' ' . substr($phone, 2, 2) . ' ' .
                substr($phone, 4, 2) . ' ' . substr($phone, 6, 2);
        }
        return $phone;
    }

    private function transformDate($value)
    {
        if (is_numeric($value)) {
            return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value))
                ->format('Y-m-d');
        }
        return Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d');
    }
}
