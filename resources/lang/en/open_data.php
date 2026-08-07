<?php
return [
    'title'         => 'Open Government Data',
    'subtitle'      => 'Public datasets from Immigration Bureau for developers and researchers',
    'license_title' => 'Data License',
    'license_desc'  => 'All data is published under CC BY 4.0 license',
    'api_hint'      => 'Access data via REST API',
    'dataset'       => [
        'stations' => ['title' => 'Immigration Offices',  'desc' => 'Address and contact information for 17 province offices'],
        'stats'    => ['title' => 'Monthly Statistics',   'desc' => 'Entry/exit counts (aggregated, anonymised)'],
        'forms'    => ['title' => 'Forms Catalog',        'desc' => 'All official immigration forms'],
    ],
];
