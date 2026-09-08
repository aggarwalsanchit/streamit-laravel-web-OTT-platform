<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ScopedTVCategorySampleExport implements FromArray, WithHeadings, WithStyles
{
    public function array(): array
    {
        return [
            [
                'News',
                'Stay updated with the latest news from around the world',
                'https://picsum.photos/200/300',
                'active'
            ],
            [
                'Sports',
                'Watch live sports events and highlights',
                'https://picsum.photos/200/300',
                'active'
            ],
            [
                'Entertainment',
                'Enjoy movies, TV shows, and entertainment content',
                'https://picsum.photos/200/300',
                'active'
            ],
            [
                'Kids',
                'Safe and educational content for children',
                'https://picsum.photos/200/300',
                'inactive'
            ]
        ];
    }

    public function headings(): array
    {
        return [
            'Name',
            'Description',
            'File URL',
            'Status'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
