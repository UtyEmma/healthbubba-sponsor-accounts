<?php

namespace App\Enums;

enum NigeriaState: string
{
    case Abia = 'AB';
    case Adamawa = 'AD';
    case AkwaIbom = 'AK';
    case Anambra = 'AN';
    case Bauchi = 'BA';
    case Bayelsa = 'BY';
    case Benue = 'BE';
    case Borno = 'BO';
    case CrossRiver = 'CR';
    case Delta = 'DE';
    case Ebonyi = 'EB';
    case Edo = 'ED';
    case Ekiti = 'EK';
    case Enugu = 'EN';
    case Gombe = 'GO';
    case Imo = 'IM';
    case Jigawa = 'JI';
    case Kaduna = 'KD';
    case Kano = 'KN';
    case Katsina = 'KT';
    case Kebbi = 'KE';
    case Kogi = 'KO';
    case Kwara = 'KW';
    case Lagos = 'LA';
    case Nasarawa = 'NA';
    case Niger = 'NI';
    case Ogun = 'OG';
    case Ondo = 'ON';
    case Osun = 'OS';
    case Oyo = 'OY';
    case Plateau = 'PL';
    case Rivers = 'RI';
    case Sokoto = 'SO';
    case Taraba = 'TA';
    case Yobe = 'YO';
    case Zamfara = 'ZA';
    case FederalCapitalTerritory = 'FC';

    public function label(): string
    {
        return match ($this) {
            self::AkwaIbom => 'Akwa Ibom',
            self::CrossRiver => 'Cross River',
            self::FederalCapitalTerritory => 'Federal Capital Territory',
            default => str($this->name)->headline()->toString(),
        };
    }

    /** @return list<array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(
            static fn (self $state): array => [
                'value' => $state->value,
                'label' => $state->label(),
            ],
            self::cases(),
        );
    }
}
