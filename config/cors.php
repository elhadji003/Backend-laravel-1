<?php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'], // Routes CORS activées
    'allowed_methods' => ['*'],                // Autoriser toutes les méthodes HTTP
    'allowed_origins' => ['*'],                // Autoriser toutes les origines
    'allowed_origins_patterns' => [],          // Aucun filtre par motif
    'allowed_headers' => ['*'],                // Autoriser tous les en-têtes
    'exposed_headers' => [],                   // Pas d'en-têtes exposés
    'max_age' => 0,                            // Pas de mise en cache
    'supports_credentials' => false,           // Désactiver les cookies partagés
];
