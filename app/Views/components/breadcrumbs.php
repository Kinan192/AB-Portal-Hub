<?php
/** @var list<array{label: string}> $breadcrumbs */
echo partial('breadcrumb', [
    'items' => array_merge([
        ['label' => 'Portal Monitoring Terpadu', 'href' => url('/'), 'icon' => 'house', 'iconClass' => 'text-[14px]'],
    ], $breadcrumbs),
]);
