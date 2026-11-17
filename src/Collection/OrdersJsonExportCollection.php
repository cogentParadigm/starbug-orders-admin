<?php
namespace Starbug\Orders\Admin\Collection;

use Starbug\Auth\SessionHandlerInterface;
use Starbug\Db\DatabaseInterface;
use Starbug\Orders\Admin\OrdersAdminCollection;
use Starbug\Price\FormatterInterface;

class OrdersJsonExportCollection extends OrdersAdminCollection {
  protected $model = "orders";

  public function __construct(protected DatabaseInterface $db, protected SessionHandlerInterface $session, protected FormatterInterface $formatter) {
  }

  public function build($query, $ops) {
    // Base order fields
    $query->select(["id", "order_status", "created", "email", "phone"], "orders");
    $totalSubquery = $query->query("lines")
      ->select("SUM(CASE WHEN lines.type='coupon_lines' THEN -1 * lines.price ELSE lines.price END * lines.qty)")
      ->where("lines.orders_id=orders.id");
    $query->getQuery()->addSelection($totalSubquery->getQuery(), "total");

    $shippingSubquery = $query->query("lines")
      ->select("SUM(lines.price * lines.qty)")
      ->where("lines.orders_id=orders.id AND lines.type='shipping_lines'");
    $query->getQuery()->addSelection($shippingSubquery->getQuery(), "shipping");

    $taxSubquery = $query->query("lines")
      ->select("SUM(lines.price * lines.qty)")
      ->where("lines.orders_id=orders.id AND lines.type='tax_lines'");
    $query->getQuery()->addSelection($taxSubquery->getQuery(), "tax");

    // Product lines - one row per SKU
    $query->leftJoin("lines AS product_line")->on("product_line.orders_id=orders.id AND product_line.type='product_lines'");
    $query->leftJoin("product_lines")->on("product_lines.lines_id=product_line.id");
    $query->leftJoin("products")->on("products.id=product_lines.product");
    $query->select("product_line.description as product_description");
    $query->select("product_line.price as product_price");
    $query->select("product_line.qty as product_qty");
    $query->select("product_lines.id as product_lines_id");
    $query->select("products.sku");

    // Shipping address
    $query->leftJoin("address AS shipping_address")->on("orders.shipping_address=shipping_address.id");
    $query->leftJoin("provinces AS shipping_province")->on("shipping_address.administrative_area=shipping_province.id");
    $query->leftJoin("countries AS shipping_country")->on("shipping_address.country=shipping_country.id");
    $query->select("shipping_address.recipient as shipping_recipient");
    $query->select("shipping_address.organization as shipping_organization");
    $query->select("shipping_address.address1 as shipping_address1");
    $query->select("shipping_address.address2 as shipping_address2");
    $query->select("shipping_address.district as shipping_district");
    $query->select("shipping_address.locality as shipping_city");
    $query->select("shipping_address.sorting_code as shipping_sorting_code");
    $query->select("COALESCE(shipping_province.code, shipping_address.administrative_area) as shipping_state");
    $query->select("shipping_address.postal_code as shipping_postal_code");
    $query->select("shipping_country.name as shipping_country");
    $query->select("shipping_country.code as shipping_country_code");

    // Billing address
    $query->leftJoin("address AS billing_address")->on("orders.billing_address=billing_address.id");
    $query->leftJoin("provinces AS billing_province")->on("billing_address.administrative_area=billing_province.id");
    $query->leftJoin("countries AS billing_country")->on("billing_address.country=billing_country.id");
    $query->select("billing_address.recipient as billing_recipient");
    $query->select("billing_address.organization as billing_organization");
    $query->select("billing_address.address1 as billing_address1");
    $query->select("billing_address.address2 as billing_address2");
    $query->select("billing_address.district as billing_district");
    $query->select("billing_address.locality as billing_city");
    $query->select("billing_address.sorting_code as billing_sorting_code");
    $query->select("COALESCE(billing_province.code, billing_address.administrative_area) as billing_state");
    $query->select("billing_address.postal_code as billing_postal_code");
    $query->select("billing_country.name as billing_country");
    $query->select("billing_country.code as billing_country_code");
    $query->select("CASE WHEN orders.billing_address is null THEN shipping_address.recipient ELSE billing_address.recipient END as customer");

    // Filters from the parent collection
    $query->condition("orders.deleted", "0");

    if (!$this->session->loggedIn(["root", "admin"])) {
      $query->condition("orders.owner", $this->session->getUserId());
    }

    if (!empty($ops["order_status"])) {
      $query->condition("orders.order_status", explode(",", $ops["order_status"]));
    } else {
      $query->condition("orders.order_status", "cart", "!=");
    }

    if (!empty($ops["start_date"])) {
      $query->condition("orders.created", date("Y-m-d", strtotime($ops["start_date"])), ">=");
    }

    if (!empty($ops["end_date"])) {
      $query->condition("orders.created", date("Y-m-d", strtotime($ops["end_date"])), "<=");
    }

    if (!empty($ops["product_type"])) {
      $types = $query->query("products")
        ->innerJoin("product_lines")->on("product_lines.product=products.id")
        ->innerJoin("lines")->on("lines.id=product_lines.lines_id")
        ->where("lines.orders_id=orders.id")
        ->condition("products.type", explode(",", $ops["product_type"]))
        ->select("products.id");
      $query->condition($types, null, "EXISTS");
    }

    // Sorting
    $query->sort("orders.created DESC, orders.id DESC");

    return $query;
  }

