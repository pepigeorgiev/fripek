@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6">
    <div class="bg-white shadow-lg rounded-2xl overflow-hidden">
        <div class="bg-gradient-to-r from-blue-500 to-indigo-600 px-6 py-4 text-white flex justify-between items-center">
            <h2 class="text-lg font-semibold flex items-center gap-2">
                <i class="fas fa-bread-slice"></i>
                Управување со лебови за: {{ $company->name }}
            </h2>
            <a href="{{ route('companies.index') }}" class="text-sm bg-white text-blue-600 px-3 py-1.5 rounded hover:bg-blue-50 transition flex items-center gap-1">
                <i class="fas fa-arrow-left"></i> Назад
            </a>
        </div>

        <div class="p-6">
            <form method="POST" action="{{ route('companies.update-bread-types', $company) }}">
                @csrf
                @method('PUT')

                <!-- Info Alert -->
                <div class="bg-blue-50 border border-blue-200 text-blue-800 p-4 rounded-lg mb-6 flex gap-4 items-start">
                    <i class="fas fa-info-circle text-blue-500 text-xl mt-1"></i>
                    <div>
                        <h4 class="font-semibold mb-1">Упатство за користење</h4>
                        <p class="text-sm">Изберете ги лебовите кои ги користи оваа компанија. Само избраните лебови ќе се прикажуваат при внесување на дневни трансакции.</p>
                    </div>
                </div>

                <!-- Bread Types Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                    @foreach($allBreadTypes as $breadType)
                        <label for="bread_type_{{ $breadType->id }}" class="cursor-pointer border rounded-lg p-4 flex items-center justify-between hover:bg-gray-50 transition group">
                            <div class="flex items-center space-x-3">
                                <input 
                                    type="checkbox" 
                                    id="bread_type_{{ $breadType->id }}" 
                                    name="bread_types[]" 
                                    value="{{ $breadType->id }}"
                                    class="form-checkbox h-5 w-5 text-blue-600"
                                    {{ in_array($breadType->id, $companyBreadTypeIds) ? 'checked' : '' }}
                                >
                                <span class="text-gray-800 font-medium group-hover:text-blue-600">
                                    {{ $breadType->name }}
                                </span>
                            </div>
                            <span class="text-xs bg-gray-200 text-gray-700 px-2 py-1 rounded">
                                {{ $breadType->code }}
                            </span>
                        </label>
                    @endforeach
                </div>

                <!-- Actions -->
                <div class="flex justify-between items-center mt-8">
                    <div class="space-x-2">
                        <button type="button" id="selectAll" class="text-sm px-4 py-2 rounded border border-gray-300 hover:bg-gray-100 transition">
                            <i class="fas fa-check-square mr-1"></i> Избери сите
                        </button>
                        <button type="button" id="deselectAll" class="text-sm px-4 py-2 rounded border border-gray-300 hover:bg-gray-100 transition">
                            <i class="fas fa-square mr-1"></i> Поништи избор
                        </button>
                    </div>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-6 py-2 rounded shadow transition">
                        <i class="fas fa-save mr-1"></i> Зачувај промени
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectAllBtn = document.getElementById('selectAll');
    const deselectAllBtn = document.getElementById('deselectAll');
    const checkboxes = document.querySelectorAll('input[name="bread_types[]"]');

    selectAllBtn?.addEventListener('click', () => {
        checkboxes.forEach(cb => cb.checked = true);
    });

    deselectAllBtn?.addEventListener('click', () => {
        checkboxes.forEach(cb => cb.checked = false);
    });
});
</script>
@endsection
