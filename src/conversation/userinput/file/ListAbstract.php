<?php
namespace dp\NutgramConversation\conversation\userinput\file;
use dp\NutgramConversation\conversation;


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
            
            if(!empty($this->files))
               $this->request->setRequired(false);
         }
      
      
      protected function stepStart(?conversation\Intent $intent=null): void
         {
            $this->invokeNextStep('stepRequest');
         }
      
      protected function stepSkip(): void
         {
            $this->end();
         }
      
      
      protected function onFileAcquired(model\File $file): void
         {
            //required means at least 1 file, we got it now
            $this->getCurrentRequest()->setRequired(false);
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
