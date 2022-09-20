<?php
namespace dp\NutgramConversation\conversation\userinput\text\model;


abstract class ValueString implements IValueModel
   {
      //TODO: read-only
      public string $value;
      
      
      abstract public function __construct(string $string);
      
      public static function newInstanceFromString(string $value): static
         {
            return new static($value);
         }
      
      
      public function __toString(): string
         {
            return $this->value;
         }
   }
