<?php
namespace Starbug\Orders\Admin\Collection;

use Starbug\Db\Collection;
use Starbug\Db\DatabaseInterface;
use Starbug\Price\FormatterInterface;

class LinesAdminCollection extends Collection {
  protected $model = "lines";
  public function __construct(protected DatabaseInterface $db, protected FormatterInterface $formatter) {
  }
  public function build($query, $ops) {
    if (!empty($ops["order"])) {
      $query->condition("lines.orders_id", $ops["order"]);
    }
    $query->leftJoin("product_lines")->on("product_lines.lines_id=lines.id");
    $query->select("lines.*");
    $query->sort("lines.position");
    return $query;
  }
  public function filterRows($rows) {
    foreach ($rows as $idx => $item) {
      $item["type"] = ucwords(str_replace("_lines", "", $item["type"]));
      $item["taxable"] = $item["taxable"] ? "Yes" : "No";
      $item["total"] = $item["price"] * $item["qty"];
      $item["total_formatted"] = $this->formatter->format($item["total"]);
      $item["price_formatted"] = $this->formatter->format($item["price"]);
      $rows[$idx] = $item;
    }
    return $rows;
  }
}
