<?php
namespace Starbug\Orders\Admin;

use function DI\add;
use function DI\autowire;
use function DI\get;
use Starbug\Orders\Admin\Collection\RowFormatter;
use Starbug\Orders\Admin\Collection\RowFormatterInterface;

return [
  "route.providers" => add([
    get(RouteProvider::class)
  ]),
  "db.schema.migrations" => add([
    get(Migration::class)
  ]),
  RowFormatterInterface::class => autowire(RowFormatter::class)
];
