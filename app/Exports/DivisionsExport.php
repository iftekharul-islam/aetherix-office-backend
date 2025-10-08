<?php

namespace App\Exports;

use App\Models\Division;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DivisionsExport implements FromCollection, WithHeadings
{
    /**
     * Return the collection of divisions to export.
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Division::with('departments', 'head') // eager load related models
            ->get()
            ->map(function ($division) {
                return [
                    'id' => $division->id,
                    'name' => $division->name,
                    'code' => $division->code,
                    'description' => $division->description,
                    'head' => $division->head ? $division->head->name : '-',
                    'departments' => $division->departments->pluck('name')->implode(', '), // comma-separated department names
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
            'Head',
            'Departments',
        ];
    }
}
