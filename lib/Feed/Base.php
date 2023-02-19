<?php

class Base
{

  protected $options = [];

  public function __construct( $options = [])
  {
    $this->options = array_merge($this->options, $options);
  }

  public function getOption( $name, $default = null )
  {
    return $this->options[$name] ?? $default;
  }
}