<?php

namespace App\Services\WorkspaceBeneficiaries;

use App\DTOs\WorkspaceBeneficiaries\ImportRowError;
use App\DTOs\WorkspaceBeneficiaries\InviteWorkspaceBeneficiaryData;
use App\DTOs\WorkspaceBeneficiaries\ParsedEmployeeImport;
use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiarySource;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use Throwable;

final class EmployeeImportReader
{
    private const REQUIRED_HEADERS = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'department',
    ];

    public function read(UploadedFile $file): ParsedEmployeeImport
    {
        $reader = $this->readerFor($file);

        try {
            $reader->open($file->getRealPath());

            return $this->readFirstSheet($reader);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'file' => 'The uploaded spreadsheet could not be read. Please verify the file and try again.',
            ]);
        } finally {
            $reader->close();
        }
    }

    private function readFirstSheet(CsvReader|XlsxReader $reader): ParsedEmployeeImport
    {
        foreach ($reader->getSheetIterator() as $sheet) {
            $headers = null;
            $rows = [];
            $errors = [];
            $seenEmails = [];
            $seenEmployeeIds = [];

            foreach ($sheet->getRowIterator() as $offset => $row) {
                $rowNumber = $offset;
                $values = array_map($this->stringValue(...), $row->toArray());

                if ($headers === null) {
                    if ($this->isBlank($values)) {
                        continue;
                    }

                    $headers = array_map(
                        static fn (string $header): string => Str::snake(mb_strtolower(trim($header))),
                        $values,
                    );
                    $missing = array_values(array_diff(self::REQUIRED_HEADERS, $headers));

                    if ($missing !== []) {
                        throw ValidationException::withMessages([
                            'file' => 'Missing required columns: '.implode(', ', $missing).'.',
                        ]);
                    }

                    continue;
                }

                if ($this->isBlank($values)) {
                    continue;
                }

                $values = array_pad($values, count($headers), '');
                $data = array_combine($headers, array_slice($values, 0, count($headers)));

                $data['email'] = mb_strtolower(trim((string) ($data['email'] ?? '')));
                $data['employee_id'] = filled($data['employee_id'] ?? null)
                    ? mb_strtoupper(trim((string) $data['employee_id']))
                    : null;

                $validator = Validator::make($data, [
                    'first_name' => ['required', 'string', 'max:100'],
                    'last_name' => ['required', 'string', 'max:100'],
                    'email' => ['required', 'email:rfc', 'max:255'],
                    'phone' => ['required', 'string', 'max:32'],
                    'department' => ['required', 'string', 'max:120'],
                    'employee_id' => ['nullable', 'string', 'max:32', 'regex:/^[A-Za-z0-9_-]+$/'],
                ]);

                $rowErrors = array_values($validator->errors()->all());

                if (isset($seenEmails[$data['email']])) {
                    $rowErrors[] = "The email duplicates row {$seenEmails[$data['email']]}.";
                }

                if ($data['employee_id'] !== null && isset($seenEmployeeIds[$data['employee_id']])) {
                    $rowErrors[] = "The employee ID duplicates row {$seenEmployeeIds[$data['employee_id']]}.";
                }

                if ($rowErrors !== []) {
                    $errors[] = new ImportRowError($rowNumber, $rowErrors);

                    continue;
                }

                $seenEmails[$data['email']] = $rowNumber;

                if ($data['employee_id'] !== null) {
                    $seenEmployeeIds[$data['employee_id']] = $rowNumber;
                }

                $rows[] = [
                    'row' => $rowNumber,
                    'data' => InviteWorkspaceBeneficiaryData::fromArray(
                        $validator->validated(),
                        WorkspaceBeneficiarySource::Import,
                    ),
                ];
            }

            if ($headers === null) {
                throw ValidationException::withMessages(['file' => 'The uploaded spreadsheet is empty.']);
            }

            return new ParsedEmployeeImport($rows, $errors);
        }

        throw ValidationException::withMessages(['file' => 'The uploaded spreadsheet does not contain a worksheet.']);
    }

    private function readerFor(UploadedFile $file): CsvReader|XlsxReader
    {
        return mb_strtolower($file->getClientOriginalExtension()) === 'csv'
            ? new CsvReader
            : new XlsxReader;
    }

    private function stringValue(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }

    /** @param list<string> $values */
    private function isBlank(array $values): bool
    {
        return collect($values)->every(static fn (string $value): bool => $value === '');
    }
}
