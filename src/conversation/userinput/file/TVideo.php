<?php
namespace dp\NutgramConversation\conversation\userinput\file;
use SergiX44\Nutgram, SergiX44\Nutgram\Telegram\Types as TGTypes;


trait TVideo
   {
      protected function getSentFile(): ?TGTypes\Media\File
         {
            $msg = $this->getUserMessage();
            return !empty($video=$msg?->video ?? $msg?->video_note)?
               $this->getBot()->getFile($video->file_id) :
               null;
         }
      
      protected function newAcquiredFile(string $id, string $name, string $localPath, ?string $trgFilename): model\Video
         {
            return new model\Video($id, $name, $localPath, $trgFilename);
         }
      
      abstract protected function getBot(): Nutgram\Nutgram;
      abstract protected function getUserMessage(): ?TGTypes\Message\Message;
   }
