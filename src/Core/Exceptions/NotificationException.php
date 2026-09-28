<?php

namespace Niang\Core\Exceptions;

/** Canal inconnu, méthode toMail()/toDatabase()/toWebhook() manquante, webhook refusé... */
class NotificationException extends \RuntimeException
{
}
