<?php
namespace dp\NutgramConversation\conversation\userinput\text\model;


interface IValueModel extends \Stringable
   {
      public static function newInstanceFromString(string $value): static;
   }
