@props(['type', 'id', 'label' => 'Signaler'])

{{-- Signalement en deux gestes : un motif, puis envoyer (CF-04). --}}
<details {{ $attributes->merge(['class' => 'mt-4']) }}>
    <summary class="cursor-pointer py-2 text-sm text-stone-500 underline">{{ $label }}</summary>
    <form method="POST" action="{{ route('reports.store') }}" class="mt-2 rounded-xl border border-stone-200 bg-white p-3 text-sm">
        @csrf
        <input type="hidden" name="target_type" value="{{ $type }}">
        <input type="hidden" name="target_id" value="{{ $id }}">
        <fieldset class="space-y-1">
            <legend class="mb-1 font-medium">Motif</legend>
            @foreach (\App\Enums\ReportReason::cases() as $reason)
                <label class="flex min-h-10 items-center gap-2">
                    <input type="radio" name="reason" value="{{ $reason->value }}" required class="h-5 w-5 accent-brand-700">
                    <span>{{ $reason->label() }}</span>
                </label>
            @endforeach
        </fieldset>
        <label for="details-{{ $type }}-{{ $id }}" class="mt-2 block text-stone-600">Précisions (facultatif)</label>
        <textarea id="details-{{ $type }}-{{ $id }}" name="details" rows="2" maxlength="1000"
                  class="mt-1 block w-full rounded-xl border border-stone-300 px-3 py-2 text-base"></textarea>
        <x-button variant="danger" class="mt-2 w-full">Envoyer le signalement</x-button>
    </form>
</details>
