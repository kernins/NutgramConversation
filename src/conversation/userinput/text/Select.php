<?php
namespace dp\NutgramConversation\conversation\userinput\text;
use dp\NutgramConversation\conversation, dp\NutgramConversation\exception;
use SergiX44\Nutgram\Telegram\Types as TGTypes;


class Select extends BaseAbstract
   {
      protected const INTENT_NS_SELOPT = 'selopt';
      
      
      protected model\IDictionary   $dictionary;
      protected int                 $optsListLimit;
      
      protected ?string             $pendingDictEntry = null;
      
      
      
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
            $this->pendingDictEntry = null;
         }
      
      
      protected function stepStart(?conversation\Intent $intent=null): void
         {
            $this->sendStartingMessage(
               'Input part of the desired option name or keep current [%s]',
               'Input part of the desired option name'
            )->next('stepSearch');
         }
      
      protected function stepSearch(): void
         {
            try
               {
                  if(($text=$this->getSentText()) !== null)
                     {
                        $found = 0;
                        $opts = $this->dictionary->search($text, $this->optsListLimit, $found);

                        $markup = TGTypes\Keyboard\InlineKeyboardMarkup::make();
                        foreach($opts as $opt) $markup->addRow($this->buildInlineButtonOption($opt));

                        if(($this->dictionary instanceof model\IDictionaryExtendable) && (count($opts) == $found))
                           $markup->addRow($this->buildInlineButtonStep($this->__t('Add new option'), 'stepNewOption'));

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
            if(!is_numeric($intent->action)) throw new exception\UnexpectedValueException(
               'Invalid SelectOption intent received: invalid option ID: '.$intent->action
            );
                     
            $this->value = $this->dictionary->getByID((int)$intent->action);
            if(empty($this->value)) throw new exception\OutOfBoundsException(
               'Invalid option ID ['.$intent->action.']: no such entry'
            );
            
            $this->end();
         }
      
      
      protected function stepNewOption(): void
         {
            //TODO: refactor, use nested text\Input
         
            //safeguard
            if(!($this->dictionary instanceof model\IDictionaryExtendable))
               throw new exception\LogicException('Only ExtendableDictionary can accept custom options');
            
            $this->sendMessage($this->__t('Input full name of the new option to be added'), [
               'reply_markup' => TGTypes\Keyboard\InlineKeyboardMarkup::make()->addRow(
                  $this->buildInlineButtonStepStart($this->__t('Cancel'))
               )
            ]);
            
            $this->next('stepNewOptionAcquire');
         }
      
      protected function stepNewOptionAcquire(): void
         {
            try
               {
                  if(($text=$this->getSentText()) !== null)
                     {
                        if(!$this->dictionary->isValidForAddition($text)) throw new exception\UnexpectedValueException(
                           $this->__t('Such dictionary entry already exists')
                        );
                        
                        $this->pendingDictEntry = $text;
                        $this->invokeNextStep('stepNewOptionConfirm');
                     }
                  else $this->invokeNextStep('stepNewOption');
               }
            catch(\Exception $ex)
               {
                  $this->sendMessage($this->__t('Incorrect value: %s', $ex->getMessage()));
                  $this->invokeNextStep('stepNewOption');
               }
         }
      
      protected function stepNewOptionConfirm(): void
         {
            $this->sendMessage($this->__t("New value: %s\nSave?", $this->pendingDictEntry), [
               'reply_markup' => TGTypes\Keyboard\InlineKeyboardMarkup::make()->addRow(
                  $this->buildInlineButtonStep($this->__t('Yes'), 'stepNewOptionSave'),
                  $this->buildInlineButtonStep($this->__t('No'), 'stepNewOption')
               )
            ]);
         }
      
      protected function stepNewOptionSave(): void
         {
            try
               {
                  $this->value = $this->dictionary->add($this->pendingDictEntry);
                  $this->pendingDictEntry = null;
                  $this->end();
               }
            catch(\Exception $ex)
               {
                  $this->sendMessage($this->__t('Failed to add new option to dictionary: %s', $ex->getMessage()));
                  $this->invokeNextStep('stepNewOption');
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
