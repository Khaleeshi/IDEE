<div class="seller">
    <strong>{{ config('idee.company.name') }}</strong>
    @if (config('idee.company.address'))<br>{{ config('idee.company.address') }}@endif
    @if (config('idee.company.rccm'))<br>RCCM {{ config('idee.company.rccm') }}@endif
    @if (config('idee.company.ninea'))<br>NINEA {{ config('idee.company.ninea') }}@endif
</div>
