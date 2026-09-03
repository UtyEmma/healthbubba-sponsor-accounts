<?php

namespace App\Http\Requests\Auth;

use App\DTOs\Auth\StoreOwnedWorkspaceData;
use App\Enums\AccountTypes;
use App\Enums\InstitutionalOrganizationType;
use App\Enums\NigeriaState;
use App\Models\User;
use App\ValueObjects\NigeriaPhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreOwnedWorkspaceRequest extends FormRequest
{
    public const SESSION_KEY = 'account_setup';

    public function authorize(): bool
    {
        $user = $this->user();
        $intent = $this->session()->get(self::SESSION_KEY);

        return $user instanceof User
            && is_array($intent)
            && (int) ($intent['user_id'] ?? 0) === $user->getKey()
            && (string) ($intent['account_type'] ?? '') === $this->input('account_type')
            && (int) ($intent['verified_at'] ?? 0) >= now()->subMinutes(15)->timestamp;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $accountType = AccountTypes::tryFrom((string) $this->input('account_type'));
        $isOrganization = in_array($accountType, [AccountTypes::BUSINESS, AccountTypes::INSTITUTION], true);
        $isInstitution = $accountType === AccountTypes::INSTITUTION;

        return [
            'account_type' => ['required', Rule::enum(AccountTypes::class)],
            'organization_name' => [Rule::requiredIf($isOrganization), 'nullable', 'string', 'max:255'],
            'organization_type' => [Rule::requiredIf($isInstitution), 'nullable', Rule::enum(InstitutionalOrganizationType::class)],
            'country_code' => [Rule::requiredIf($isInstitution), 'nullable', Rule::in(['NG'])],
            'state_code' => [Rule::requiredIf($isInstitution), 'nullable', Rule::enum(NigeriaState::class)],
            'official_email' => [Rule::requiredIf($isInstitution), 'nullable', 'email', 'max:255'],
            'official_phone' => [Rule::requiredIf($isInstitution), 'nullable', 'regex:/^\+234\d{10}$/'],
            'authorization_confirmed' => [$isInstitution ? 'accepted' : 'nullable'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'organization_name' => $this->string('organization_name')->squish()->toString() ?: null,
            'official_email' => $this->string('official_email')->lower()->trim()->toString() ?: null,
            'official_phone' => $this->filled('official_phone')
                ? NigeriaPhoneNumber::normalize($this->string('official_phone')->toString())
                : null,
        ]);
    }

    public function workspaceData(): StoreOwnedWorkspaceData
    {
        $accountType = AccountTypes::from((string) $this->validated('account_type'));

        return new StoreOwnedWorkspaceData(
            accountType: $accountType,
            organizationName: $this->validated('organization_name'),
            organizationType: $accountType === AccountTypes::INSTITUTION
                ? InstitutionalOrganizationType::from((string) $this->validated('organization_type'))
                : null,
            countryCode: $accountType === AccountTypes::INSTITUTION
                ? (string) $this->validated('country_code')
                : null,
            state: $accountType === AccountTypes::INSTITUTION
                ? NigeriaState::from((string) $this->validated('state_code'))
                : null,
            officialEmail: $accountType === AccountTypes::INSTITUTION
                ? $this->validated('official_email')
                : null,
            officialPhone: $accountType === AccountTypes::INSTITUTION
                ? $this->validated('official_phone')
                : null,
            authorizationConfirmed: $accountType === AccountTypes::INSTITUTION,
        );
    }
}
