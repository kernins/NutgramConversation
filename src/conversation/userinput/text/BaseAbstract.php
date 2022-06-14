<?php
namespace dp\NutgramConversation\conversation\userinput\text;
use dp\NutgramConversation\conversation, dp\NutgramConversation\exception;
use SergiX44\Nutgram\Telegram\Types as TGTypes;


abstract class BaseAbstract extends conversation\userinput\BaseAbstract
   {
      protected string|\Stringable|null   $value = null;
      protected string|\Stringable|null   $valueDefault = null;
      
      protected bool                      $isCancelable = false;
      
      
      
      public function __construct(string|\Stringable|null $default=null, bool $cancelable=false)
         {
            if($default !== null) $this->valueDefault = $default;
            $this->isCancelable = $cancelable;
               
            parent::__construct();
         }
      
      
      public function getValue(): string|\Stringable|null //nullable due to isCancellable
         {
            if(!$this->isEnded()) throw new exception\BadMethodCallException('Can not get value from unfinished UserInput collector');
            return $this->value ?? $this->valueDefault;
         }
      
      public function reset(): void
         {
            parent::reset();
            $this->value = null;
         }
      
      
      protected function sendStartingMessage(string $textNewOrKeep, string $textNew): static
         {
            if($this->valueDefault !== null)
               {
                  $text = $this->__t($textNewOrKeep, (string)$this->valueDefault);
                  $btn = $this->buildInlineButtonEnd($this->__t('Keep current'));
               }
            else
               {
                  $text = $this->__t($textNew);
                  if($this->isCancelable) $btn = $this->buildInlineButtonEnd($this->__t('Cancel'));
               }
         
            $this->sendMessage($text, !empty($btn)? [
               'reply_markup' => TGTypes\Keyboard\InlineKeyboardMarkup::make()->addRow($btn)
            ] : []);
            
            return $this;
         }
      
      
      protected function getSentText(): ?string
         {
            if(($text=$this->getUserMessage()?->text) !== null)
               {
                  if(!strlen($text=trim($text))) throw new exception\UnexpectedValueException(
                     $this->__t('No meaningful text received')
                  );
               }
            return $text;
         }
   }
