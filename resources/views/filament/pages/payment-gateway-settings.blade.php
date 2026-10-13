<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">External payment gateways by account type</x-slot>
            <x-slot name="description">
                Select the provider used for newly created external payments from each account type. Wallet payments remain wallet-first, and existing pending payments and recurring mandates continue with their recorded provider.
            </x-slot>

            <div class="space-y-6">
                @foreach ($accountTypeOptions as $accountType => $accountTypeLabel)
                    <fieldset>
                        <legend class="mb-3 text-sm font-semibold text-gray-950 dark:text-white">
                            {{ $accountTypeLabel }}
                        </legend>

                        <div class="grid gap-4 md:grid-cols-2">
                            @foreach ($gatewayOptions as $value => $option)
                                <label @class([
                                    'cursor-pointer rounded-xl border p-4 transition',
                                    'border-primary-500 bg-primary-50 dark:bg-primary-950/30' => ($gateways[$accountType] ?? null) === $value,
                                    'border-gray-200 dark:border-white/10' => ($gateways[$accountType] ?? null) !== $value,
                                    'cursor-not-allowed opacity-60' => ! $option['configured'],
                                ])>
                                    <div class="flex items-start gap-3">
                                        <input
                                            type="radio"
                                            wire:model="gateways.{{ $accountType }}"
                                            value="{{ $value }}"
                                            @disabled(! $option['configured'])
                                            class="mt-1 border-gray-300 text-primary-600 focus:ring-primary-500"
                                        />
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center justify-between gap-3">
                                                <span class="font-semibold text-gray-950 dark:text-white">{{ $option['label'] }}</span>
                                                <x-filament::badge :color="$option['configured'] ? 'success' : 'danger'">
                                                    {{ $option['configured'] ? 'Configured' : 'Unavailable' }}
                                                </x-filament::badge>
                                            </div>

                                            @if (! $option['configured'])
                                                <p class="mt-2 text-sm text-danger-600 dark:text-danger-400">
                                                    Missing server configuration: {{ implode(' ', $option['issues']) }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>

                        @error("gateways.{$accountType}")
                            <p class="mt-3 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
                        @enderror
                    </fieldset>
                @endforeach
            </div>
        </x-filament::section>

        <div class="flex justify-end">
            <x-filament::button
                type="submit"
                wire:loading.attr="disabled"
                wire:confirm="Update the active payment gateway for each account type?"
            >
                Save gateways
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
