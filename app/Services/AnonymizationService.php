<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * AnonymizationService - Détecte et anonymise automatiquement les données sensibles
 * Utilisé dans le flux ChatIA pour protéger les données clients avant envoi à l'IA
 */
class AnonymizationService
{
    // Mapping des types de données avec leur placeholder
    private const PLACEHOLDERS = [
        'name' => '[X]',
        'address' => '[ADRESSE]',
        'phone' => '[TÉLÉPHONE]',
        'email' => '[EMAIL]',
        'id' => '[ID]',
        'iban' => '[IBAN]',
        'company' => '[ENTREPRISE]',
    ];

    // Regex patterns pour détecter les données sensibles
    private const PATTERNS = [
        // Noms complets (Au moins 2 mots capitalisés)
        'name' => '/\b([A-Z][a-zàâäéèêëïîôöùûüœæç]+(?:\s+[A-Z][a-zàâäéèêëïîôöùûüœæç]+)+)\b/',
        
        // Adresses complètes
        'address' => '/\b\d+\s+(?:rue|avenue|boulevard|place|chemin|allée|cours|square|impasse|cour|quai|montée|passage|ruelle|impasse|voie|route|côte|boulevard|avenue|rue|place|chemin|allée)\b.*?(?:75|77|78|91|92|93|94|95|59|62|13|69|33|34|30)[\s\d]{2,}/i',
        
        // Numéros de téléphone (formats variés)
        'phone' => '/(?:\+?\d{1,3}[-.\s]?)?\(?(?:\d{2,3})\)?[-.\s]?(?:\d{2,3})[-.\s]?(?:\d{4})/i',
        
        // Adresses email
        'email' => '/\b[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}\b/',
        
        // Numéros de CNI/Passeport/Pièce d'identité
        'id' => '/\b(?:CI|FR|SN|BF|ML|BJ|TG|NE|SL|GH|LR|GN|GW|CV|ST|MZ|MU|SC|MA|DZ|TN|EG|LY|SU|SD|ER|ET|DJ|KE|UG|RW|BW|NA|ZA|ZW|ZM|MW|AO|GQ|CM|CD|CG|GA|BG|KZ|KG|TJ|TM|UZ|AF|PK|BD|NP|LK|MM|LA|KH|VN|TH|MY|SG|BN|PH|ID|TL|PG|NC|VU|FJ|SB|KI|TO|WS|PW|FM|MH|MR|MA|DZ|TN|LY|SD|SS|ET|ER|DJ|SO|KE|UG|RW|BW|ZA|NA|MW|ZM|ZW|AO|MZ|GA|CG|CD|CM|CH|CZ|DE|DK|ES|FI|FR|GB|GR|HU|IE|IT|LU|MT|NL|PL|PT|RO|SE|SK|AT|BE|HR|CY|LT|LV|SI)[-\s]?[\dA-Z]{6,15}\b/',
        
        // IBAN
        'iban' => '/\b[A-Z]{2}\d{2}[\dA-Z]{1,30}\b/',
    ];

    /**
     * Analyser et anonymiser un document
     * 
     * @param string $content Contenu du document à anonymiser
     * @return array [
     *     'anonymized_content' => string, // Contenu anonymisé
     *     'detections' => array,          // Liste des données détectées
     *     'mapping' => array,             // Mapping original → placeholder
     *     'has_sensitive_data' => bool,   // Si données sensibles trouvées
     * ]
     */
    public function anonymizeDocument(string $content): array
    {
        if (empty($content)) {
            return [
                'anonymized_content' => $content,
                'detections' => [],
                'mapping' => [],
                'has_sensitive_data' => false,
            ];
        }

        // 1. Détecter les données sensibles
        $detections = $this->detectSensitiveData($content);

        if (empty($detections)) {
            return [
                'anonymized_content' => $content,
                'detections' => [],
                'mapping' => [],
                'has_sensitive_data' => false,
            ];
        }

        // 2. Créer mapping des remplacements
        $mapping = [];
        $anonymizedContent = $content;

        foreach ($detections as $detection) {
            $original = $detection['value'];
            $type = $detection['type'];
            $placeholder = self::PLACEHOLDERS[$type] ?? '[CONFIDENTIEL]';

            // Éviter les doublons dans le mapping
            if (!isset($mapping[$original])) {
                $mapping[$original] = $placeholder;

                // Remplacer dans le contenu (case-insensitive pour certains cas)
                $anonymizedContent = str_replace(
                    $original,
                    $placeholder,
                    $anonymizedContent
                );
            }
        }

        Log::info('Document anonymisé', [
            'detections_count' => count($detections),
            'mappings_count' => count($mapping),
            'original_length' => strlen($content),
            'anonymized_length' => strlen($anonymizedContent),
        ]);

        return [
            'anonymized_content' => $anonymizedContent,
            'detections' => $detections,
            'mapping' => $mapping,
            'has_sensitive_data' => true,
        ];
    }

