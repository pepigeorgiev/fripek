@extends('layouts.app')

@section('content')
<div class="bg-white p-6 rounded-lg shadow-lg">
    <h2 class="text-2xl font-bold mb-6">Управување со корисници</h2>

    <!-- Create User Form -->
    <div class="mb-8">
        <h3 class="text-xl font-semibold mb-4">Креирај нов корисник</h3>
        <form action="{{ route('users.store') }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700">Име</label>
                    <input type="text" name="name" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                
                <div>
                    <label class="block text-gray-700">Email</label>
                    <input type="email" name="email" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                
                <div>
                    <label class="block text-gray-700">Лозинка</label>
                    <input type="text" name="password" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                
                <div>
                    <label class="block text-gray-700">Улога</label>
                    <select name="role" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="user">Корисник</option>
                        <option value="admin-user">Администратор</option>
                    </select>
                </div>
            </div>
            
            <button type="submit"
                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Креирај корисник
            </button>
        </form>
    </div>


    @if (session('new_user_password'))
    <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-4">
        <p class="font-bold">Креиран е нов корисник со:</p>
        <p>Емаил: {{ session('new_user_email') }}</p>
        <p>Лозинка: {{ session('new_user_password') }}</p>
        <p class="text-red-500 font-bold">Зачувајте ја оваа лозинка - нема да биде прикажана повторно!</p>
    </div>
@endif
    <!-- Users List -->
    <div>
        <h3 class="text-xl font-semibold mb-4">Листа на корисници</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full table-auto">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Име</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Улога</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Лозинка</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Акции</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($users as $user)
                    <tr id="user-row-{{ $user->id }}">
                        <td class="px-6 py-4 whitespace-nowrap">{{ $user->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $user->email }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ ucfirst($user->role) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <button onclick="resetPassword({{ $user->id }})"
                                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded-md text-sm">
                                Ресетирај лозинка
                            </button>
                            @if (session('reset_password_' . $user->id))
                                <div class="mt-2 p-2 bg-yellow-100 border border-yellow-400 rounded">
                                    <p class="text-sm">Нова лозинка: <span class="font-mono">{{ session('reset_password_' . $user->id) }}</span></p>
                                    <p class="text-xs text-red-600">Зачувајте ја оваа лозинка - нема да биде прикажана повторно!</p>
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <form action="{{ route('users.destroy', $user) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" onclick="return confirm('Дали сте сигурни?')"
                                        class="text-red-600 hover:text-red-900">Избриши</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function updatePassword(userId) {
    const password = document.getElementById(`password-${userId}`).value;
    
    fetch(`/users/${userId}/update-password`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ password: password })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Password updated successfully');
        } else {
            alert('Failed to update password');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to update password');
    });
}

function resetPassword(userId) {
    if (confirm('Дали сте сигурни дека сакате да ја ресетирате лозинката?')) {
        fetch(`/users/${userId}/reset-password`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show the new password in an alert
                alert(`Новата лозинка за корисникот е: ${data.password}\n\nЗачувајте ја оваа лозинка - нема да биде прикажана повторно!`);
            } else {
                alert('Грешка: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Грешка при ресетирање на лозинката');
        });
    }
}
</script>
@endsection
