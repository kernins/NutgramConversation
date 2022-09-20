<?php
namespace dp\NutgramConversation\conversation\userinput\text\model;
use dp\NutgramConversation\exception;


class ValuePhone extends ValueString
   {
      protected const REQ_COUNTRY_CODE = true;
      
      protected const DIGIT_LENGTH = [
         [8, 10],    //[min, max] digits count w/o country code
         [10, 12]    //[min, max] digits count including country code (excl + sign)
      ];
      
      protected const SANITIZE_REG_EXP = ['/[^\+\d]/', '/(?<!^)\+/'];
      
      
      
      public function __construct(string $phone)
         {
            $phone = trim($phone);
            if(!($hasCC=(bool)preg_match('/^\+\s*\d+/ui', $phone)) && static::REQ_COUNTRY_CODE) 
               throw new exception\UnexpectedValueException('Phone number must include +country code');
            
            $dLen = strlen(preg_replace('/[^\d]/', '', $phone));
            if(($dLen < static::DIGIT_LENGTH[(int)$hasCC][0]) || ($dLen > static::DIGIT_LENGTH[(int)$hasCC][1]))
               throw new exception\UnexpectedValueException('Invalid or incomplete phone number given: invalid length');
            
            $this->value = empty(static::SANITIZE_REG_EXP)?
               $phone : preg_replace(static::SANITIZE_REG_EXP, '', $phone);
         }
   }
