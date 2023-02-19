<?php

class FacebookCSV extends Base
{

  private
    $header = false,
    $handle = null;

  public function render( WC_Product_Query $query )
  {

    header('Content-type: text/csv');
    $this->handle = fopen( $file = 'php://output', 'w');
    foreach( $query->get_products() as $product )
    {
      /** @var WC_Product_Simple $product */
      $this->product($product);
    }
    fclose($this->handle);
  }




  public function product( WC_Product $product)
  {
    $message = [
      'id' => $product->get_id(),
      'sku' => $product->get_sku(),
      'title' =>   $product->get_title(),
      'description' => trim(strip_tags($product->get_short_description())),
      'availability' => $product->is_in_stock() ? 'in stock' : 'out of stock',
      'condition' => 'new',
      'link' => $product->get_permalink(),
      'image_link' => wp_get_attachment_image_url($product->get_image_id('full')),
      'brand' => 'Naturka',
      'categories' => implode(',', array_map(function($id){ return get_term($id)->name; }, $product->get_category_ids())),
      'tags'       => implode(',', array_map(function($id){ return get_tag($id)->name; }, $product->get_tag_ids())),
      'price' => $product->get_price(),
      'regular_price' => $product->get_regular_price(),
      'sale_price' => $product->get_sale_price(),
    ];

    if (!$this->header )
    {
      fputcsv($this->handle,array_keys($message));
      $this->header = true;
    }

    fputcsv( $this->handle, $message );

  }


}