<?php

namespace App\Services;

use App\Models\User;
use App\Models\Conversation;
use Illuminate\Support\Facades\Log;

class UserContextProfiler
{
    /**
     * Construire le contexte utilisateur pour le prompt AI
     * 
     * @param User $user
     * @param array $conversationHistory
     * @return string
     */
    public function buildUserContextPrompt(User $user, array $conversationHistory = []): string
    {
        $profile = $this->analyzeUserProfile($user, $conversationHistory);
        
        $context = "=== PROFIL UTILISATEUR ===\n";
        
        // Type d'utilisateur
        $context .= "Type: " . $this->getUserTypeDescription($profile['type']) . "\n";
        
        // Niveau de connaissance
        $context .= "Niveau juridique: " . $this->getKnowledgeLevelDescription($profile['knowledge_level']) . "\n";

        if ($profile['type'] === 'etudiant') {
            $context .= "Niveau académique: " . $this->getAcademicLevelFromKnowledge($profile['knowledge_level']) . "\n";
        }
        
        // Domaines d'intérêt
        if (!empty($profile['domains'])) {
            $context .= "Domaines d'intérêt: " . implode(', ', $profile['domains']) . "\n";
        }
        
        // Statistiques d'interaction
        if (!empty($profile['stats'])) {
            $context .= "Historique: {$profile['stats']['total_questions']} questions posées, ";
            $context .= "domaine principal: {$profile['stats']['primary_domain']}\n";
        }
        
        // Instructions adaptées
        $context .= "\n=== INSTRUCTIONS D'ADAPTATION ===\n";
        $context .= $this->getAdaptationInstructions($profile);
        
        $context .= "===========================\n\n";
        
        Log::info('User context built', [
            'user_id' => $user->id,
            'profile_type' => $profile['type'],
            'knowledge_level' => $profile['knowledge_level'],
        ]);
        
        return $context;
    }

    /**
     * Analyser le profil utilisateur
     * 
     * @param User $user
     * @param array $conversationHistory
     * @return array
     */
    private function analyzeUserProfile(User $user, array $conversationHistory): array
    {
        // Type d'utilisateur
        $type = $user->mobile_role ?? $this->inferUserType($user, $conversationHistory);
        
        // Niveau de connaissance
        $knowledgeLevel = $user->knowledge_level ?? $this->inferKnowledgeLevel($conversationHistory);
        
        // Domaines d'intérêt
        $domains = $this->extractDomainsOfInterest($user, $conversationHistory);
        
        // Statistiques
        $stats = $this->getInteractionStats($user, $conversationHistory);
        
        return [
            'type' => $type,
            'knowledge_level' => $knowledgeLevel,
            'domains' => $domains,
            'stats' => $stats,
        ];
    }

    /**
     * Inférer le type d'utilisateur basé sur les conversations
     * 
     * @param User $user
     * @param array $conversationHistory
     * @return string
     */
    private function inferUserType(User $user, array $conversationHistory): string
    {
        // Par défaut: particulier
        if (empty($conversationHistory)) {
            return 'particulier';
        }

        $messages = array_map(fn($msg) => strtolower($msg['content'] ?? ''), $conversationHistory);
        $allText = implode(' ', $messages);

        // Indices pour Avocat
        $lawyerIndicators = ['client', 'dossier', 'plaidoirie', 'tribunal', 'jurisprudence', 'citation', 'référence légale'];
        $lawyerScore = 0;
        foreach ($lawyerIndicators as $indicator) {
            if (stripos($allText, $indicator) !== false) {
                $lawyerScore++;
            }
        }

        // Indices pour Étudiant
        $studentIndicators = ['cours', 'examen', 'étude', 'apprendre', 'expliquer', 'comprendre', 'définition'];
        $studentScore = 0;
        foreach ($studentIndicators as $indicator) {
            if (stripos($allText, $indicator) !== false) {
                $studentScore++;
            }
        }

        // Indices pour Entreprise
        $businessIndicators = ['entreprise', 'société', 'contrat commercial', 'employé', 'salarié', 'fiscal', 'comptabilité'];
        $businessScore = 0;
        foreach ($businessIndicators as $indicator) {
            if (stripos($allText, $indicator) !== false) {
                $businessScore++;
            }
        }

        // Déterminer le type avec le score le plus élevé
        $scores = [
            'avocat' => $lawyerScore,
            'etudiant' => $studentScore,
            'entreprise' => $businessScore,
        ];

        arsort($scores);
        $topType = array_key_first($scores);

        return $scores[$topType] > 0 ? $topType : 'particulier';
    }

