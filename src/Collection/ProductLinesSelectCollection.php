<?php
namespace Starbug\Orders\Admin\Collection;

use Starbug\Db\Collection\SelectCollection;
use Starbug\Db\DatabaseInterface;
use Starbug\Db\Schema\SchemerInterface;
use Starbug\Price\FormatterInterface;

class ProductLinesSelectCollection extends SelectCollection {
  protected $model = "product_lines";
  protected $search_fields = "product_lines.id,lines.description";
  public function __construct(DatabaseInterface $db, SchemerInterface $schemer, protected FormatterInterface $formatter) {
    parent::__construct($db, $schemer);
  }
  public function build($query, $ops) {
    $query->innerJoin("lines")->on("lines.id=product_lines.lines_id");
    if (!empty($ops["orders_id"])) {
      $query->condition("lines.orders_id", $ops["orders_id"]);
    }
    $query = parent::build($query, $ops);
    $query->select("CONCAT(product_lines.id, ': ', lines.description) as label");
    $query->select(["price", "qty"], "lines");
    return $query;
  }
  public function filterRows($rows) {
    foreach ($rows as $idx => $item) {
      $item["total"] = $item["price"] * $item["qty"];
      $item["total_formatted"] = $this->formatter->format($item["total"]);
      $item["price_formatted"] = $this->formatter->format($item["price"]);
      $rows[$idx] = $item;
    }
    return $rows;
  }
}
