<?php
namespace Starbug\Orders\Admin\Collection;

use Starbug\Intl\AddressFormatter;
use Starbug\Price\FormatterInterface;

class RowFormatter implements RowFormatterInterface {
  public function __construct(protected FormatterInterface $prices, protected AddressFormatter $address) {
  }
  public function format(array $row, array $ops = []): array {
    $record = [];
    $record["ID"] = $row["id"];
    $record["Product Line ID"] = $row["product_lines_id"];
    $record["Date"] = $row["created"];
    $record["SKU"] = $row["sku"];
    $record["Product"] = $row["product"];
    $record["Product Price"] = $this->prices->format($row["product_price"]);
    $record["Total"] = empty($row["total"]) ? "" : $this->prices->format($row["total"]);
    $record["Address"] = $row["address1"];
    $record["Address 2"] = $row["address2"];
    $record["City"] = $row["locality"];
    $record["State"] = $row["state"];
    $record["ZIP"] = $row["postal_code"];
    return $record;
  }
}
