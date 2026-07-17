@extends('layouts.admin')

@section('content')
    <x-page-header title="Users" subtitle="Manage the foundation user master.">
        @if(auth()->user()->can('create', \App\Models\User::class))
            <a href="{{ route('users.create') }}" class="btn btn-primary">New User</a>
        @endif
    </x-page-header>

    <div class="page-surface p-3">
        <x-data-table class="table mb-0">
            <thead>
            <tr>
                <th>Name</th>
                <th>Username</th>
                <th>Branch</th>
                <th>Role</th>
                <th>Status</th>
                <th class="text-end">Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->username }}</td>
                    <td>{{ $user->branch?->name ?? 'Super Administrator' }}</td>
                    <td>{{ $user->primaryRoleName() }}</td>
                    <td><x-status-badge :status="$user->status" /></td>
                    <td class="text-end">
                        <a href="{{ route('users.show', $user) }}" class="btn btn-sm btn-outline-secondary">View</a>
                        <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6"><x-empty-state title="No users yet" message="Seeders create the administrator accounts for you." /></td>
                </tr>
            @endforelse
            </tbody>
        </x-data-table>
        <x-pagination :paginator="$users" />
    </div>
@endsection
