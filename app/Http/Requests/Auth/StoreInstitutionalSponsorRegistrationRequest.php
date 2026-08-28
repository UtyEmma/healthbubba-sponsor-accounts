<?php

namespace App\Http\Requests\Auth;

use App\Actions\Fortify\PasswordValidationRules;
use App\DTOs\InstitutionalRegistration\InstitutionalSponsorRegistrationData;
use App\Enums\InstitutionalOrganizationType;
use App\Enums\NigeriaState;
use App\Models\User;
use App\ValueObjects\NigeriaPhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreInstitutionalSponsorRegistrationRequest extends FormRequest
{
    use PasswordValidationRules;

    public function authorize(): bool
    {
        return $this->user() === null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'organization_name' => ['required', 'string', 'max:255'],
            'organization_type' => ['required', Rule::enum(InstitutionalOrganizationType::class)],
            'country_code' => ['required', Rule::in(['NG'])],
            'state_code' => ['required', Rule::enum(NigeriaState::class)],
            'official_email' => ['required', 'email', 'max:255'],
            'official_phone' => ['required', 'regex:/^\+234\d{10}$/'],
            'name' => ['required', 'string', 'max:255'],
            'job_title' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class)],
            'phone' => ['required', 'regex:/^\+234\d{10}$/', Rule::unique(User::class)],
            'password' => [...$this->passwordRules(), 'confirmed'],
            'authorization_confirmed' => ['accepted'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'organization_name' => $this->string('organization_name')->squish()->toString(),
            'official_email' => $this->string('official_email')->lower()->trim()->toString(),
            'official_phone' => NigeriaPhoneNumber::normalize($this->string('official_phone')->toString()),
            'name' => $this->string('name')->squish()->toString(),
            'job_title' => $this->string('job_title')->squish()->toString(),
            'email' => $this->string('email')->lower()->trim()->toString(),
            'phone' => NigeriaPhoneNumber::normalize($this->string('phone')->toString()),
        ]);
    }

    public function registrationData(): InstitutionalSponsorRegistrationData
    {
        return new InstitutionalSponsorRegistrationData(
            organizationName: (string) $this->validated('organization_name'),
            organizationType: InstitutionalOrganizationType::from((string) $this->validated('organization_type')),
            countryCode: (string) $this->validated('country_code'),
            state: NigeriaState::from((string) $this->validated('state_code')),
            officialEmail: (string) $this->validated('official_email'),
            officialPhone: (string) $this->validated('official_phone'),
            ownerName: (string) $this->validated('name'),
            jobTitle: (string) $this->validated('job_title'),
            ownerEmail: (string) $this->validated('email'),
            ownerPhone: (string) $this->validated('phone'),
            password: (string) $this->validated('password'),
        );
    }
}