    /**
     * Inférer le niveau de connaissance juridique
     * 
     * @param array $conversationHistory
     * @return string
     */
    private function inferKnowledgeLevel(array $conversationHistory): string
    {
        $totalQuestions = count($conversationHistory);

        if ($totalQuestions === 0) {
            return 'debutant';
        }

        $messages = array_map(fn($msg) => strtolower($msg['content'] ?? ''), $conversationHistory);
        $allText = implode(' ', $messages);

        // Termes juridiques avancés
        $advancedTerms = [
            'jurisprudence', 'doctrine', 'opposabilité', 'novation', 'subrogation',
            'prescription acquisitive', 'dol', 'vice du consentement', 'nullité relative',
            'acte authentique', 'force probante', 'voies de recours', 'cassation'
        ];

        $advancedTermCount = 0;
        foreach ($advancedTerms as $term) {
            if (stripos($allText, $term) !== false) {
                $advancedTermCount++;
            }
        }

        // Questions simples
        $basicPatterns = [
            '/c\'est quoi/i',
            '/qu\'est[- ]ce que/i',
            '/comment\s+faire\s+pour/i',
            '/je\s+ne\s+comprends\s+pas/i',
        ];

        $basicQuestionCount = 0;
        foreach ($basicPatterns as $pattern) {
            if (preg_match($pattern, $allText)) {
                $basicQuestionCount++;
            }
        }

        // Détermination du niveau
        if ($advancedTermCount >= 3 || $totalQuestions > 20) {
            return 'expert';
        } elseif ($advancedTermCount >= 1 || $totalQuestions > 5) {
            return 'intermediaire';
        } else {
            return 'debutant';
        }
    }

    /**
     * Extraire les domaines d'intérêt de l'utilisateur
     * 
     * @param User $user
     * @param array $conversationHistory
     * @return array
     */
    private function extractDomainsOfInterest(User $user, array $conversationHistory): array
    {
        // Si déjà enregistré dans la base
        if ($user->domains_of_interest) {
            $stored = json_decode($user->domains_of_interest, true);
            if (is_array($stored) && !empty($stored)) {
                return $stored;
            }
        }

        if (empty($conversationHistory)) {
            return [];
        }

        $messages = array_map(fn($msg) => strtolower($msg['content'] ?? ''), $conversationHistory);
        $allText = implode(' ', $messages);

        $domains = [
            'Droit du travail' => ['travail', 'employé', 'licenciement', 'contrat de travail', 'salarié', 'congé'],
            'Droit commercial' => ['commercial', 'société', 'sarl', 'entreprise', 'commerce', 'registre de commerce'],
            'Droit civil' => ['mariage', 'succession', 'divorce', 'famille', 'héritage', 'testament'],
            'Droit foncier' => ['terrain', 'propriété', 'titre foncier', 'cadastre', 'bail', 'location'],
            'Droit pénal' => ['pénal', 'infraction', 'délit', 'crime', 'sanction', 'tribunal correctionnel'],
            'Droit fiscal' => ['impôt', 'taxe', 'déclaration fiscale', 'tva', 'contribution', 'fisc'],
            'Droit des contrats' => ['contrat', 'obligation', 'clause', 'résiliation', 'accord', 'convention'],
        ];

        $detectedDomains = [];
        foreach ($domains as $domain => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                if (stripos($allText, $keyword) !== false) {
                    $score++;
                }
            }
            if ($score >= 2) {
                $detectedDomains[$domain] = $score;
            }
        }

        // Trier par score décroissant
        arsort($detectedDomains);

