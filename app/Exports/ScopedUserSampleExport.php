<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ScopedUserSampleExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function array(): array
    {
        // Sample user data for import
        return [
            [
                'Jane',
                'Smith',
                'jane.smith@example.com',
                '+1234567891',
                'https://picsum.photos/300/300',
                'Female',
                '12345678',
                '1988-12-03',
                '123 Main St, Anytown, USA'
            ],
            [
                'Michael',
                'Johnson',
                'michael.johnson@example.com',
                '+1234567892',
                'https://picsum.photos/300/300',
                'Male',
                '12345678',
                '1992-08-22',
                '123 Main St, Anytown, USA'
            ],
            [
                'Sarah',
                'Williams',
                'sarah.williams@example.com',
                '+1234567893',
                'https://picsum.photos/300/300',
                'Female',
                '12345678',
                '1985-03-10',
                '123 Main St, Anytown, USA'
            ],
            [
                'David',
                'Brown',
                'david.brown@example.com',
                '+1234567894',
                'https://picsum.photos/300/300',
                'Male',
                '12345678',
                '1995-11-18',
                '123 Main St, Anytown, USA'
            ]
        ];
    }

    public function headings(): array
    {
        return [
            'First Name',
            'Last Name',
            'Email',
            'Mobile',
            'File URL',
            'Gender',
            'Password',
            'Date Of Birth',
            'Address'
        ];
    }
}
