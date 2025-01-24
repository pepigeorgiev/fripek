<div class="flex items-center gap-4">
    <form action="{{ route('summary.index') }}" method="GET" class="flex items-center gap-2">
        @if(request()->has('user_id'))
            <input type="hidden" name="user_id" value="{{ request('user_id') }}">
        @endif
        
        <select 
            name="date" 
            id="date" 
            class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
            onchange="this.form.submit()"
        >
            @foreach($availableDates as $date)
                <option 
                    value="{{ $date }}" 
                    {{ request('date', now()->toDateString()) == $date ? 'selected' : '' }}
                >
                    {{ \Carbon\Carbon::parse($date)->format('d.m.Y') }}
                </option>
            @endforeach
        </select>
    </form>
</div>