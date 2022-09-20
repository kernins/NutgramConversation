<?php
namespace dp\NutgramConversation\conversation\userinput\text;
use dp\NutgramConversation\conversation, dp\NutgramConversation\exception;
use SergiX44\Nutgram\Telegram\Types as TGTypes;


//TODO: validator support? RegExp? Or define as a class?
class Input extends BaseAbstract
   {
      protected const VALMDL_REQUIRE_IFACE   = model\IValueModel::class;
      protected const VALMDL_REQUIRE_SUBCLS  = null;
   
      protected ?string $valueModel = null;
      
      
      
      public function __construct(?string $valueModel=null, string|\Stringable|null $default=null, bool $cancelable=false)
         {
            if(!empty($valueModel))
               {
                  try {$r = new \ReflectionClass($valueModel);}
                  catch(\ReflectionException $ex)
                     {
                        throw new exception\InvalidArgumentException(
                           'Invalid ValueModel FQN given: '.$valueModel,
                           $ex->getCode(),
                           $ex
                        );
                     }
                  
                  if(!empty(static::VALMDL_REQUIRE_IFACE) && !$r->implementsInterface(static::VALMDL_REQUIRE_IFACE))
                     throw new exception\DomainException('ValueModel must implement '.static::VALMDL_REQUIRE_IFACE);
                  if(!empty(static::VALMDL_REQUIRE_SUBCLS) && !$r->isSubclassOf(static::VALMDL_REQUIRE_SUBCLS))
                     throw new exception\DomainException('ValueModel must be a subclass of '.static::VALMDL_REQUIRE_SUBCLS);
                  
                  $this->valueModel = $valueModel;
                  
                  if(($default!==null) && !($default instanceof $this->valueModel))
                     {
                        try {$default = $this->valueModel::newInstanceFromString((string)$default);}
                        catch(\Exception $ex) {$default = null;}
                     }
               }
            
            parent::__construct($default, $cancelable);
         }
      
      
      
      protected function stepStart(?conversation\Intent $intent=null): void
         {
            $this->sendStartingMessage(
               'Input new value or keep current [%s]',
               'Input new value'
            )->next('stepAcquire');
         }
         
      protected function stepAcquire(): void
         {
            try
               {
                  if(($text=$this->getSentText()) !== null)
                     {
                        $this->value = !empty($this->valueModel)?
                           $this->valueModel::newInstanceFromString($text) :
                           $text;
                        
                        $this->validateValue(); //TODO: refactor? formalize? use Validator class?
                        $this->invokeNextStep('stepConfirm');
                     }
                  else $this->invokeNextStep(self::STEP_START);
               }
            catch(\Exception $ex)
               {
                  $this->sendMessage($this->__t('Incorrect value: %s', $ex->getMessage()));
                  $this->invokeNextStep(self::STEP_START);
               }
         }
         
      protected function validateValue(): void
         {
         
         }
         
      protected function stepConfirm(): void
         {
            $this->sendMessage($this->__t("New value: %s\nSave?", $this->value), [
               'reply_markup' => TGTypes\Keyboard\InlineKeyboardMarkup::make()->addRow(
                  $this->buildInlineButtonEnd($this->__t('Yes')),
                  $this->buildInlineButtonStepStart($this->__t('No'))
               )
            ]);
         }
   }
