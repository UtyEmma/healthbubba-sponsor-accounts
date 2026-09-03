<?php

namespace App\Http\Requests\Institutional;

use App\Enums\InstitutionalReportFormat;
use App\Enums\InstitutionalReportType;
use App\Http\Requests\InstitutionalOnboarding\AuthorizedInstitutionalWorkspaceRequest;
use Illuminate\Validation\Rule;

final class ExportInstitutionalReportRequest extends AuthorizedInstitutionalWorkspaceRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'report' => ['required', Rule::enum(InstitutionalReportType::class)],
            'format' => ['required', Rule::enum(InstitutionalReportFormat::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'report' => $this->route('report'),
            'format' => $this->route('format'),
        ]);
    }

    public function reportType(): InstitutionalReportType
    {
        return InstitutionalReportType::from((string) $this->validated('report'));
    }

    public function reportFormat(): InstitutionalReportFormat
    {
        return InstitutionalReportFormat::from((string) $this->validated('format'));
    }
}
