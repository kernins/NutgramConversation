<?php
namespace dp\NutgramConversation\conversation\userinput\text\model;


class ValueInt extends ValueNumeric
   {
      protected const MAX_PARTS_CNT = 2;
      
      
      //TODO: php 8.1 declare read-only
      public int $value;
      
      
      
      final public function __construct(int $value)
         {
            $this->value = $value;
         }
      
      public function getValue(): int
         {
            return $this->value;
         }
      
      
      protected static function newInstanceFromParts(array $parts): static
         {
            return new static((int)implode('', $parts));
         }
   }
