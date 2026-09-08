<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ScopedSeasonsSampleExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function array(): array
    {
        // Return sample data for seasons import
        return [
            [
                'Breaking Bad - Season 1',
                'The first season of Breaking Bad follows Walter White, a high school chemistry teacher who turns to cooking methamphetamine.',
                '1',
                '1',
                'active',
                'paid',
                'YouTube',
                'https://www.youtube.com/watch?v=HhesaQXLuRY',
                'https://picsum.photos/1200/800',
                'https://picsum.photos/1200/800',
                '2',
                '9.99',
                'subscription',
                '30',
                '0.00',
                'premium'
            ],
            [
                'Game of Thrones - Season 1',
                'The first season of Game of Thrones follows the Stark family and the political intrigue in the Seven Kingdoms.',
                '2',
                '1',
                'active',
                'paid',
                'YouTube',
                'https://www.youtube.com/watch?v=BpJYNVhGf1s',
                'https://picsum.photos/1200/800',
                'https://picsum.photos/1200/800',
                '3',
                '14.99',
                'subscription',
                '30',
                '0.00',
                'premium'
            ],
            [
                'The Office - Season 1',
                'The first season of The Office introduces the employees of Dunder Mifflin Scranton branch.',
                '3',
                '1',
                'active',
                'free',
                'YouTube',
                'https://www.youtube.com/watch?v=UZjOKhVlqgY',
                'https://picsum.photos/1200/800',
                'https://picsum.photos/1200/800',
                '0',
                '0.00',
                'one_time',
                '',
                '0.00',
                'all'
            ],
            [
                'Stranger Things - Season 1',
                'The first season of Stranger Things follows the disappearance of Will Byers and the supernatural events in Hawkins.',
                '4',
                '1',
                'active',
                'paid',
                'YouTube',
                'https://www.youtube.com/watch?v=b9EkMc79ZSU',
                'https://picsum.photos/1200/800',   
                'https://picsum.photos/1200/800',
                '2',
                '12.99',
                'subscription',
                '30',
                '0.00',
                'premium'
            ],
            [
                'Friends - Season 1',
                'The first season of Friends follows six friends living in Manhattan as they navigate life and relationships.',
                '5',
                '1',
                'active',
                'free',
                'YouTube',
                'https://www.youtube.com/watch?v=hDNNmeeOd1Q',
                'https://picsum.photos/1200/800',   
                'https://picsum.photos/1200/800',
                '0',
                '0.00',
                'one_time',
                '',
                '0.00',
                'all'
            ],
            [
                'The Office - Season 2',
                'The second season of The Office continues the workplace comedy at Dunder Mifflin.',
                '3',
                '2',
                'inactive',
                'free',
                'Vimeo',
                'https://vimeo.com/example',
                'https://picsum.photos/1200/800',
                'https://picsum.photos/1200/800',
                '0',
                '4.99',
                'one_time',
                '7',
                '10.00',
                'premium'
            ]
        ];
    }

    public function headings(): array
    {
        return [
            'Name',
            'Description',
            'TV Show',
            'Season Index',
            'Status',
            'Access',
            'Trailer URL Type',
            'Trailer URL',
            'Poster URL',
            'Poster TV URL',
            'Plan ID',
            'Price',
            'Purchase Type',
            'Access Duration',
            'Discount',
            'Available For'
        ];
    }
}
