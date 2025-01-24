@extends('layouts.app')

@section('content')
<div class="container mx-auto">
    <h1 class="text-2xl font-bold mb-4">User Management</h1>

    <!-- Add User Button -->
    <div class="mb-4">
        <button onclick="openAddUserModal()" 
                class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
            Add New User
        </button>
    </div>

    <!-- Users List -->
    <div class="bg-white rounded shadow">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Name
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Role
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($users as $user)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        {{ $user->name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 rounded-full text-sm {{ $user->is_admin 
                            ? 'bg-purple-100 text-purple-800' 
                            : 'bg-gray-100 text-gray-800' }}">
                            {{ $user->is_admin ? 'Admin' : 'User' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right">
                        <button onclick="openEditUserModal({{ $user->id }})" 
                                class="text-blue-500 hover:text-blue-700">
                            Edit
                        </button>
                        <button onclick="deleteUser({{ $user->id }})" 
                                class="ml-4 text-red-500 hover:text-red-700">
                            Delete
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Add User Modal -->
<div id="addUserModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Add New User</h3>
            <form id="addUserForm" class="mt-4">
                @csrf
                <div class="mb-4">
                    <input type="text" name="name" placeholder="Username" 
                           class="w-full px-3 py-2 border rounded-md">
                </div>
                <div class="mb-4">
                    <input type="password" name="password" placeholder="Password" 
                           class="w-full px-3 py-2 border rounded-md">
                </div>
                <div class="mt-4">
                    <button type="submit" 
                            class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                        Add User
                    </button>
                    <button type="button" onclick="closeAddUserModal()" 
                            class="ml-2 bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection