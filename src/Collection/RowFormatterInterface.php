<?php
namespace Starbug\Orders\Admin\Collection;

interface RowFormatterInterface {
  /**
   * Map an export row to the columns of the csv export.
   *
   * Applications override the implementation to choose their own columns.
   */
  public function format(array $row, array $ops = []): array;
}
