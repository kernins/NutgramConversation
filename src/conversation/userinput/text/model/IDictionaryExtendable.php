<?php
namespace dp\NutgramConversation\conversation\userinput\text\model;


interface IDictionaryExtendable extends IDictionary
   {
      public function isEmpty(): bool;
   
      public function add(string|IDictionaryEntry $value): IDictionaryEntry;
      public function isValidForAddition(string|IDictionaryEntry $value): bool;
      
      /** @return string|null FQN of a class implementing IValueModel. Optional */
      public function getEntryValueModel(): ?string;
   }
