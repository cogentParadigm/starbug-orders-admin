<?php
namespace Starbug\Orders\Admin;

use Starbug\Admin\RouteProvider as AdminRouteProvider;
use Starbug\Orders\Admin\Collection\LinesAdminCollection;
use Starbug\Orders\Admin\Collection\OrdersExportCollection;
use Starbug\Orders\Admin\Collection\ProductLinesSelectCollection;
use Starbug\Orders\Admin\Controller\OrdersApiController;
use Starbug\Orders\Admin\Display\LinesGrid;
use Starbug\Routing\Route;

class RouteProvider extends AdminRouteProvider {
  public function configure(Route $routes) {
    $orders = $this->addCrud($routes, "orders", [
      "grid" => OrdersGrid::class,
      "form" => OrdersForm::class,
      "enableCreateAction" => false
    ]);
    $orders["adminApi"]->setOption("collection", OrdersAdminCollection::class);
    $orders["list"]->addRoute("/view/{id:[0-9]+}", OrdersAdminController::class, [
      "productsGrid" => LinesGrid::class
    ]);
    $api = $routes->getRoute("api");
    $this->addApiRoute($api->addRoute("/product-lines/admin.json"), "product_lines", ProductLinesAdminCollection::class);
    $this->addApiRoute($api->addRoute("/product-lines/select.json"), "product_lines", ProductLinesSelectCollection::class);
    $this->addApiRoute($api->addRoute("/lines/admin.json"), "lines", LinesAdminCollection::class);
    $api->addRoute("/orders/export.{format:json|csv}")
      ->setOptions([
        "collection" => OrdersExportCollection::class,
        "model" => "orders",
        "groups" => ["root", "admin"]
      ])
      ->setController(OrdersApiController::class);
  }
}
