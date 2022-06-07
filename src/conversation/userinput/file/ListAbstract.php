<?php
namespace dp\NutgramConversation\conversation\userinput\file;
use dp\NutgramConversation\conversation;
use SergiX44\Nutgram\Telegram\Types as TGTypes;


abstract class ListAbstract extends CollectionAbstract
   {
      protected model\Request $request;
      
      
      
      /**
       * @param string        $saveDir
       * @param model\Request $request
       * @param model\File[]  $filesDefault
       */
      public function __construct(string $saveDir, model\Request $request, iterable $filesDefault=[])
         {
            $this->request = $request;
            parent::__construct($saveDir, $filesDefault);
         }
      
      
      protected function stepStart(?conversation\Intent $intent=null): void
         {
            $this->invokeNextStep('stepRequest');
         }
      
      protected function stepSkip(): void
         {
            $this->end();
         }
      
      
      protected function requestFile(model\Request $req): void
         {
            $acqCnt = count($this->files);
            $this->bot->sendMessage($this->__tp($req->getText($acqCnt>0), $acqCnt), $req->isOptional()||($acqCnt>0)? [
               'reply_markup' => TGTypes\Keyboard\InlineKeyboardMarkup::make()->addRow(
                  $this->buildInlineButtonStep($this->__t('Skip'), 'stepSkip')
               )
            ] : []);
         }
      
      
      protected function getSourceRequest(model\File $forFile): ?model\Request
         {
            return $this->request;
         }
      
      protected function getCurrentRequest(): model\Request
         {
            return $this->request;
         }
      
      protected function getCurrentFileName(): string
         {
            $name = trim($this->getUserMessage()?->caption ?? '');
            
            return !strlen($name)?
               $this->getCurrentRequest()->getName().'_'.(count($this->files)+1) :
               $name;
         }
   }
