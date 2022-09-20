<?php
namespace dp\NutgramConversation\conversation\userinput\text\model;
use dp\NutgramConversation\exception;


class ValueEmail extends ValueString
   {
      public function __construct(string $email)
         {
            if(!filter_var($email, FILTER_VALIDATE_EMAIL))
               throw new exception\UnexpectedValueException('Invalid email address given');
            
            $this->value = $email;
         }
   }
