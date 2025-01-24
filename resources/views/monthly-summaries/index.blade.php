@extends('layouts.app')

@section('content')
<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4">Месечен преглед</h1>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($companies as $company)
        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-xl font-semibold mb-2">{{ $company->name }}</h2>
            <a href="{{ route('monthly-summary.show', $company) }}" 
               class="text-blue-500 hover:text-blue-700">
                Погледни месечен преглед
            </a>
        </div>
        @endforeach
    </div>
</div>
@endsection