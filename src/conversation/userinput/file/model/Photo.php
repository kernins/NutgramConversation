<?php
namespace dp\NutgramConversation\conversation\userinput\file\model;


class Photo extends File
   {
      public static function getTGType(): string
         {
            return 'photo';
         }
   }
