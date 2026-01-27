<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class ExtractDocumentsCommand extends Command
{
    /**
     * Signature de la commande
     */
    protected $signature = 'documents:extract';

    /**
     * Description
     */
    protected $description = 'Extrait le contenu des documents uploadés (sans packages Laravel)';

    /**
     * Exécute la commande
     */
    public function handle()
    {
        $this->info('🚀 Extraction des documents en cours...');

        // Chemin vers le script Python
        $scriptPath = base_path('scripts/extract_documents.py');

        if (!file_exists($scriptPath)) {
            $this->error("❌ Script non trouvé: {$scriptPath}");
            return Command::FAILURE;
        }

        // Lancer le script Python
        $process = new Process(['python3', $scriptPath]);
        $process->setWorkingDirectory(base_path());
        $process->setTimeout(300); // 5 minutes

        try {
            $process->mustRun(function ($type, $buffer) {
                if ($type === Process::ERR) {
                    $this->error($buffer);
                } else {
                    $this->line($buffer);
                }
            });

            $this->info('✅ Extraction terminée avec succès');
            return Command::SUCCESS;
        } catch (\Symfony\Component\Process\Exception\ProcessFailedException $e) {
            $this->error('❌ Erreur lors de l\'extraction');
            $this->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}
