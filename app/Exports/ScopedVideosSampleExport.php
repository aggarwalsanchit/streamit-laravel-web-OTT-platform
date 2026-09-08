<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ScopedVideosSampleExport implements FromArray, WithHeadings, WithStyles
{
    public function array(): array
    {
        return [
            [
                'The Matrix',
                'A computer hacker learns about the true nature of reality and his role in the war against its controllers.',
                'paid',
                '1',
                '02:16:00',
                '1999-03-31',
                'https://picsum.photos/300/450',
                'https://picsum.photos/300/450',
                'URL',
                'https://example.com/matrix.mp4',
                '1080p',
                'URL',
                'https://example.com/matrix-1080p.mp4',
                'active'
            ],
            [
                'Inception',
                'A thief who steals corporate secrets through dream-sharing technology is given the inverse task of planting an idea into the mind of a C.E.O.',
                'paid',
                '1',
                '02:28:00',
                '2010-07-16',
                'https://picsum.photos/300/450',
                'https://picsum.photos/300/450',
                'YouTube',
                'https://www.youtube.com/watch?v=YoHD9XEInc0',
                '720p',
                'YouTube',
                'https://www.youtube.com/watch?v=YoHD9XEInc0',
                'active'
            ],
            [
                'Interstellar',
                'A team of explorers travel through a wormhole in space in an attempt to ensure humanity\'s survival.',
                'free',
                '0',
                '02:49:00',
                '2014-11-07',
                'https://picsum.photos/300/450',
                'https://picsum.photos/300/450',
                'HLS',
                'https://example.com/interstellar.m3u8',
                '4K',
                'HLS',
                'https://example.com/interstellar-4k.m3u8',
                'active'
            ],
            [
                'The Dark Knight',
                'When the menace known as the Joker wreaks havoc and chaos on the people of Gotham, Batman must accept one of the greatest psychological and physical tests of his ability to fight injustice.',
                'paid',
                '1',
                '02:32:00',
                '2008-07-18',
                'https://picsum.photos/300/450',
                'https://picsum.photos/300/450',
                'YouTube',
                'https://www.youtube.com/watch?v=hDNNmeeOd1Q',
                '1080p',
                'YouTube',
                'https://www.youtube.com/watch?v=hDNNmeeOd1Q',
                'inactive'
            ]
        ];
    }

    public function headings(): array
    {
        return [
            'Name',
            'Description',
            'Access',
            'Plan ID',
            'Duration',
            'Release Date',
            'Poster URL',
            'Poster TV URL',
            'Video Upload Type',
            'Video URL',
            'Quality',
            'Quality Type',
            'Quality URL',
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
