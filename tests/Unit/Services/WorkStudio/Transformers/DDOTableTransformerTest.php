<?php

use App\Services\WorkStudio\Transformers\DDOTableTransformer;
use Carbon\Carbon;

beforeEach(function () {
    $this->transformer = new DDOTableTransformer;
});

it('transforms valid DDOTable response to collection', function () {
    $response = [
        'Protocol' => 'DATASET',
        'DataSet' => [
            'Heading' => ['Name', 'Value', 'Status'],
            'Data' => [
                ['Circuit A', 100, 'ACTIV'],
                ['Circuit B', 200, 'QC'],
            ],
        ],
    ];

    $result = $this->transformer->transform($response);

    expect($result)->toHaveCount(2)
        ->and($result[0])->toBe(['Name' => 'Circuit A', 'Value' => 100, 'Status' => 'ACTIV'])
        ->and($result[1])->toBe(['Name' => 'Circuit B', 'Value' => 200, 'Status' => 'QC']);
});

it('returns empty collection for invalid response', function () {
    $invalidResponses = [
        [],
        ['Protocol' => 'OTHER'],
        ['Protocol' => 'DATASET', 'DataSet' => []],
        ['Protocol' => 'DATASET', 'DataSet' => ['Heading' => []]],
    ];

    foreach ($invalidResponses as $response) {
        $result = $this->transformer->transform($response);
        expect($result)->toBeEmpty();
    }
});

it('validates response structure correctly', function () {
    expect($this->transformer->isValidResponse([
        'Protocol' => 'DATASET',
        'DataSet' => [
            'Heading' => ['Col1'],
            'Data' => [['Val1']],
        ],
    ]))->toBeTrue();

    expect($this->transformer->isValidResponse([]))->toBeFalse();
    expect($this->transformer->isValidResponse(['Protocol' => 'INVALID']))->toBeFalse();
});

it('parses workstudio date format correctly', function () {
    $response = [
        'Protocol' => 'DATASET',
        'DataSet' => [
            'Heading' => ['SimpleDate', 'IsoDate'],
            'Data' => [
                ['/Date(2025-12-05)/', '/Date(2025-12-05T20:12:44.142Z)/'],
            ],
        ],
    ];

    $result = $this->transformer->transform($response);

    expect($result[0]['SimpleDate'])->toBeInstanceOf(Carbon::class)
        ->and($result[0]['SimpleDate']->format('Y-m-d'))->toBe('2025-12-05')
        ->and($result[0]['IsoDate'])->toBeInstanceOf(Carbon::class)
        ->and($result[0]['IsoDate']->format('Y-m-d'))->toBe('2025-12-05');
});

it('handles invalid dates by returning null', function () {
    $response = [
        'Protocol' => 'DATASET',
        'DataSet' => [
            'Heading' => ['InvalidDate', 'OldDate'],
            'Data' => [
                ['/Date(1899-12-30)/', '/Date(invalid)/'],
            ],
        ],
    ];

    $result = $this->transformer->transform($response);

    expect($result[0]['InvalidDate'])->toBeNull()
        ->and($result[0]['OldDate'])->toBeNull();
});

it('transforms geometry objects', function () {
    $response = [
        'Protocol' => 'DATASET',
        'DataSet' => [
            'Heading' => ['Geometry'],
            'Data' => [
                [[
                    '@sourceFormat' => 'DataObjectGeometry',
                    'type' => 'Polygon',
                    'coordinates' => [[[-75.28, 40.33], [-75.27, 40.32]]],
                ]],
            ],
        ],
    ];

    $result = $this->transformer->transform($response);

    expect($result[0]['Geometry'])->toBeArray()
        ->and($result[0]['Geometry']['type'])->toBe('Polygon')
        ->and($result[0]['Geometry']['_is_geometry'])->toBeTrue();
});

it('extracts headings from response', function () {
    $response = [
        'Protocol' => 'DATASET',
        'DataSet' => [
            'Heading' => ['Col1', 'Col2', 'Col3'],
            'Data' => [],
        ],
    ];

    $headings = $this->transformer->getHeadings($response);

    expect($headings)->toBe(['Col1', 'Col2', 'Col3']);
});

it('gets row count correctly', function () {
    $response = [
        'Protocol' => 'DATASET',
        'DataSet' => [
            'Heading' => ['Col1'],
            'Data' => [['A'], ['B'], ['C']],
        ],
    ];

    expect($this->transformer->getRowCount($response))->toBe(3);
});

it('selects specific columns from collection', function () {
    $data = collect([
        ['Name' => 'A', 'Value' => 1, 'Extra' => 'X'],
        ['Name' => 'B', 'Value' => 2, 'Extra' => 'Y'],
    ]);

    $result = $this->transformer->selectColumns($data, ['Name', 'Value']);

    expect($result[0])->toBe(['Name' => 'A', 'Value' => 1])
        ->and($result[0])->not->toHaveKey('Extra');
});
