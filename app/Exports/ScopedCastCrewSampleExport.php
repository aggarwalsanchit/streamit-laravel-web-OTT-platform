<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ScopedCastCrewSampleExport implements FromArray, WithHeadings, WithStyles
{
    protected $type;

    public function __construct($type = 'actor')
    {
        $this->type = $type;
    }

    public function array(): array
    {
        if ($this->type === 'director') {
            return [
                [
                    'Christopher Nolan',
                    'Director',
                    'Christopher Nolan is a British-American film director, producer, and screenwriter. Known for his complex narratives and innovative storytelling techniques.',
                    'London, England',
                    '1970-07-30',
                    'Film Director',
                    'https://picsum.photos/300/400'
                ],
                [
                    'Steven Spielberg',
                    'Director',
                    'Steven Allan Spielberg is an American film director, producer, and screenwriter. A pioneer of the New Hollywood era and one of the most popular directors in film history.',
                    'Cincinnati, Ohio, USA',
                    '1946-12-18',
                    'Film Director',
                    'https://picsum.photos/300/400'
                ],
                [
                    'Martin Scorsese',
                    'Director',
                    'Martin Charles Scorsese is an American film director, producer, screenwriter, and film historian. Known for his films about crime and Italian-American themes.',
                    'New York City, New York, USA',
                    '1942-11-17',
                    'Film Director',
                    'https://picsum.photos/300/400'
                ],
                [
                    'Quentin Tarantino',
                    'Director',
                    'Quentin Jerome Tarantino is an American film director, producer, screenwriter, and actor. Known for his nonlinear storylines, satirical subject matter, and aestheticization of violence.',
                    'Knoxville, Tennessee, USA',
                    '1963-03-27',
                    'Film Director',
                    'https://picsum.photos/300/400'
                ]
            ];
        } else {
            return [
                [
                    'Leonardo DiCaprio',
                    'Actor',
                    'Leonardo Wilhelm DiCaprio is an American actor and film producer. Known for his work in biopics and period films.',
                    'Los Angeles, California, USA',
                    '1974-11-11',
                    'Actor',
                    'https://picsum.photos/300/400'
                ],
                [
                    'Tom Hanks',
                    'Actor',
                    'Thomas Jeffrey Hanks is an American actor and filmmaker. Known for his comedic and dramatic roles in a variety of film genres.',
                    'Concord, California, USA',
                    '1956-07-09',
                    'Actor',
                    'https://picsum.photos/300/400'
                ],
                [
                    'Meryl Streep',
                    'Actor',
                    'Mary Louise Streep is an American actress. Often described as the best actress of her generation, Streep is particularly known for her versatility and accent adaptability.',
                    'Summit, New Jersey, USA',
                    '1949-06-22',
                    'Actor',
                    'https://picsum.photos/300/400'
                ],
                [
                    'Robert De Niro',
                    'Actor',
                    'Robert Anthony De Niro Jr. is an American actor, producer, and director. He is particularly known for his collaborations with Martin Scorsese.',
                    'New York City, New York, USA',
                    '1943-08-17',
                    'Actor',
                    'https://picsum.photos/300/400'
                ]
            ];
        }
    }

    public function headings(): array
    {
        return [
            'Name',
            'Type',
            'Bio',
            'Place of Birth',
            'Date of Birth',
            'Designation',
            'Image URL'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
