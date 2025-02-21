@extends('layouts.app')

@section('content')
<div class="container mx-auto">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">Тип на леб</h1>
        <a href="{{ route('bread-types.create') }}" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
            Додади артикал
        </a>
    </div>

  

    <div class="bg-white rounded shadow overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr class="bg-gray-100">
                <th class="px-6 py-3 text-left">Шифра на производ</th>

                    <th class="px-6 py-3 text-left">Име</th>
                    <th class="px-6 py-3 text-left">Редовна цена</th>
                    <th class="px-6 py-3 text-left">Цена за продажба на вчерашен леб</th>
                    <th class="px-6 py-3 text-left">Продажба на стар леб</th>
                    <th class="px-6 py-3 text-left">Дали лебот е активен</th>
                    <th class="px-6 py-3 text-right">Промени или Избриши</th>
                </tr>
            </thead>
            <tbody>
                @forelse($breadTypes as $breadType)
                    <tr class="border-t hover:bg-gray-50">
                    <td class="px-6 py-4">{{ $breadType->code }}</td>

                        <td class="px-6 py-4">{{ $breadType->name }}</td>
                        <td class="px-6 py-4">
                            {{ rtrim(rtrim(number_format($breadType->price, 5), '0'), '.') }} ден.
                        </td>
                        <td class="px-6 py-4">
                            {{ rtrim(rtrim(number_format($breadType->old_price, 5), '0'), '.') }} ден.
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $breadType->available_for_daily ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $breadType->available_for_daily ? 'Да' : 'Не' }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $breadType->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $breadType->is_active ? 'Активен' : 'Не е активен' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end space-x-2">
                                <a href="{{ route('bread-types.edit', $breadType) }}" 
                                   class="text-blue-500 hover:bg-blue-100 px-3 py-1 rounded">
                                    Промени
                                </a>
                                <form action="{{ route('bread-types.destroy', $breadType) }}" 
                                      method="POST" 
                                      class="inline delete-form"
                                      data-bread-name="{{ $breadType->name }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="text-red-500 hover:bg-red-100 px-3 py-1 rounded delete-btn">
                                        Бриши
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            Нема внесено типови на леб
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const deleteForms = document.querySelectorAll('.delete-form');
    
    deleteForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const breadName = this.dataset.breadName;
            
            if (confirm(`Дали сте сигурни дека сакате да го избришете лебот "${breadName}"?`)) {
                this.submit();
            }
        });
    });
});
</script>
@endpush
@endsection