        // Retourner top 3
        return array_keys(array_slice($detectedDomains, 0, 3));
    }

    /**
     * Obtenir les statistiques d'interaction
     * 
     * @param User $user
     * @param array $conversationHistory
     * @return array
     */
    private function getInteractionStats(User $user, array $conversationHistory): array
    {
        $domains = $this->extractDomainsOfInterest($user, $conversationHistory);
        
        return [
            'total_questions' => count($conversationHistory),
            'primary_domain' => $domains[0] ?? 'Général',
            'experience_days' => $user->created_at ? now()->diffInDays($user->created_at) : 0,
        ];
    }

    /**
     * Obtenir la description du type d'utilisateur
     * 
     * @param string $type
     * @return string
     */
    private function getUserTypeDescription(string $type): string
    {
        return match($type) {
            'etudiant' => 'Étudiant en droit (apprentissage académique)',
            'avocat' => 'Professionnel du droit / Avocat',
            'entreprise' => 'Professionnel d\'entreprise / Chef d\'entreprise',
            'particulier' => 'Particulier / Citoyen',
            default => 'Utilisateur général',
        };
    }

    /**
     * Obtenir la description du niveau de connaissance
     * 
     * @param string $level
     * @return string
     */
    private function getKnowledgeLevelDescription(string $level): string
    {
        return match($level) {
            'debutant' => 'Débutant (peu de connaissances juridiques)',
            'intermediaire' => 'Intermédiaire (connaissances de base solides)',
            'expert' => 'Avancé/Expert (connaissances juridiques approfondies)',
            default => 'Non évalué',
        };
    }

    /**
     * Mapper le niveau de connaissance vers L1/L2/L3 pour étudiants
     *
     * @param string $level
     * @return string
     */
    private function getAcademicLevelFromKnowledge(string $level): string
    {
        return match($level) {
            'debutant' => 'L1',
            'intermediaire' => 'L2',
            'expert' => 'L3',
            default => 'L1',
        };
    }

    /**
     * Générer les instructions d'adaptation pour l'IA
     * 
     * @param array $profile
     * @return string
     */
    private function getAdaptationInstructions(array $profile): string
    {
        $instructions = "";

        // Adaptation selon le type
        switch ($profile['type']) {
            case 'etudiant':
                $instructions .= "- Privilégier des explications pédagogiques avec définitions et exemples\n";
                $instructions .= "- Citer les sources légales pour faciliter l'apprentissage\n";
                $instructions .= "- Encourager la réflexion critique\n";
                $instructions .= "- MODE RÉVISION ACTIVE (par défaut): ne pas donner la réponse directement\n";
                $instructions .= "- EXCEPTION: si l'utilisateur demande explicitement de rédiger une dissertation ou un exposé,\n";
                $instructions .= "  fournir une rédaction complète et structurée (introduction, problématique, plan,\n";
                $instructions .= "  développement, conclusion), avec références si pertinentes\n";
                $instructions .= "- Poser des questions guidées progressives et attendre les réponses\n";
                $instructions .= "- Corriger sans humilier, ton bienveillant de tuteur\n";
                $instructions .= "- Adapter la profondeur selon le niveau académique (L1/L2/L3)\n";
                $instructions .= "- Si la demande commence par 'Explique-moi', appliquer la structure: \n";
                $instructions .= "  Q1: Avant de répondre, sais-tu ce que c'est que [concept A] ? (attendre)\n";
                $instructions .= "  Q2: Peux-tu donner un exemple de [concept A] ? (attendre)\n";
                $instructions .= "  Q3: Maintenant, [concept B] s'ajoute à cela... Comment ? (attendre)\n";
                $instructions .= "  Puis: Voici la réponse complète...\n";
                break;

            case 'avocat':
                $instructions .= "- Fournir des réponses précises avec références légales complètes\n";
                $instructions .= "- Mentionner la jurisprudence pertinente si disponible\n";
                $instructions .= "- Adopter un ton professionnel et technique\n";
                $instructions .= "- Si l'utilisateur demande explicitement de rédiger une dissertation ou un exposé,\n";
                $instructions .= "  fournir une rédaction complète et structurée (introduction, problématique, plan,\n";
                $instructions .= "  développement, conclusion), avec références si pertinentes\n";
                $instructions .= "- ANONYMISATION ACTIVÉE: commencer chaque traitement de document par [ANONYMISATION ACTIVE]\n";
                $instructions .= "- Remplacer tous les noms par [X], [Y], etc.\n";
                $instructions .= "- CITATIONS STRICTES: chaque affirmation juridique doit inclure\n";
                $instructions .= "  l'article exact, la source, la date de dernière mise à jour,\n";
                $instructions .= "  et si modifié récemment: ⚠️ Modifié par [Décret] du [date]\n";
                $instructions .= "- CONTRATS: identifier le type, demander juridiction/parties/éléments clés,\n";
                $instructions .= "  générer un squelette, ajouter un avertissement de validation par avocat,\n";
                $instructions .= "  proposer clauses optionnelles et lister les risques non couverts\n";
                $instructions .= "- JURISPRUDENCE: si question complexe, proposer 3 arrêts (cour, date, numéro, faits, principe, portée)\n";
                break;

            case 'entreprise':
                $instructions .= "- Mettre l'accent sur les implications pratiques et opérationnelles\n";
                $instructions .= "- Souligner les risques et opportunités commerciales\n";
                $instructions .= "- Proposer des solutions pragmatiques\n";
                $instructions .= "- Si l'utilisateur demande explicitement de rédiger une dissertation ou un exposé,\n";
                $instructions .= "  fournir une rédaction complète et structurée (introduction, problématique, plan,\n";
                $instructions .= "  développement, conclusion), avec références si pertinentes\n";
                $instructions .= "- CONTEXTE ENTREPRISE: demander secteur, taille, localisation si manquants\n";
                $instructions .= "- CONFORMITÉ: couvrir obligations légales, bonnes pratiques, risques (amende/prison si dispo), actions correctives\n";
                $instructions .= "- SIMULATEURS: si calcul coût embauche, demander salaire brut, localisation, secteur,\n";
                $instructions .= "  puis fournir coût total + ventilation + comparaison secteur\n";
                $instructions .= "- ALERTES RÉGLEMENTAIRES: proposer points d'attention adaptés au secteur\n";
                $instructions .= "- MODÈLES: si demande contrat (ex: CDI), fournir template avec sections à personnaliser\n";
                $instructions .= "  et notes explicatives, ajouter 'À adapter avec votre avocat'\n";
                break;

            case 'particulier':
                $instructions .= "- Utiliser un langage accessible et clair\n";
                $instructions .= "- Expliquer les termes juridiques complexes\n";
                $instructions .= "- Donner des exemples concrets de la vie quotidienne\n";
                $instructions .= "- Si l'utilisateur demande explicitement de rédiger une dissertation ou un exposé,\n";
                $instructions .= "  fournir une rédaction complète et structurée (introduction, problématique, plan,\n";
                $instructions .= "  développement, conclusion), avec références si pertinentes\n";
                break;
        }

        // Adaptation selon le niveau
        switch ($profile['knowledge_level']) {
            case 'debutant':
                $instructions .= "- Éviter le jargon technique autant que possible\n";
                $instructions .= "- Structurer les réponses de manière simple et progressive\n";
                break;

            case 'expert':
                $instructions .= "- Utiliser la terminologie juridique appropriée\n";
                $instructions .= "- Approfondir les aspects techniques et nuances\n";
                break;
        }

        return $instructions;
    }

    /**
     * Mettre à jour le profil utilisateur après une conversation
     * 
     * @param User $user
     * @param array $conversationHistory
     * @return void
     */
    public function updateUserProfile(User $user, array $conversationHistory): void
    {
        $profile = $this->analyzeUserProfile($user, $conversationHistory);

        // Mise à jour uniquement si non défini manuellement
        if (!$user->mobile_role) {
            $user->mobile_role = $profile['type'];
        }

        $user->knowledge_level = $profile['knowledge_level'];
        $user->domains_of_interest = json_encode($profile['domains']);
        $user->interaction_stats = json_encode($profile['stats']);

        $user->save();

        Log::info('User profile updated', [
            'user_id' => $user->id,
            'type' => $user->mobile_role,
            'level' => $user->knowledge_level,
        ]);
    }
}
