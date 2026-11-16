<?php

namespace App\Enums;

/** How the store and search results are ordered (the `sort` query parameter). */
enum ProductSort: string
{
    /** Best search match first. Only meaningful with a search query; otherwise Newest is used. */
    case Relevance = 'relevance';
    case PriceAsc = 'price_asc';
    case PriceDesc = 'price_desc';
    case Newest = 'newest';
    case Name = 'name';
}
