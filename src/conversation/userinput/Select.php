<?php
namespace dp\NutgramConversation\conversation\userinput;
use dp\NutgramConversation\conversation, dp\NutgramConversation\exception;
use SergiX44\Nutgram\Telegram\Types as TGTypes;


//TODO: refactor, see text\Select
//TODO: optsList limit & pagination
class Select extends BaseAbstract
   {
      protected const INTENT_NS_SELOPT = 'selopt';
      
      
      protected ?string $value = null;
      protected ?string $valueDefault = null;
      
      protected bool    $isCancelable = false;
      
      /** @var string[]|\Stringable[]  [string key => string|\Stringable value] */
      protected array   $options;
      
      
      
      public function __construct(array $options, ?string $default=null, bool $cancelable=false)
         {
            $this->options = $options;
            
            if(($default!==null) && isset($this->options[$default])) $this->valueDefault = $default;
            $this->isCancelable = $cancelable;
            
            parent::__construct();
         }
      
      
      public function getValue(): ?string
         {
            if(!$this->isEnded()) throw new exception\BadMethodCallException('Can not get value from unfinished UserInput collector');
            return $this->value ?? $this->valueDefault;
         }
      
      
      public function reset(): void
         {
            parent::reset();
            $this->value = null;
         }
      
      
      protected function stepStart(?conversation\Intent $intent=null): void
         {
            $markup = TGTypes\Keyboard\InlineKeyboardMarkup::make();
         
            if($this->valueDefault !== null)
               {
                  $text = $this->__t('Select an option or keep current [%s]', $this->options[$this->valueDefault]);
                  $markup->addRow($this->buildInlineButtonEnd($this->__t('Keep current')));
               }
            else
               {
                  $text = $this->__t('Select an option');
                  if($this->isCancelable) $markup->addRow($this->buildInlineButtonEnd($this->__t('Cancel')));
               }
            
            foreach($this->options as $key => $name)
               {
                  if($key === $this->valueDefault) continue;
                  $markup->addRow($this->buildInlineButtonOption($key, $name));
               }
            
            $this->bot->sendMessage($text, ['reply_markup' => $markup]);
         }
      
      
      protected function handleSelectOptionIntent(conversation\Intent $intent): void
         {
            if(!isset($this->options[$intent->action]))
               throw new exception\OutOfBoundsException('Invalid option ID ['.$intent->action.']: no such entry');
         
            $this->value = $intent->action;
            $this->end();
         }
      
      protected function routeIntent(conversation\Intent $intent, ?conversation\Intent $nested = null): void
         {
            if($intent->isNested()) throw new exception\LogicException(
               'Nested Intents must be split() before routing'
            );
            
            switch($intent->namespace)
               {
                  case self::INTENT_NS_SELOPT:
                     $this->handleSelectOptionIntent($intent);
                     break;
                  default:
                     parent::routeIntent($intent, $nested);
               }
         }
      
      protected function buildInlineButtonOption(string $id, string|\Stringable $name): TGTypes\Keyboard\InlineKeyboardButton
         {
            return $this->buildInlineButtonCallback(
               (string)$name,
               new conversation\Intent($id, self::INTENT_NS_SELOPT)
            );
         }
   }
