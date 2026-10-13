<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">External payment gateways by account type</x-slot>
            <x-slot name="description">
                Select the provider used for newly created external payments from each account type. Wallet payments remain wallet-first, and existing pending payments and recurring mandates continue with their recorded provider.
            </x-slot>

            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($accountTypeOptions as $accountType => $accountTypeLabel)
                    <fieldset class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
                        <legend class="px-1 text-sm font-semibold text-gray-950 dark:text-white">
                            {{ $accountTypeLabel }}
                        </legend>

                        <div class="mt-2">
                            <x-filament::input.wrapper>
                                <x-filament::input.select
                                    wire:model.live="gateways.{{ $accountType }}"
                                    aria-label="Payment gateway for {{ $accountTypeLabel }} accounts"
                                >
                                    @foreach ($gatewayOptions as $value => $option)
                                        <option value="{{ $value }}" @disabled(! $option['configured'])>
                                            {{ $option['label'] }}{{ $option['configured'] ? '' : ' — unavailable' }}
                                        </option>
                                    @endforeach
                                </x-filament::input.select>
                            </x-filament::input.wrapper>
                        </div>

                        <div class="mt-3 space-y-2">
                            @foreach ($gatewayOptions as $value => $option)
                                @if (($gateways[$accountType] ?? null) === $value)
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ $option['label'] }} configuration
                                        </p>
                                        <x-filament::badge :color="$option['configured'] ? 'success' : 'danger'">
                                            {{ $option['configured'] ? 'Configured' : 'Unavailable' }}
                                        </x-filament::badge>
                                    </div>

                                    @if (! $option['configured'])
                                        <p class="text-sm text-danger-600 dark:text-danger-400">
                                            Missing server configuration: {{ implode(' ', $option['issues']) }}
                                        </p>
                                    @endif
                                @endif
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