    /**
     * Détecter les données sensibles dans le contenu
     * 
     * @param string $content
     * @return array Liste des détections [['type' => string, 'value' => string], ...]
     */
    private function detectSensitiveData(string $content): array
    {
        $detections = [];
        $detectedValues = []; // Pour éviter les doublons

        foreach (self::PATTERNS as $type => $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                foreach ($matches[0] as $match) {
                    $match = trim($match);
                    
                    // Ignorer les valeurs trop courtes ou déjà détectées
                    if (strlen($match) >= 3 && !in_array($match, $detectedValues)) {
                        // Filtrer les faux positifs courants
                        if ($this->isValidDetection($type, $match)) {
                            $detections[] = [
                                'type' => $type,
                                'value' => $match,
                                'placeholder' => self::PLACEHOLDERS[$type] ?? '[CONFIDENTIEL]',
                            ];
                            $detectedValues[] = $match;
                        }
                    }
                }
            }
        }

        return $detections;
    }

    /**
     * Valider si une détection est un vrai positif (pas un faux positif)
     * 
     * @param string $type Type de détection
     * @param string $value Valeur détectée
     * @return bool
     */
    private function isValidDetection(string $type, string $value): bool
    {
        // Filtrer les faux positifs par type
        return match ($type) {
            'email' => $this->isValidEmail($value),
            'phone' => $this->isValidPhone($value),
            'iban' => $this->isValidIBAN($value),
            'address' => $this->isValidAddress($value),
            default => true,
        };
    }

    private function isValidEmail(string $email): bool
    {
        // Au minimum une structure email correcte
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function isValidPhone(string $phone): bool
    {
        // Vérifier qu'il y a au moins 8 chiffres
        $digits = preg_replace('/\D/', '', $phone);
        return strlen($digits) >= 8;
    }

    private function isValidIBAN(string $iban): bool
    {
        // Format IBAN : 2 lettres + 2 chiffres + alphanumérique
        $iban = strtoupper($iban);
        return preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]{1,30}$/', $iban) === 1;
    }

    private function isValidAddress(string $address): bool
    {
        // Au minimum 10 caractères pour une adresse valide
        return strlen($address) >= 10;
    }

    /**
     * Ré-identifier un texte en utilisant le mapping (optionnel)
     * Permet de restaurer les données originales si besoin
     * 
     * @param string $anonymizedContent
     * @param array $mapping
     * @return string
     */
    public function reIdentify(string $anonymizedContent, array $mapping): string
    {
        $reidentified = $anonymizedContent;

        // Inverser le mapping pour restaurer
        $reverseMapping = array_flip($mapping);

        foreach ($reverseMapping as $placeholder => $original) {
            $reidentified = str_replace($placeholder, $original, $reidentified);
        }

        return $reidentified;
    }

    /**
     * Résumé des données détectées (pour logs/analytics)
     * 
     * @param array $detections
     * @return array Résumé par type
     */
    public function summarizeDetections(array $detections): array
    {
        $summary = [];

        foreach ($detections as $detection) {
            $type = $detection['type'];
            if (!isset($summary[$type])) {
                $summary[$type] = 0;
            }
            $summary[$type]++;
        }

        return $summary;
    }
}
