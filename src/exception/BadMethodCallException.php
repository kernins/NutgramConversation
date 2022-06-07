<?php
namespace dp\NutgramConversation\exception;
use dp\NutgramConversation\IException;


class BadMethodCallException extends \BadMethodCallException implements IException {}
