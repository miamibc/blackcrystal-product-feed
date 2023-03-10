<?php

class Kaup24XML extends Base
{

  public function render( WC_Product_Query $query )
  {
    header('Content-type: application/xml');
    $xml = new SimpleXMLElement('<mywebstore/>');
    $xml->addChild('products');
    $discount = $this->getOption('discount')*1;

    foreach( $query->get_products() as $product )
    {
      /** @var WC_Product_Simple $product */
      $prod = $xml->products->addChild('product');
      $prod->id = $product->get_id();
      $prod->sku = $product->get_sku();
      $prod->permalink = $product->get_permalink();
      $prod->title = $product->get_title();
      $prod->description = trim(strip_tags($product->get_short_description()));
      $prod->image = wp_get_attachment_image_url($product->get_image_id('full'));

      $incl = $prod->addChild('categories');
      foreach ($product->get_category_ids() as $id)
      {
        $incl->addChild('category',get_term($id)->name)
        //   ->addAttribute('id',$id)
        ;
      }

      $incl = $prod->addChild('tags');
      foreach ($product->get_tag_ids() as $id)
      {
        $incl->addChild('tag',get_tag($id)->name)
        //   ->addAttribute('id',$id)
        ;
      }

      $prod->price = $product->get_price();
      $prod->regular_price = $product->get_regular_price();
      $prod->wholesale_price = $product->get_sale_price();

    }

    //echo $xml->asXML();
    //return;

    $dom = dom_import_simplexml($xml)->ownerDocument;
    $dom->formatOutput = true;
    echo $dom->saveXML();
  }



}