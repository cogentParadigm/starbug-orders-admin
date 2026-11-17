<?php
namespace Starbug\Orders\Admin\Display;

use Starbug\Dojo\Display\GridDisplay;

class LinesGrid extends GridDisplay {
  public $model = "lines";
  public $action = "admin";
  public function buildDisplay($options) {
    $this->add(["type"]);
    $this->add(["description", "label" => "Description"]);
    $this->add(["taxable"]);
    $this->add(["price_formatted", "label" => "Price"]);
    $this->add(["qty", "label" => "Quantity"]);
    $this->add(["total_formatted", "label" => "Total"]);
    $this->remove("row_options");
  }
}
