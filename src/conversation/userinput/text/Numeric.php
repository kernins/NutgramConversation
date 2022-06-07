<?php
namespace dp\NutgramConversation\conversation\userinput\text;
use dp\NutgramConversation\exception;


class Numeric extends Input
   {
      protected const VALMDL_REQUIRE_SUBCLS = model\ValueNumeric::class;
   
      protected int|float|null $rangeMin = null;
      protected int|float|null $rangeMax = null;
      
      
      
      public function setValueRange(int|float|null $min, int|float|null $max): static
         {
            $this->rangeMin = $min;
            $this->rangeMax = $max;
            return $this;
         }
      
      
      protected function validateValue(): void
         {
            if(!($this->value instanceof model\ValueNumeric)) throw new exception\DomainException(
               'An instance of '.model\ValueNumeric::class.' expected, got '.gettype($this->value)
            );
            $nVal = $this->value->value;
            
            if(($this->rangeMin !== null) && ($nVal < $this->rangeMin))
               throw new exception\RangeException($this->__t('Value must be >= %s', (string)$this->rangeMin));
            
            if(($this->rangeMax !== null) && ($nVal > $this->rangeMax))
               throw new exception\RangeException($this->__t('Value must be <= %s', (string)$this->rangeMax));
         }
   }
