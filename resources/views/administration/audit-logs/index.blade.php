@extends('layouts.admin')

@section('content')
    <x-page-header title="Audit Logs" subtitle="Foundation audit trail for sensitive administrative actions." />

    <div class="page-surface p-3">
        <x-data-table class="table mb-0">
            <thead>
            <tr>
                <th>Created</th>
                <th>Event</th>
                <th>Module</th>
                <th>User</th>
                <th>Branch</th>
                <th class="text-end">Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse($auditLogs as $log)
                <tr>
                    <td>{{ optional($log->created_at)->format(config('hrms.date_format').' H:i') }}</td>
                    <td>{{ $log->event }}</td>
                    <td>{{ $log->module }}</td>
                    <td>{{ $log->user?->name ?? 'System' }}</td>
                    <td>{{ $log->branch?->name ?? 'Company Level' }}</td>
                    <td class="text-end">
                        <a href="{{ route('audit-logs.show', $log) }}" class="btn btn-sm btn-outline-secondary">View</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6"><x-empty-state title="No audit logs" message="Foundation activities will appear here after sign-in and admin actions." /></td>
                </tr>
            @endforelse
            </tbody>
        </x-data-table>
        <x-pagination :paginator="$auditLogs" />
    </div>
@endsection
