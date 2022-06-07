<?php
namespace dp\NutgramConversation\conversation\userinput\file;
use SergiX44\Nutgram, SergiX44\Nutgram\Telegram\Types as TGTypes;


trait TPhoto
   {
      protected function getSentFile(): ?TGTypes\Media\File
         {
            return !empty($photo=$this->getUserMessage()?->photo)?
               $this->getBot()->getFile(end($photo)->file_id) :
               null;
         }
      
      protected function newAcquiredFile(string $id, string $name, string $localPath, ?string $trgFilename): model\Photo
         {
            return new model\Photo($id, $name, $localPath, $trgFilename);
         }
      
      abstract protected function getBot(): Nutgram\Nutgram;
      abstract protected function getUserMessage(): ?TGTypes\Message\Message;
   }
