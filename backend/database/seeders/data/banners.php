<?php

/*
 * Hero carousel slides, in display order. link_url is a path inside the frontend. The last banner is
 * inactive, to show that switching a banner off hides it without deleting it.
 */
return [
    [
        'title' => 'Winter Sale',
        'subtitle' => 'Up to 55% off top games from trusted sellers',
        'link_url' => '/store',
        'active' => true,
    ],
    [
        'title' => 'Elden Ring',
        'subtitle' => 'Explore the Lands Between for less: compare offers from several sellers',
        'link_url' => '/product/elden-ring-steam-global',
        'active' => true,
    ],
    [
        'title' => 'Tears of the Kingdom',
        'subtitle' => 'The sky above Hyrule is waiting. Nintendo keys for Europe and North America',
        'link_url' => '/product/the-legend-of-zelda-tears-of-the-kingdom-nintendo-europe',
        'active' => true,
    ],
    [
        'title' => "Baldur's Gate 3",
        'subtitle' => 'Roll for initiative: the award-winning RPG, delivered instantly',
        'link_url' => '/product/baldurs-gate-3-steam-global',
        'active' => true,
    ],
    [
        'title' => 'Summer Sale',
        'subtitle' => 'This sale has ended',
        'link_url' => '/store',
        'active' => false,
    ],
];
