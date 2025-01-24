@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="bg-white shadow rounded-lg p-6">
        <h1 class="text-2xl font-bold mb-6">Фактури</h1>

        {{-- Export Form --}}
        <div class="mb-8">
            <form action="{{ route('invoice-companies.export') }}" method="POST" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Почетен датум</label>
                        <input type="date" name="start_date" required 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Краен датум</label>
                        <input type="date" name="end_date" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" 
                            class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Експортирај фактури
                    </button>
                </div>
            </form>
        </div>

        {{-- Error Message --}}
        @if(session('error'))
            <div class="mb-4 bg-red-50 border border-red-200 text-red-800 rounded-md p-4">
                {{ session('error') }}
            </div>
        @endif

        {{-- Success Message --}}
        @if(session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-800 rounded-md p-4">
                {{ session('success') }}
            </div>
        @endif

        {{-- Recent Export Jobs --}}
        @if(isset($exportJobs) && $exportJobs->isNotEmpty())
            <div class="mt-6">
                <h2 class="text-lg font-semibold mb-4">Последни експорти</h2>
                <div class="space-y-4">
                    @foreach($exportJobs as $job)
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <div class="flex justify-between items-center">
                                <div>
                                    <span class="font-medium">Статус: 
                                        @switch($job->status)
                                            @case('pending')
                                                <span class="text-yellow-600">Во ред за обработка</span>
                                                @break
                                            @case('processing')
                                                <span class="text-blue-600">Се процесира</span>
                                                @break
                                            @case('completed')
                                                <span class="text-green-600">Завршено</span>
                                                @break
                                            @case('failed')
                                                <span class="text-red-600">Неуспешно</span>
                                                @break
                                            @default
                                                <span class="text-gray-600">{{ $job->status }}</span>
                                        @endswitch
                                    </span>
                                    <br>
                                    <span class="text-sm text-gray-500">
                                        Креирано: {{ $job->created_at->format('d.m.Y H:i') }}
                                    </span>
                                </div>
                                <div>
                                    @if($job->status === 'completed')
                                        <a href="{{ route('invoice-companies.download', $job->id) }}" 
                                           class="text-blue-600 hover:text-blue-800">
                                            Преземи
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Companies List --}}
        <div class="mt-6">
            <h2 class="text-lg font-semibold mb-4">Достапни компании за фактурирање</h2>
            @if($invoiceCompanies->isEmpty())
                <p class="text-gray-500 italic">Нема компании за фактурирање.</p>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($invoiceCompanies as $company)
                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                            <h3 class="font-medium text-lg">{{ $company->name }}</h3>
                            <p class="text-sm text-gray-600">Код: {{ $company->code }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
