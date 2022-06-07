<?php
namespace dp\NutgramConversation\conversation\userinput;
use dp\NutgramConversation\conversation;


abstract class BaseAbstract extends conversation\NestedAbstract
   {
      abstract public function getValue();
   }
