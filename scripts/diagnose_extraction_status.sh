#!/bin/bash

################################################################################
# Script de diagnostic complet de l'extraction des documents
################################################################################

cd /home/threesixty/yyy/Dossy

echo "================================================================================"
echo "DIAGNOSTIC COMPLET DES EXTRACTIONS"
echo "================================================================================"
echo ""

# Statistiques globales
php artisan tinker --execute="
    echo str_repeat('=', 80) . PHP_EOL;
    echo 'STATISTIQUES GLOBALES' . PHP_EOL;
    echo str_repeat('=', 80) . PHP_EOL;
    
    \$total = App\Models\LegalDocument::count();
    \$extracted = App\Models\LegalDocument::where('extracted_text_length', '>', 0)->count();
    \$failed = \$total - \$extracted;
    \$percent = \$total > 0 ? round((\$extracted / \$total) * 100, 2) : 0;
    
    echo 'Total documents: ' . \$total . PHP_EOL;
    echo 'Extraits avec succès: ' . \$extracted . ' (' . \$percent . '%)' . PHP_EOL;
    echo 'Sans extraction: ' . \$failed . PHP_EOL;
    echo str_repeat('-', 80) . PHP_EOL;
    echo PHP_EOL;
    
    // Documents sans extraction
    \$docs_failed = App\Models\LegalDocument::whereNull('extracted_text_length')
        ->orWhere('extracted_text_length', 0)
        ->orderBy('file_size', 'asc')
        ->get(['id', 'file_name', 'file_size']);
    
    if (\$docs_failed->count() > 0) {
        echo str_repeat('=', 80) . PHP_EOL;
        echo 'DOCUMENTS SANS EXTRACTION (' . \$docs_failed->count() . ')' . PHP_EOL;
        echo str_repeat('=', 80) . PHP_EOL;
        echo PHP_EOL;
        
        // Grouper par taille
        \$groups = [
            'tiny' => ['label' => 'TRÈS PETITS (< 1MB)', 'docs' => []],
            'small' => ['label' => 'PETITS (1-5MB)', 'docs' => []],
            'medium' => ['label' => 'MOYENS (5-15MB)', 'docs' => []],
            'large' => ['label' => 'GRANDS (15-30MB)', 'docs' => []],
            'huge' => ['label' => 'TRÈS GRANDS (> 30MB)', 'docs' => []],
        ];
        
        foreach (\$docs_failed as \$doc) {
            \$size_mb = (\$doc->file_size ?? 0) / (1024 * 1024);
            
            if (\$size_mb < 1) {
                \$groups['tiny']['docs'][] = \$doc;
            } elseif (\$size_mb < 5) {
                \$groups['small']['docs'][] = \$doc;
            } elseif (\$size_mb < 15) {
                \$groups['medium']['docs'][] = \$doc;
            } elseif (\$size_mb < 30) {
                \$groups['large']['docs'][] = \$doc;
            } else {
                \$groups['huge']['docs'][] = \$doc;
            }
        }
        
        foreach (\$groups as \$key => \$group) {
            if (count(\$group['docs']) > 0) {
                echo '📁 ' . \$group['label'] . ' (' . count(\$group['docs']) . ' documents)' . PHP_EOL;
                echo str_repeat('-', 80) . PHP_EOL;
                
                foreach (\$group['docs'] as \$doc) {
                    \$size_mb = (\$doc->file_size ?? 0) / (1024 * 1024);
                    \$filename = substr(\$doc->file_name, 0, 55);
                    echo sprintf('  ID %-4d | %6.1f MB | %s', \$doc->id, \$size_mb, \$filename) . PHP_EOL;
                }
                echo PHP_EOL;
            }
        }
    } else {
        echo '✅ TOUS LES DOCUMENTS ONT ÉTÉ EXTRAITS !' . PHP_EOL;
    }
    
    // Documents avec petite extraction (potentiellement incomplet)
    \$docs_partial = App\Models\LegalDocument::where('extracted_text_length', '>', 0)
        ->where('extracted_text_length', '<', 1000)
        ->orderBy('extracted_text_length', 'asc')
        ->get(['id', 'file_name', 'extracted_text_length', 'file_size']);
    
    if (\$docs_partial->count() > 0) {
        echo str_repeat('=', 80) . PHP_EOL;
        echo '⚠️  DOCUMENTS AVEC EXTRACTION PARTIELLE (< 1000 caractères)' . PHP_EOL;
        echo str_repeat('=', 80) . PHP_EOL;
        echo PHP_EOL;
        
        foreach (\$docs_partial as \$doc) {
            \$size_mb = (\$doc->file_size ?? 0) / (1024 * 1024);
            \$filename = substr(\$doc->file_name, 0, 50);
            echo sprintf(
                '  ID %-4d | %6.1f MB | %5d chars | %s',
                \$doc->id,
                \$size_mb,
                \$doc->extracted_text_length,
                \$filename
            ) . PHP_EOL;
        }
        echo PHP_EOL;
    }
    
    // Top 10 des documents avec le plus de texte extrait
    \$docs_top = App\Models\LegalDocument::where('extracted_text_length', '>', 0)
        ->orderBy('extracted_text_length', 'desc')
        ->limit(10)
        ->get(['id', 'file_name', 'extracted_text_length']);
    
    if (\$docs_top->count() > 0) {
        echo str_repeat('=', 80) . PHP_EOL;
        echo '🏆 TOP 10 DES DOCUMENTS LES PLUS RICHES EN CONTENU' . PHP_EOL;
        echo str_repeat('=', 80) . PHP_EOL;
        echo PHP_EOL;
        
        foreach (\$docs_top as \$index => \$doc) {
            \$filename = substr(\$doc->file_name, 0, 50);
            echo sprintf(
                '  %2d. ID %-4d | %8d chars | %s',
                \$index + 1,
                \$doc->id,
                \$doc->extracted_text_length,
                \$filename
            ) . PHP_EOL;
        }
        echo PHP_EOL;
    }
"

echo "================================================================================"
echo ""
