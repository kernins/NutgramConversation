<?php
namespace dp\NutgramConversation\conversation\userinput\text\model;
use dp\NutgramConversation\exception;


abstract class ValueNumeric implements IValueModel
   {
      protected const MAX_PARTS_CNT = 0; //must be overriden
      
      
      
      final public static function newInstanceFromString(string $value): static
         {
            $parts = preg_split(
               '/[.,]/u',
               preg_replace('/[ \s]+/u', '', $value),
               -1,
               PREG_SPLIT_NO_EMPTY
            );
            
            if(count($parts) > static::MAX_PARTS_CNT) throw new exception\UnexpectedValueException(
               'Invalid value given: unknown format: '.$value
            );
            
            $isNegative = 0;
            $parts[0] = preg_replace('/^-/u', '', $parts[0], 1, $isNegative);
            if(($invalid=preg_grep('/^\d+$/u', $parts, PREG_GREP_INVERT)) === false) throw new exception\LogicException(
               'Internal error: preg_grep() failed: code '.preg_last_error().': '.preg_last_error_msg(),
               preg_last_error()
            );
            if(!empty($invalid)) throw new exception\UnexpectedValueException(
               'Invalid value given: not a number: '.$value
            );
            if($isNegative) $parts[0] = '-'.$parts[0];
                  
            return static::newInstanceFromParts($parts);
         }
         
      abstract protected static function newInstanceFromParts(array $parts): static;
      
      
      
      abstract public function getValue();
      
      public function __toString(): string
         {
            return (string)$this->getValue();
         }
   }
