<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ScopedEpisodesSampleExport implements FromArray, WithHeadings, WithStyles
{
    public function array(): array
    {
        return [
            [
                'Pilot',
                'Walter White, a struggling high school chemistry teacher, is diagnosed with lung cancer. He teams up with former student Jesse Pinkman to cook and sell methamphetamine.',
                'Breaking Bad',
                'Breaking Bad Season 1',
                '1',
                'free',
                'active',
                'YouTube',
                'https://www.youtube.com/watch?v=HhesaQXLuRY',
                'https://picsum.photos/200/300',
                'https://picsum.photos/1200/800',
                'YouTube',
                'https://www.youtube.com/watch?v=hDNNmeeOd1Q',
                '0',
                '9.2',
                'TV-MA',
                '12:00',
                '2008-01-20',
                '0',
                '9.99',
                'one_time',
                '30',
                '10',
                'all'
            ],
            [
                'The One Where Monica Gets a Roommate',
                'Monica and the gang help Ross deal with his impending divorce, while Joey and Chandler help Ross move his new furniture.',
                'Friends',
                'Friends Season 1',
                '1',
                'free',
                'active',
                'YouTube',
                'https://www.youtube.com/watch?v=LHOtME2DL4g',
                'https://picsum.photos/200/300',
                'https://picsum.photos/1200/800',
                'YouTube',
                'https://www.youtube.com/watch?v=hDNNmeeOd1Q',
                '0',
                '8.5',
                'TV-14',
                '22:00',
                '1994-09-22',
                '0',
                '14.99',
                'monthly',
                '30',
                '15',
                'all'
            ]
        ];
    }

    public function headings(): array
    {
        return [
            'Name',
            'Description',
            'TV Show',
            'Season',
            'Episode Number',
            'Access',
            'Status',
            'Trailer URL Type',
            'Trailer URL',
            'Poster URL',
            'Poster TV URL',
            'Video Upload Type',
            'Video URL Input',
            'Plan ID',
            'IMDb Rating',
            'Content Rating',
            'Duration',
            'Release Date',
            'Is Restricted',
            'Price',
            'Purchase Type',
            'Access Duration',
            'Discount',
            'Available For'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
