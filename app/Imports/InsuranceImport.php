<?php
namespace App\Imports;

use App\Models\Insurance;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class InsuranceImport implements ToCollection, WithHeadingRow, WithStartRow
{
    private array $processedImmatriculations = [];
    private array $errors = [];
    private int $successCount = 0;
    private ?int $headerRowIndex = null;
    private array $headerColumns = [];

    private const EN_TETES_REQUISES = [
        'echeance' => 'echeance',
        'assure' => 'ASSURE',
        'immatriculation' => 'Immatriculation',
        'telephone' => 'Telephone'
    ];

    private const FORMATS_DATE = ['d/m/Y', 'Y-m-d', 'd-m-Y'];
    private const PREFIXE_TELEPHONE = '+229';
    private const MAX_HEADER_SEARCH_ROWS = 10;

    public function startRow(): int
    {
        return 1;
    }

    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            $this->ajouterErreur("Le fichier est vide");
            return;
        }

        if (!$this->findHeadersAndColumns($rows)) {
            return;
        }

        $dataRows = $rows->slice($this->headerRowIndex + 1);

        $this->traiterLignes($dataRows->filter(function ($row) {
            return !$this->estLigneVide($row);
        }));
    }

    private function findHeadersAndColumns(Collection $rows): bool
    {
        $required_headers = array_map('strtolower', array_values(self::EN_TETES_REQUISES));

        for ($rowIndex = 0; $rowIndex < min($rows->count(), self::MAX_HEADER_SEARCH_ROWS); $rowIndex++) {
            $row = $rows[$rowIndex];
            $this->headerColumns = [];

            foreach ($row as $colIndex => $cellValue) {
                if (empty($cellValue))
                    continue;

                $value = strtolower(trim((string) $cellValue));

                foreach (self::EN_TETES_REQUISES as $key => $header) {
                    if (strtolower($header) === $value) {
                        $this->headerColumns[$key] = $colIndex;
                        break;
                    }
                }
            }

            if (count($this->headerColumns) === count(self::EN_TETES_REQUISES)) {
                $this->headerRowIndex = $rowIndex;
                return true;
            }
        }

        $this->ajouterErreur("En-têtes requises non trouvées dans les " . self::MAX_HEADER_SEARCH_ROWS . " premières lignes");
        return false;
    }

    private function preparerDonnees($row): array
    {
        $donnees = [];

        foreach (self::EN_TETES_REQUISES as $key => $header) {
            if (isset($this->headerColumns[$key]) && isset($row[$this->headerColumns[$key]])) {
                $donnees[$key] = trim((string) $row[$this->headerColumns[$key]]);
            } else {
                $donnees[$key] = '';
            }
        }

        return $donnees;
    }

    private function traiterLignes(Collection $rows): void
    {
        DB::beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                if (count($row) < max($this->headerColumns)) {
                    continue;
                }

                $donnees = $this->preparerDonnees($row);

                if ($this->estLigneVide($donnees)) {
                    continue;
                }

                if (!$this->validerLigne($donnees, $index + $this->headerRowIndex + 2)) {
                    continue;
                }

                $this->traiterEnregistrement($donnees);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->ajouterErreur("Erreur lors de l'import : {$e->getMessage()}");
        }
    }

    private function validerLigne(array $ligne, int $numero_ligne): bool
    {
        if ($this->estLigneVide($ligne)) {
            return false;
        }

        $validateur = Validator::make($ligne, [
            'assure' => 'required|string|min:2|max:255',
            'telephone' => 'string',
            'echeance' => 'required|string',
            'immatriculation' => ['required', 'string', 'max:255', 'regex:/^[A-Z0-9\s-]+$/i']
        ]);

        if ($validateur->fails()) {
            $this->ajouterErreur(sprintf(
                "Ligne %d invalide : %s",
                $numero_ligne,
                implode(', ', $validateur->errors()->all())
            ));
            return false;
        }

        try {
            $this->transformerDate($ligne['echeance']);
        } catch (\Exception $e) {
            $this->ajouterErreur("Ligne {$numero_ligne} : Format de date invalide ({$ligne['echeance']})");
            return false;
        }

        return true;
    }

    private function estNumeroTelephoneValide(string $telephone): bool
    {
        $chiffres = preg_replace('/[^0-9]/', '', $telephone);
        return strlen($chiffres) >= 8 && strlen($chiffres) <= 11;
    }

    private function estLigneVide($ligne): bool
    {
        if (is_array($ligne)) {
            return empty(array_filter($ligne, fn($valeur) => !empty($valeur) || $valeur === '0'));
        }
        return empty(array_filter($ligne->toArray(), fn($valeur) => !empty($valeur) || $valeur === '0'));
    }

    private function traiterEnregistrement(array $donnees): void
    {
        $immatriculation = strtoupper(trim($donnees['immatriculation']));

        if (in_array($immatriculation, $this->processedImmatriculations)) {
            $this->ajouterErreur("Doublon détecté pour l'immatriculation : $immatriculation");
            return;
        }

        $assurance = Insurance::firstOrNew(['immatriculation' => $immatriculation]);

        $telephone = trim($donnees['telephone']);

        if (
            empty($telephone) ||
            $telephone === '(229) 00000000' ||
            $telephone === '(229)(229) 00000000' ||
            !$this->estNumeroTelephoneValide($telephone)
        ) {
            $telephone = '';
        } else {
            $telephone = $this->formaterNumeroTelephone($telephone);
        }

        $assurance->fill([
            'assure' => $donnees['assure'],
            'telephone' => $telephone,
            'echeance' => $this->transformerDate($donnees['echeance']),
            'sync_status' => 'pending',
            'sync_message' => null
        ]);
        $assurance->save();

        $this->processedImmatriculations[] = $immatriculation;
        $this->successCount++;
    }

    private function formaterNumeroTelephone(string $telephone): string
    {
        $chiffres = preg_replace('/[^0-9]/', '', $telephone);

        if (strpos($chiffres, '229') === 0) {
            $chiffres = substr($chiffres, 3);
        }

        if (strlen($chiffres) === 8) {
            return sprintf(
                '+229 %s %s %s %s',
                substr($chiffres, 0, 2),
                substr($chiffres, 2, 2),
                substr($chiffres, 4, 2),
                substr($chiffres, 6, 2)
            );
        }

        return '+229 ' . $chiffres;
    }

    private function transformerDate($valeur): Carbon
    {
        if (empty($valeur)) {
            throw new \Exception("Date vide");
        }

        if (is_numeric($valeur)) {
            return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($valeur));
        }

        foreach (self::FORMATS_DATE as $format) {
            try {
                return Carbon::createFromFormat($format, $valeur);
            } catch (\Exception $e) {
                continue;
            }
        }

        throw new \Exception("Format de date non reconnu");
    }

    private function ajouterErreur(string $message): void
    {
        $this->errors[] = $message;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getSuccessCount(): int
    {
        return $this->successCount;
    }
}
