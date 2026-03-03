<?php
/**
 * Category configuration (SAFE — survives updates)
 *
 * Each key is a URL prefix. The PHP views use this to determine
 * which category to filter by and what labels to show.
 *
 * Managed via CMS admin (/categories). Manual edits are also OK.
 */
return [
    '/inlagg' => ['category' => 'Inlägg', 'title_sv' => 'Inlägg', 'title_en' => 'Posts', 'base_url' => '/inlagg'],
];
