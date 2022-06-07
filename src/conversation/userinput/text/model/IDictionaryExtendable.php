<?php
namespace dp\NutgramConversation\conversation\userinput\text\model;


interface IDictionaryExtendable extends IDictionary
   {
      public function add(string $value): IDictionaryEntry;
      public function isValidForAddition(string $value): bool;
   }
