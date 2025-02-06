<?php

namespace App\Exports;

use App\Models\Insurance;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InsuranceExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    protected $insurance;

    public function __construct($insurance)
    {
        $this->insurance = collect($insurance);
    }

    public function collection()
    {
        return $this->insurance;
    }

    public function headings(): array
    {
        return [
            'Immatriculation',
            'Échéance',
            "Nom de l'assuré",
            "Téléphone de l'assuré",
        ];
    }

    public function map($insurance): array
    {
        return [
            $insurance->immatriculation,
            $insurance->echeance->format('d/m/Y'),
            $insurance->assure,
            $insurance->telephone,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:D1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => 'solid',
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => 'center',
                'vertical' => 'center',
            ],
        ]);

        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle("A2:D$lastRow")->applyFromArray([
            'fill' => [
                'fillType' => 'solid',
                'startColor' => ['rgb' => 'D9E2F3'],
            ],
            'font' => [
                'color' => ['rgb' => '000000'],
            ],
            'alignment' => [
                'vertical' => 'center',
            ],
        ]);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 15,
            'C' => 30,
            'D' => 20,
        ];
    }
}
