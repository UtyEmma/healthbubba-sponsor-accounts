@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}">
<img src="{{ config('mail.brand.logo_url') }}" width="48" height="47" alt="{{ config('mail.brand.name') }} logo">
<br>
{{ config('mail.brand.name') }}
<br>
<small>{{ config('mail.brand.tagline') }}</small>
</a>
</td>
</tr>
