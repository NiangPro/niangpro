<?php

namespace Niang\Core;

/**
 * Deux drivers, pilotés par MAIL_MAILER dans .env ('log' par défaut) :
 *  - 'log' : écrit le contenu de l'email dans storage/logs/ via Log — pratique en développement,
 *    sans configuration ni serveur SMTP.
 *  - 'array' : garde les emails envoyés en mémoire du process, pour les assertions de test
 *    (voir fake()/sent()).
 * Pas d'envoi SMTP réel : ça demanderait soit une extension, soit un client écrit à la main que
 * cet environnement ne peut pas vérifier contre un vrai serveur — mieux vaut l'absence assumée
 * qu'une implémentation non testée. Le prochain driver (SMTP ou une API tierce) s'ajoute par un
 * cas de plus dans dispatch(), sans toucher Mailable/PendingMail.
 */
class Mail
{
    /** @var array<int, array{to: string, mailable: Mailable}> */
    private static array $sent = [];
    private static bool $faked = false;

    public static function to(string $address): PendingMail
    {
        return new PendingMail($address);
    }

    /** @internal appelé par PendingMail::send() */
    public static function dispatch(string $to, Mailable $mailable): void
    {
        if (self::$faked || Env::get('MAIL_MAILER', 'log') === 'array') {
            self::$sent[] = ['to' => $to, 'mailable' => $mailable];
            return;
        }

        // Le corps complet est loggé (utile en dev pour lire un lien de vérification sans boîte mail
        // réelle) : ne pas garder MAIL_MAILER=log en production si vos emails contiennent des secrets.
        Log::info('Email à {to} : {subject}', [
            'to' => $to,
            'subject' => $mailable->subject(),
            'body' => $mailable->body(),
        ]);
    }

    /** Bascule sur le driver 'array' pour la suite du test, quel que soit MAIL_MAILER dans .env. */
    public static function fake(): void
    {
        self::$faked = true;
        self::$sent = [];
    }

    /** @return array<int, array{to: string, mailable: Mailable}> rempli seulement après fake() ou avec MAIL_MAILER=array */
    public static function sent(): array
    {
        return self::$sent;
    }

    /** @internal remet Mail dans son état par défaut — à appeler en tearDown entre deux tests. */
    public static function reset(): void
    {
        self::$faked = false;
        self::$sent = [];
    }
}
