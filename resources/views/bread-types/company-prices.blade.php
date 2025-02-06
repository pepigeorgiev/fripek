@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-bold mb-6">Цени по компании за {{ $breadType->name }}</h2>

        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('bread-types.updateCompanyPrices', $breadType) }}" class="space-y-4">
            @csrf

            <div class="mb-4">
                <label for="valid_from" class="block text-gray-700 font-bold mb-2">Важи од датум</label>
                <input type="date" 
                       name="valid_from" 
                       id="valid_from" 
                       value="{{ old('valid_from', date('Y-m-d')) }}"
                       required
                       step="0.01"

                       min="{{ date('Y-m-d') }}"
                       class="w-full px-3 py-2 border rounded-lg">
            </div>

            <div class="grid grid-cols-1 gap-6">
                @foreach($companies as $company)
                <div class="border p-4 rounded-lg">
                    <h3 class="font-bold mb-4">{{ $company->name }}</h3>
                    <input type="hidden" name="companies[{{ $loop->index }}][company_id]" value="{{ $company->id }}">
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 mb-2">Цена</label>
                            <input type="number" 
                                   name="companies[{{ $loop->index }}][price]" 
                                   value="{{ old("companies.{$loop->index}.price", $company->pivot->price ?? $breadType->price) }}"
                                   step="0.01"
                                   min="0"
                                   required
                                   class="w-full px-3 py-2 border rounded-lg">
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 mb-2">Стара цена</label>
                            <input type="number" 
                                   name="companies[{{ $loop->index }}][old_price]" 
                                   value="{{ old("companies.{$loop->index}.old_price", $company->pivot->old_price ?? $breadType->old_price) }}"
                                   step="0.01"
                                   min="0"
                                   required
                                   class="w-full px-3 py-2 border rounded-lg">
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="flex justify-end mt-6">
                <a href="{{ route('bread-types.edit', $breadType) }}" 
                   class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 mr-2">
                    Назад
                </a>
                <button type="submit" 
                        class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                    Зачувај цени
                </button>
            </div>
        </form>

        @if($breadType->companies->isNotEmpty())
        <div class="mt-8">
            <h3 class="font-bold mb-4">Историја на цени по компании</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="px-4 py-2 text-left">Компанија</th>
                            <th class="px-4 py-2 text-right">Цена</th>
                            <th class="px-4 py-2 text-right">Стара цена</th>
                            <th class="px-4 py-2 text-left">Важи од</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($breadType->companies as $company)
                        <tr class="border-t">
                            <td class="px-4 py-2">{{ $company->name }}</td>
                            <td class="px-4 py-2 text-right">
                                @php
                                    $price = DB::table('bread_type_company')
                                        ->where('bread_type_id', $breadType->id)
                                        ->where('company_id', $company->id)
                                        ->orderBy('created_at', 'desc')
                                        ->value('price');
                                    \Log::info('Retrieved price from DB:', ['price' => $price]);
                                    echo $price;
                                @endphp
                            </td>
                            <td class="px-4 py-2 text-right">
                                @php
                                    $oldPrice = DB::table('bread_type_company')
                                        ->where('bread_type_id', $breadType->id)
                                        ->where('company_id', $company->id)
                                        ->orderBy('created_at', 'desc')
                                        ->value('old_price');
                                    \Log::info('Retrieved old price from DB:', ['old_price' => $oldPrice]);
                                    echo $oldPrice;
                                @endphp
                            </td>
                            <td class="px-4 py-2">{{ date('d.m.Y', strtotime($company->pivot->valid_from)) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection