<?php
namespace Starbug\Orders\Admin\Collection;

use Starbug\Auth\SessionHandlerInterface;
use Starbug\Db\DatabaseInterface;
use Starbug\Orders\Admin\OrdersAdminCollection;

class OrdersExportCollection extends OrdersAdminCollection {
  public function __construct(protected DatabaseInterface $db, protected SessionHandlerInterface $session, protected RowFormatterInterface $rowFormatter) {
  }
  public function build($query, $ops) {
    $query = parent::build($query, $ops);

    // Product
    $query->leftJoin("lines")->on("lines.orders_id=orders.id AND lines.type='product_lines'");
    $query->leftJoin("product_lines")->on("product_lines.lines_id=lines.id");
    $query->leftJoin("products")->on("products.id=product_lines.product");
    $query->select(["price as product_price", "description as product"], "lines");
    $query->select("product_lines.id as product_lines_id");
    $query->select("products.sku");

    // Address
    $query->leftJoin("address")->on("orders.shipping_address=address.id");
    $query->leftJoin("provinces")->on("address.administrative_area=provinces.id");
    $query->select(["address1", "address2", "locality", "postal_code"], "address");
    $query->select("provinces.code as state");

    return $query;
  }
  public function filterRows($rows) {
    foreach ($rows as $idx => $row) {
      $rows[$idx] = $this->rowFormatter->format($row);
    }
    return $rows;
  }
}
