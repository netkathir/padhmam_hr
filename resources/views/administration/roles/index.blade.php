@extends('layouts.admin')

@section('content')
    <x-page-header title="Roles" subtitle="Manage role permissions for the foundation layer." />

    <div class="page-surface p-3">
        <x-data-table class="table mb-0">
            <thead>
            <tr>
                <th>Name</th>
                <th>Slug</th>
                <th>Users</th>
                <th class="text-end">Action</th>
            </tr>
            </thead>
            <tbody>
            @foreach($roles as $role)
                <tr>
                    <td>{{ $role->name }}</td>
                    <td>{{ $role->slug }}</td>
                    <td>{{ $role->users_count }}</td>
                    <td class="text-end">
                        <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-outline-primary">Manage Permissions</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </x-data-table>
        <x-pagination :paginator="$roles" />
    </div>
@endsection
