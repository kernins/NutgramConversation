<?php
namespace dp\NutgramConversation\conversation\userinput\text\model;


interface IDictionary
   {
      /**
       * @param string  $value
       * @param int     $limit
       * @param int     $found
       * @return IDictionaryEntry[]
       */
      public function search(string $value, int $limit=0, int &$found=0): array;
      
      public function getById(string $id): ?IDictionaryEntry;
   }
