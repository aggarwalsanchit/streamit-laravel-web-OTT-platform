<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ScopedGenresSampleExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function array(): array
    {
        // Return sample data for genres import
        return [
            [
                'Action1',
                'Exciting action movies with fast-paced sequences',
                'active',
                'https://picsum.photos/800/600'
            ],
            [
                'Comedy1',
                'Funny movies that make you laugh',
                'inactive',
                'https://picsum.photos/800/600'
            ],
            [
                'Drama1',
                'Serious dramatic films with emotional depth',
                'active',
                'https://picsum.photos/800/600'
            ],
            [
                'Horror1',
                'Scary movies that create suspense and fear',
                'active',
                'https://picsum.photos/800/600'
            ],
            [
                'Romance1',
                'Love stories and romantic films',
                'inactive',
                'https://picsum.photos/800/600'
            ]
        ];
    }

    public function headings(): array
    {
        return [
            'Name',
            'Description',
            'Status',
            'Image URL'
        ];
    }
}
