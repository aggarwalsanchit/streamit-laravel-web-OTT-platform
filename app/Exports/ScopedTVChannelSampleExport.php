<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ScopedTVChannelSampleExport implements FromArray, WithHeadings, WithStyles
{
    public function array(): array
    {
        return [
            [
                'CNN News',
                'News',
                'Stay updated with the latest news from around the world',
                'free',
                '0',
                'https://picsum.photos/200/300',
                'https://picsum.photos/200/300',
                't_url',
                'HLS',
                'https://example.com/stream1.m3u8',
                'https://example.com/stream1-backup.m3u8',
                '',
                'active'
            ],
            [
                'ESPN Sports',
                'Sports',
                'Watch live sports events and highlights',
                'Paid',
                '1',
                'https://picsum.photos/200/300',
                'https://picsum.photos/200/300',
                't_url',
                'RTMP',
                'rtmp://example.com/live/sports',
                'rtmp://backup.example.com/live/sports',
                '',
                'active'
            ],
            [
                'HBO Max',
                'Entertainment',
                'Enjoy movies, TV shows, and entertainment content',
                'Paid',
                '1',
                'https://picsum.photos/200/300',
                'https://picsum.photos/200/300',
                't_embedded',
                'Embedded',
                '',
                '',
                '<iframe src="https://example.com/embed/hbo" width="100%" height="100%"></iframe>',
                'active'
            ],
            [
                'Cartoon Network',
                'Kids',
                'Safe and educational content for children',
                'free',
                '0',
                'https://picsum.photos/200/300',
                'https://picsum.photos/200/300',
                't_url',
                'HLS',
                'https://example.com/stream4.m3u8',
                '',
                '',
                'inactive'
            ]
        ];
    }

    public function headings(): array
    {
        return [
            'Name',
            'Category',
            'Description',
            'Access',
            'Plan ID',
            'Poster URL',
            'Poster TV URL',
            'Type',
            'Stream Type',
            'Server URL',
            'Server URL1',
            'Embedded',
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
