<?php
namespace dp\NutgramConversation\conversation;
use dp\NutgramConversation\util\Translator, dp\NutgramConversation\exception;
use SergiX44\Nutgram, SergiX44\Nutgram\Telegram\Types as TGTypes;


abstract class NestedAbstract
   {
      private const CBQ_DATA_MAXLEN    = 64; //TG limitation
   
      //TODO: declare final
      protected const STEP_START       = 'stepStart';
      protected const STEP_END         = 'stepEnd';
      
      //TODO: declare final
      protected const INTENT_NS_STEP   = 'step';
      protected const INTENT_NS_END    = 'end';
      
      
      /**
       * Must not be serialized (due to introduced circular ref)
       * @var Nutgram\Nutgram|null
       */
      protected ?Nutgram\Nutgram $bot;
      
      private Translator         $_translator;
      
      
      private string             $_nextStep  = self::STEP_START;
      private bool               $_isStarted = false;
      private bool               $_isCompleted = false;
      private ?Intent            $_returnIntent = null;
      
      
      /**
       * Default intent to return when this subconv is ended
       * @var Intent|null
       */
      private ?Intent            $_returnIntentDefault = null;
      
      /**
       * Describes full route to this subconv's parent scope from bot entry point
       * Used primarily for callback button's intents
       * @var Intent|null
       */
      private ?Intent            $_parentScopeIntent = null;
      /**
       * Current step/action local Intent, DOES NOT include parentScopeIntent
       * To be assigned (with parentScopeIntent attached) as parentScope to subconvs created by this instance
       * @var Intent|null
       */
      private ?Intent            $_currentScopeIntent = null;
      
      
      /**
       * Active nested conversations, created by this instance
       * @var self[]
       */
      private array              $_nestedConversations = [];
      
      /**
       * Latest bot message with ReplyMarkup attached
       * @var array  [chatId => messageId]
       */
      private array              $_lastMessageWithReplyMarkup = [];
      
      
      
      public function __construct(?Intent $parentScope=null, ?Translator $trans=null)
         {
            if(!empty($parentScope)) $this->setParentScopeIntent($parentScope);
            $this->setTranslator($trans ?? new Translator());
         }
      
      
      final public function setParentScopeIntent(Intent $intent): static
         {
            $this->_parentScopeIntent = $intent;
            return $this;
         }
      
      final public function setReturnIntentDefault(Intent $intent): static
         {
            $this->_returnIntentDefault = $intent;
            return $this;
         }
      
      final public function setTranslator(Translator $trans): static
         {
            $this->_translator = $trans;
            return $this;
         }
      
      
      /**
       * @param Nutgram\Nutgram  $bot
       * @param Intent|null      $intent nested Intent
       * @return bool            Whether this subconv is ended
       * @throws exception\BadMethodCallException
       */
      public function invoke(Nutgram\Nutgram $bot, ?Intent $intent=null): bool
         {
            if($this->isEnded()) throw new exception\BadMethodCallException(
               'Attempting to continue an ended sub-conversation'
            );
         
            try
               {
                  $this->bot = $bot;
                  $this->dispatch($intent);
                  
                  return $this->isEnded();
               }
            catch(\Throwable $ex)
               {
                  $this->reset();
                  throw $ex;
               }
            finally
               {
                  $this->bot = null;   //Closures and objects with circular refs can not be serialized
               }
         }
      
      final protected function dispatch(?Intent $intent=null): void
         {
            /* Avoid handling out-of-scope callback queries
             * In particular ones that is actually starting this subconv
             */
            //FIXME: disable CBQ handling completely for nested non-root conversations
            //CASE: conversation-starting Intent gets doubled (btn pressed 2nd time before removed)
            //REPRODUCE: disable editMessageReplyMarkup(), try start subconv twice
            //TMPFIX: empty($this->_parentScopeIntent) cond, revisit and improve
            if(empty($intent) && empty($this->_parentScopeIntent) && $this->_isStarted && $this->bot->isCallbackQuery())
               {
                  try {$this->bot->answerCallbackQuery();}
                  catch(Nutgram\Telegram\Exceptions\TelegramException $ex)
                     {
                        /* In case of late request arrival (~1min+) answerCallbackQuery() will fail with
                         * TG BadRequest "query is too old and response timeout expired or query ID is invalid"
                         * Just ignore this, request still could be processed
                         */
                     }
                  $intent = Intent::newInstanceFromString($this->bot->callbackQuery()?->data);
               }
            
            $cid = $this->bot->chatId();
            if(!empty($this->_lastMessageWithReplyMarkup[$cid]))
               {
                  try
                     {
                        $this->bot->editMessageReplyMarkup([
                           'chat_id'      => $cid,
                           'message_id'   => $this->_lastMessageWithReplyMarkup[$cid]
                        ]);
                        unset($this->_lastMessageWithReplyMarkup[$cid]);
                        
                     }
                  catch(\Throwable $ex) {/*in case msg is uneditable do nothing*/}
               }
            
            if(!empty($intent)) $this->handleIntent($intent);
            else $this->invokeNextStep();
         }
      
      final protected function invokeNextStep(?string $step=null, ?Intent $intent=null): void
         {
            if($step !== null) $this->next($step);
            
            $this->_isStarted = true;
            $this->_currentScopeIntent = new Intent($this->_nextStep, self::INTENT_NS_STEP);
            $this->{$this->_nextStep}($intent);
         }
         
      final protected function handleIntent(Intent $intent): void
         {
            [$this->_currentScopeIntent, $nestedIntent] = $intent->split();
            $this->routeIntent($this->_currentScopeIntent, $nestedIntent);
         }
         
      protected function routeIntent(Intent $intent, ?Intent $nested=null): void
         {
            if($intent->isNested()) throw new exception\LogicException(
               'Nested Intents must be split() before routing'
            );
            
            switch($intent->namespace)
               {
                  case self::INTENT_NS_STEP:
                     $this->invokeNextStep($intent->action, $nested);
                     break;
                  case self::INTENT_NS_END:
                     $this->end($nested);
                     break;
                  default:
                     throw new exception\LogicException('Invalid intent namespace: '.$intent->namespace);
               }
         }
      
      
      abstract protected function stepStart(?Intent $intent=null): void;
      
      protected function stepEnd(?Intent $intent=null): void
         {
            $this->end($intent);
         }
      
      
      public function reset(): void
         {
            $this->next(self::STEP_START);
            $this->_isStarted = false;
            $this->_isCompleted = false;
            $this->_returnIntent = null;
         }
      
      final protected function next(string $step): void
         {
            if(empty($step) || !method_exists($this, $step))
               throw new exception\LogicException('Invalid step ['.$step.']: no handler method found');
            
            if($this->_nextStep != $step)
               $this->onStepChange($this->_nextStep, $step);
            
            $this->_nextStep = $step;
         }
      
      final protected function end(?Intent $returnIntent=null): void
         {
            $this->_isCompleted = true;
            $this->_returnIntent = $returnIntent ?? $this->_returnIntentDefault;
         }
      
      
      protected function onStepChange(string $fromStep, string $toStep): void
         {
            //to be overriden if necessary
         }
      
      
      final public function isEnded(): bool
         {
            return $this->_isCompleted;
         }
      
      final public function getReturnIntent(): ?Intent
         {
            if(!$this->isEnded()) throw new exception\BadMethodCallException(
               'ReturnIntent can only be requested on completed Action instance'
            );
            return $this->_returnIntent;
         }
      
      
      final protected function getNestedConversation(string $key, callable|self $factoryOrInstance, array $translOverrides=[]): self
         {
            if(!isset($this->_nestedConversations[$key]))
               {
                  if(!($factoryOrInstance instanceof self)) //got callable factory
                     {
                        $inst = $factoryOrInstance();
                        if(!($inst instanceof self)) throw new exception\LogicException(
                           'Nested conversation factory must return an instance of '.self::class.', '.gettype($inst).' returned'
                        );
                     }
                  else $inst = $factoryOrInstance;
                  
                  $inst->setParentScopeIntent(Intent::newInstanceFromString(
                     (string)$this->_currentScopeIntent,
                     $this->_parentScopeIntent
                  ))->setTranslator(
                     empty($translOverrides)? $this->_translator : $this->_translator->newInstanceWithOverride($translOverrides)
                  );
                  
                  $this->_nestedConversations[$key] = $inst;
               }
            return $this->_nestedConversations[$key];
         }
         
      final protected function unregNestedConversation(string $key): void
         {
            unset($this->_nestedConversations[$key]);
         }
      
      
      
      final protected function buildInlineButtonCallback(string $name, Intent $intent): TGTypes\Keyboard\InlineKeyboardButton
         {
            if(!empty($this->_parentScopeIntent)) $intent = Intent::newInstanceFromString((string)$intent, $this->_parentScopeIntent);
            if(strlen($cbd=(string)$intent) > self::CBQ_DATA_MAXLEN) throw new exception\OutOfRangeException( //outofrange -> logic exception
               'Unable to build an inline button for ['.$cbd.']: CallbackQuery data must not exceed '.self::CBQ_DATA_MAXLEN.' chars'
            );
            return TGTypes\Keyboard\InlineKeyboardButton::make($name, callback_data:$cbd);
         }
      
      final protected function buildInlineButtonCurrentRoute(string $name): TGTypes\Keyboard\InlineKeyboardButton
         {
            return $this->buildInlineButtonCallback($name, $this->_currentScopeIntent);
         }
      
      final protected function buildInlineButtonStep(string $name, string $step): TGTypes\Keyboard\InlineKeyboardButton
         {
            return $this->buildInlineButtonCallback($name, new Intent($step, self::INTENT_NS_STEP));
         }
      
      final protected function buildInlineButtonStepStart(string $name): TGTypes\Keyboard\InlineKeyboardButton
         {
            return $this->buildInlineButtonStep($name, self::STEP_START);
         }
      
      final protected function buildInlineButtonStepEnd(string $name): TGTypes\Keyboard\InlineKeyboardButton
         {
            return $this->buildInlineButtonStep($name, self::STEP_END);
         }
      
      final protected function buildInlineButtonEnd(string $name, ?Intent $retIntent=null): TGTypes\Keyboard\InlineKeyboardButton
         {
            $intentEnd = Intent::newInstanceDefault(self::INTENT_NS_END);
            
            return $this->buildInlineButtonCallback(
               $name,
               empty($retIntent)? $intentEnd : Intent::newInstanceFromString((string)$retIntent, $intentEnd)
            );
         }
      
      
      final protected function getUserMessage(): ?TGTypes\Message\Message
         {
            $msg = $this->bot->message(); //may return bot's own message if it's the last one
            return $msg?->from?->is_bot? null : $msg;
         }
      
      protected function getBot(): Nutgram\Nutgram
         {
            return $this->bot;
         }
      
      
      protected function sendMessage(string $text, array $opts=[]): ?TGTypes\Message\Message
         {
            $msg = $this->bot->sendMessage($text, $opts);
            
            if($msg?->reply_markup instanceof TGTypes\Keyboard\InlineKeyboardMarkup)
               $this->_lastMessageWithReplyMarkup[$msg->chat->id] = $msg->message_id;
            
            return $msg;
         }
      
      
      protected function __t(string $fmt, ...$params): string
         {
            return $this->_translator->translate($fmt, ...$params);
         }
         
      protected function __tp(string $fmt, int $number, ...$params): string
         {
            return $this->_translator->translatePlural($fmt, $number, ...$params);
         }
      
      protected function __tm(string $fmt): string|array
         {
            return $this->_translator->map($fmt);
         }
   }
