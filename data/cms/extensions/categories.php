<?php
/**
 * Category configuration (SAFE — survives updates)
 *
 * Managed via CMS admin (/categories). Manual edits are also OK.
 */
return [
    '/blogg' => ['category' => 'Blogg', 'title_sv' => 'Blogg', 'title_en' => 'Blog', 'base_url' => '/blogg'],
    '/inlagg' => ['category' => 'Inlägg', 'title_sv' => 'Inlägg', 'title_en' => 'Posts', 'base_url' => '/inlagg'],
    '/nyheter' => ['category' => 'Nyhet', 'title_sv' => 'Nyheter', 'title_en' => 'News', 'base_url' => '/nyheter'],
    '/event' => ['category' => 'Event', 'title_sv' => 'Event', 'title_en' => 'Events', 'base_url' => '/event'],
];
