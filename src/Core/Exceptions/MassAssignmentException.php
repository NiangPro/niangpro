<?php

namespace Niang\Core\Exceptions;

/** create()/update() appelé sur un Model qui n'a pas déclaré ses colonnes modifiables ($fillable). */
class MassAssignmentException extends \LogicException
{
}
