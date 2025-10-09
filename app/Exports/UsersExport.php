<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UsersExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return User::select('id', 'name', 'email', 'role', 'department_id', 'supervisor_id')
                   ->with('department.division', 'supervisor')
                   ->get()
                   ->map(function($user) {
                       return [
                           'id' => $user->id,
                           'name' => $user->name,
                           'email' => $user->email,
                           'role' => $user->role,
                           'department' => $user->department ? $user->department->name : '-',
                           'division' => $user->department && $user->department->division ? $user->department->division->name : '-',
                           'supervisor' => $user->supervisor ? $user->supervisor->name : '-',
                       ];
                   });
    }

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Email',
            'Role',
            'Department',
            'Division',
            'Supervisor',
        ];
    }
}
