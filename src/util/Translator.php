<?php
namespace dp\NutgramConversation\util;


class Translator
   {
      protected array $strings = [];
      
      
      
      final public function __construct(array $strings=[])
         {
            $this->strings = $strings;
         }
      
      public function newInstanceWithOverride(array $override): static
         {
            return new static($override + $this->strings);
         }
      
      
      
      public function translate(string $fmt, ...$params): string
         {
            return sprintf($this->strings[$fmt] ?? $fmt, ...$params);
         }
         
      public function translatePlural(string $fmt, int $number, ...$params): string
         {
            if(!empty($this->strings[$fmt]))
               {
                  $fmt = is_array($this->strings[$fmt])?
                     $this->strings[$fmt][$this->pluralIndexForNumber($number)] ?? $this->strings[$fmt][0] :
                     $this->strings[$fmt];
               }
            return sprintf($fmt, ...(empty($params)? [$number] : $params));
         }
      
      
      protected function pluralIndexForNumber(int $number): int
         {
            $number = abs($number);
            
            if($number == 0) $idx = 3;
            elseif(($number<10) || ($number>20))
               {
                  switch($number%10)
                     {
                        case 1:
                           $idx = 1;
                           break;
                        case 2:
                        case 3:
                        case 4:
                           $idx = 2;
                           break;
                        default:
                           $idx = 0;
                     }
               }
            else $idx = 0;
            
            return $idx;
         }
   }
