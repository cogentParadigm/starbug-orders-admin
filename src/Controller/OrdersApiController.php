<?php
namespace Starbug\Orders\Admin\Controller;

use Starbug\Orders\Admin\Collection\OrdersExportCollection;
use Starbug\Orders\Admin\Collection\OrdersJsonExportCollection;
use Psr\Http\Message\ResponseInterface;
use Starbug\Api\Controller\CollectionController;

class OrdersApiController extends CollectionController {
  public function __invoke($collection, $model = false, $format = "json"): ResponseInterface {
    if ($format == "csv") {
      $collection = OrdersExportCollection::class;
    } elseif ($format == "json") {
      $collection = OrdersJsonExportCollection::class;
    }
    return parent::__invoke($collection, $model, $format);
  }
}
