<?php
namespace dp\NutgramConversation\conversation\userinput\file\model;


class Video extends File
   {
      public static function getTGType(): string
         {
            return 'video';
         }
   }
