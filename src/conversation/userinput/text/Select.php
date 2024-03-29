<?php
namespace dp\NutgramConversation\conversation\userinput\text;
use dp\NutgramConversation\conversation, dp\NutgramConversation\exception;
use SergiX44\Nutgram\Telegram\Types as TGTypes;


class Select extends BaseAbstract
   {
      protected const INTENT_NS_SELOPT = 'selopt';
      
      
      protected model\IDictionary   $dictionary;
      protected int                 $optsListLimit;
      
      protected ?string             $lastUserText = null; //last user input
      
      
      
      public function __construct(model\IDictionary $dict, ?model\IDictionaryEntry $default=null, bool $cancelable=false, int $optsLimit=5)
         {
            $this->dictionary = $dict;
            $this->setOptsListLimit($optsLimit);
            
            parent::__construct($default, $cancelable);
         }
      
      public function setOptsListLimit(int $limit): static
         {
            if($limit < 1) throw new exception\OutOfRangeException('Options list limit must be >= 1');
            $this->optsListLimit = $limit;
            return $this;
         }
      
      
      public function reset(): void
         {
            parent::reset();
            $this->lastUserText = null;
         }
      
      
      protected function stepStart(?conversation\Intent $intent=null): void
         {
            $this->lastUserText = null;
            
            if(($this->dictionary instanceof model\IDictionaryExtendable) && $this->dictionary->isEmpty())
               {
                  $markup = TGTypes\Keyboard\InlineKeyboardMarkup::make();
                  $markup->addRow($this->buildInlineButtonStep($this->__t('Add new option'), 'stepNewOption'));
                  if($this->isCancelable) $markup->addRow($this->buildInlineButtonEnd($this->__t('Cancel')));
               
                  $this->sendMessage(
                     $this->__t('Dictionary is empty'),
                     ['reply_markup' => $markup]
                  );
               }
            else
               {
                  $this->sendStartingMessage(
                     'Input part of the desired option name or keep current [%s]',
                     'Input part of the desired option name'
                  )->next('stepSearch');
               }
         }
      
      protected function stepSearch(): void
         {
            try
               {
                  if(!empty($text=$this->getSentText())) $this->lastUserText = $text;
                  if($this->lastUserText !== null) //using current or previous input
                     {
                        $found = 0;
                        $opts = $this->dictionary->search($this->lastUserText, $this->optsListLimit, $found);

                        $markup = TGTypes\Keyboard\InlineKeyboardMarkup::make();
                        foreach($opts as $opt) $markup->addRow($this->buildInlineButtonOption($opt));

                        if(($this->dictionary instanceof model\IDictionaryExtendable) && (count($opts) == $found))
                           $markup->addRow($this->buildInlineButtonStep($this->__t('Add new option'), 'stepNewOption'));
                        if($this->isCancelable) $markup->addRow($this->buildInlineButtonStepStart($this->__t('Cancel')));

                        $this->sendMessage(
                           $found > 0?
                              $this->__tp('Got %u total matches, pick one or refine your search', $found) :
                              $this->__t('Nothing found, refine your search'),
                           ['reply_markup' => $markup]
                        );
                     }
                  else $this->invokeNextStep(self::STEP_START);
               }
            catch(\Exception $ex)
               {
                  $this->sendMessage($this->__t('Incorrect value: %s', $ex->getMessage()));
                  $this->invokeNextStep(self::STEP_START);
               }
         }
      
      protected function handleSelectOptionIntent(conversation\Intent $intent): void
         {     
            $this->value = $this->dictionary->getByID($intent->action);
            if(empty($this->value)) throw new exception\OutOfBoundsException(
               'Invalid option ID ['.$intent->action.']: no such entry'
            );
            
            $this->end();
         }
      
      
      protected function stepNewOption(?conversation\Intent $intent=null): void
         {
            //safeguard
            if(!($this->dictionary instanceof model\IDictionaryExtendable))
               throw new exception\LogicException('Only ExtendableDictionary can accept custom options');
            
            /* @var $subc Input */
            $subc = $this->getNestedConversation(__METHOD__, function() {
               return new Input(
                  valueModel: $this->dictionary->getEntryValueModel(),
                  default: !empty($this->lastUserText) && $this->dictionary->isValidForAddition($this->lastUserText)? $this->lastUserText : null,
                  cancelable: true
               );
            }, [
               'Input new value'                      => $this->__tm('Input full name of the new option to be added'),
               'Input new value or keep current [%s]' => $this->__tm('Input full name of the new option to be added or use query [%s]'),
               'Keep current'                         => $this->__tm('Use query'),
               "New value: %s\nSave?"                 => $this->__tm('Add new option with name "%s"?')
            ]);
            
            try
               {    
                  if($subc->invoke($this->bot, $intent)) //finished
                     {
                        $this->unregNestedConversation(__METHOD__);
                        
                        if(($opt=$subc->getValue()) !== null)
                           {
                              $this->lastUserText = (string)$opt; //will be used for search in case of dup
                              if(!$this->dictionary->isValidForAddition($opt)) throw new exception\UnexpectedValueException(
                                 $this->__t('Such dictionary entry already exists')
                              );
                              $this->value = $this->dictionary->add($opt);
                              $this->end();
                           }
                        else $this->invokeNextStep('stepStart');
                     }
               }
            catch(\Exception $ex)
               {
                  $this->sendMessage($this->__t('Incorrect value: %s', $ex->getMessage()));
                  $this->invokeNextStep('stepSearch'); //allow to escape newOption in case of dup or user changed mind
               }
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
      
      protected function buildInlineButtonOption(model\IDictionaryEntry $option): TGTypes\Keyboard\InlineKeyboardButton
         {
            return $this->buildInlineButtonCallback(
               (string)$option,
               new conversation\Intent($option->getId(), self::INTENT_NS_SELOPT)
            );
         }
   }
