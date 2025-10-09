<?php

namespace App\Exports;

use App\Models\Department;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DepartmentsExport implements FromCollection, WithHeadings
{
    /**
     * Return the collection of departments to export.
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Department::with('division') // Eager load the related division
            ->get()
            ->map(function ($department) {
                return [
                    'id' => $department->id,
                    'name' => $department->name,
                    'code' => $department->code,
                    'description' => $department->description,
                    'division' => $department->division ? $department->division->name : '-', // Include division name
                ];
            });
    }

    /**
     * Define the headings for each column.
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Code',
            'Description',
            'Division',
        ];
    }
}
