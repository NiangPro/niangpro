<?php

// Dépendances interdites restantes (voir PackageBoundariesTest). Objectif : zéro.
return [
    'core: Container → Http\Request (http)' => true,
    'core: Container → Validation\FormRequest (http)' => true,
];
