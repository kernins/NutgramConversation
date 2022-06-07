<?php
namespace dp\NutgramConversation\conversation\userinput\text\model;


interface IDictionary
   {
      public function search(string $value, int $limit=0, int &$found=0): array;
      
      public function getById(int $id): ?IDictionaryEntry;
   }
