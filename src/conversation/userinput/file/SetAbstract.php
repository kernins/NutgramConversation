<?php
namespace dp\NutgramConversation\conversation\userinput\file;
use dp\NutgramConversation\conversation, dp\NutgramConversation\exception;
use SergiX44\Nutgram\Telegram\Types as TGTypes;


abstract class SetAbstract extends CollectionAbstract
   {
      /** @var model\Request[]   [idx => Request] */
      protected array   $requests = [];

      protected int     $currReqIdx = 0;
      
      
      
      /**
       * @param string           $saveDir
       * @param model\Request[]  $requests
       * @param model\File[]     $filesDefault
       */
      public function __construct(string $saveDir, iterable $requests, iterable $filesDefault=[])
         {
            $reqNames = [];
            foreach($requests as $req)
               {
                  if(isset($reqNames[$req->getName()]))
                     throw new exception\UnexpectedValueException('Duplicate request name: '.$req->getName());
                  
                  $reqNames[$req->getName()] = true;
                  $this->requests[] = $req;
               }
            parent::__construct($saveDir, $filesDefault);
         }
      
      protected function prepareDefaultFile(model\File $file): void
         {
            parent::prepareDefaultFile($file);
            $file->setNeedsConfirmation(true); //ask for replacement
         }
      
      
      public function reset(): void
         {
            parent::reset();
            $this->currReqIdx = 0;
         }
      
      
      protected function stepStart(?conversation\Intent $intent=null): void
         {
            $cntReq = count($this->requests);
            while( //skipping completed requests
               ($this->currReqIdx < $cntReq) && 
               isset($this->files[$cfn=$this->getCurrentFileName()]) &&
               !$this->files[$cfn]->getNeedsConfirmation()
            ) $this->currReqIdx++;
            
            if($this->currReqIdx < $cntReq) $this->invokeNextStep('stepRequest');
            elseif(($cntSkp=$cntReq-count($this->files)) > 1)
               {
                  $this->sendMessage($this->__tp('Would like to upload any of %u skipped entries?', $cntSkp), [
                     'reply_markup' => TGTypes\Keyboard\InlineKeyboardMarkup::make()->addRow(
                        $this->buildInlineButtonStep($this->__t('Yes'), 'stepStartover'),
                        $this->buildInlineButtonEnd($this->__t('No'))
                     )
                  ]);
               }
            else $this->end();
         }
      
      protected function stepStartover(): void
         {
            $this->currReqIdx = 0;
            $this->invokeNextStep(self::STEP_START);
         }
      
      protected function stepSkip(): void
         {
            if(isset($this->files[$cfn=$this->getCurrentFileName()]))
               $this->files[$cfn]->setNeedsConfirmation(false); //user skipped -> confirmed
            
            $this->currReqIdx++;
            $this->invokeNextStep(self::STEP_START);
         }
      
      
      protected function requestFile(model\Request $req): void
         {
            $file = $this->files[$req->getName()] ?? null;
            $this->sendMessage($req->getText($file?->getNeedsConfirmation() ?? false), $req->isOptional()||!empty($file)? [
               'reply_markup' => TGTypes\Keyboard\InlineKeyboardMarkup::make()->addRow(
                  $this->buildInlineButtonStep($this->__t(empty($file)? 'Skip':'Keep current'), 'stepSkip')
               )
            ] : []);
         }
      
      protected function onFileAcquired(model\File $file): void
         {
            $this->currReqIdx++;
            parent::onFileAcquired($file);
         }
      
      
      protected function getSourceRequest(model\File $forFile): ?model\Request
         {
            foreach($this->requests as $req)
               {
                  if($req->getName() === $forFile->getName()) return $req;
               }
            return null;
         }
      
      protected function getCurrentRequest(): model\Request
         {
            if(empty($this->requests[$this->currReqIdx]))
               throw new exception\LogicException('getCurrentRequest() called with out-of-bounds ptr: '.$this->currReqIdx);
         
            return $this->requests[$this->currReqIdx];
         }
      
      protected function getCurrentFileName(): string
         {
            return $this->getCurrentRequest()->getName();
         }
   }