  public function filterRows($rows) {
    $orders = [];
    foreach ($rows as $row) {
      $id = $row["id"];
      if (!isset($orders[$id])) {
        $orders[$id] = [
          "id" => $row["id"],
          "order_status" => $row["order_status"],
          "created" => $row["created"],
          "email" => $row["email"] ?? null,
          "phone" => $row["phone"] ?? null,
          "customer" => $row["customer"] ?? null,
          "total" => empty($row["total"]) ? null : $this->formatter->format($row["total"]),
          "shipping" => empty($row["shipping"]) ? null : $this->formatter->format($row["shipping"]),
          "tax" => empty($row["tax"]) ? null : $this->formatter->format($row["tax"]),
          "shipping_address" => [
            "recipient" => $row["shipping_recipient"] ?? null,
            "organization" => $row["shipping_organization"] ?? null,
            "address1" => $row["shipping_address1"] ?? null,
            "address2" => $row["shipping_address2"] ?? null,
            "district" => $row["shipping_district"] ?? null,
            "city" => $row["shipping_city"] ?? null,
            "state" => $row["shipping_state"] ?? null,
            "sorting_code" => $row["shipping_sorting_code"] ?? null,
            "postal_code" => $row["shipping_postal_code"] ?? null,
            "country" => $row["shipping_country"] ?? null,
            "country_code" => $row["shipping_country_code"] ?? null,
          ],
          "billing_address" => [
            "recipient" => $row["billing_recipient"] ?? null,
            "organization" => $row["billing_organization"] ?? null,
            "address1" => $row["billing_address1"] ?? null,
            "address2" => $row["billing_address2"] ?? null,
            "district" => $row["billing_district"] ?? null,
            "city" => $row["billing_city"] ?? null,
            "state" => $row["billing_state"] ?? null,
            "sorting_code" => $row["billing_sorting_code"] ?? null,
            "postal_code" => $row["billing_postal_code"] ?? null,
            "country" => $row["billing_country"] ?? null,
            "country_code" => $row["billing_country_code"] ?? null,
          ],
          "products" => [],
        ];
      }
      if (!empty($row["product_lines_id"])) {
        $orders[$id]["products"][] = [
          "product_lines_id" => $row["product_lines_id"],
          "sku" => $row["sku"] ?? null,
          "description" => $row["product_description"] ?? null,
          "price" => empty($row["product_price"]) ? null : $this->formatter->format($row["product_price"]),
          "qty" => $row["product_qty"] ?? null,
        ];
      }
    }
    return array_values($orders);
  }
}
