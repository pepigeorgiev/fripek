@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-2xl font-bold mb-4">Потврди бришење</h2>
        
        <p class="mb-4">Дали си сигурен за бришење на компанијата {{ $company->name }}?</p>
        
        <div class="flex space-x-4">
            <form action="{{ route('companies.destroy', $company) }}" method="POST">
                @csrf
                @method('DELETE')
                <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                    Бриши компанија
                </button>
            </form>
            
            <a href="{{ route('companies.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                Врати се назад
            </a>
        </div>
    </div>
</div>
@endsection