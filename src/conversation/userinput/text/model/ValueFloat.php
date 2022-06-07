<?php
namespace dp\NutgramConversation\conversation\userinput\text\model;


class ValueFloat extends ValueNumeric
   {
      protected const MAX_PARTS_CNT = 3;
      
      
      //TODO: php 8.1 declare read-only
      public float $value;
      
      
      
      final public function __construct(float $value)
         {
            $this->value = $value;
         }
         
      public function getValue(): float
         {
            return $this->value;
         }
      
      
      protected static function newInstanceFromParts(array $parts): static
         {
            if(count($parts) > 1)
               {
                  $decimal = array_pop($parts);
                  $value = implode('', $parts).'.'.$decimal;
               }
            else $value = $parts[0];
            
            return new static((float)$value);
         }
   }
