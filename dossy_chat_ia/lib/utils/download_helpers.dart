import 'package:flutter/material.dart';
import '../services/download_service.dart';

/// Helpers pour le téléchargement de fichiers
class DownloadHelpers {
  /// Télécharge un template
  static Future<void> downloadTemplate({
    required BuildContext context,
    required int templateId,
    required String templateTitle,
    required String fileType,
    required String? token,
    required Future<String?> Function(int id, String token) fetchDownloadUrl,
  }) async {
    if (token == null) {
      _showError(context, 'Non authentifié');
      return;
    }

    final progressNotifier = ValueNotifier<double>(0.0);

    try {
      // Obtenir l'URL de téléchargement
      final downloadUrl = await fetchDownloadUrl(templateId, token);
      
      if (downloadUrl == null) {
        throw Exception('URL de téléchargement non disponible');
      }

      if (!context.mounted) return;

      // Afficher dialog de progression
      DownloadService.showDownloadDialog(
        context,
        templateTitle,
        progressNotifier,
      );

      // Télécharger le fichier
      final downloadService = DownloadService();
      final filePath = await downloadService.downloadFile(
        url: downloadUrl,
        fileName: '$templateTitle.$fileType',
        token: token,
        onProgress: (received, total) {
          if (total != -1) {
            progressNotifier.value = received / total;
          }
        },
      );

      // Fermer dialog
      if (context.mounted) {
        Navigator.of(context).pop();
        
        // Afficher succès
        DownloadService.showDownloadSnackbar(
          context,
          templateTitle,
          filePath,
        );
      }
    } catch (e) {
      // Fermer dialog si ouvert
      if (context.mounted && Navigator.of(context).canPop()) {
        Navigator.of(context).pop();
      }
      
      if (context.mounted) {
        _showError(context, 'Erreur: ${e.toString()}');
      }
    }
  }

  /// Télécharge une ressource fiscale
  static Future<void> downloadFiscalResource({
    required BuildContext context,
    required int resourceId,
    required String resourceTitle,
    required String fileType,
    required String? token,
    required Future<String?> Function(int id, String token) fetchDownloadUrl,
  }) async {
    // Utiliser la même logique que downloadTemplate
    await downloadTemplate(
      context: context,
      templateId: resourceId,
      templateTitle: resourceTitle,
      fileType: fileType,
      token: token,
      fetchDownloadUrl: fetchDownloadUrl,
    );
  }

  /// Télécharge un document juridique
  static Future<void> downloadLegalDocument({
    required BuildContext context,
    required int documentId,
    required String documentTitle,
    required String fileName,
    required String? token,
    required Future<String?> Function(int id, String token) fetchDownloadUrl,
  }) async {
    if (token == null) {
      _showError(context, 'Non authentifié');
      return;
    }

    final progressNotifier = ValueNotifier<double>(0.0);

    try {
      // Obtenir l'URL de téléchargement
      final downloadUrl = await fetchDownloadUrl(documentId, token);
      
      if (downloadUrl == null) {
        // Si null, afficher un message d'erreur plus spécifique
        if (context.mounted) {
          _showError(context, 'Impossible de récupérer le fichier. Vérifiez votre abonnement ou votre quota de téléchargement.');
        }
        return;
      }

      if (!context.mounted) return;

      // Afficher dialog de progression
      DownloadService.showDownloadDialog(
        context,
        documentTitle,
        progressNotifier,
      );

      // Télécharger le fichier
      final downloadService = DownloadService();
      final filePath = await downloadService.downloadFile(
        url: downloadUrl,
        fileName: fileName,
        token: token,
        onProgress: (received, total) {
          if (total != -1) {
            progressNotifier.value = received / total;
          }
        },
      );

      // Fermer dialog
      if (context.mounted) {
        Navigator.of(context).pop();
        
        // Afficher succès
        DownloadService.showDownloadSnackbar(
          context,
          documentTitle,
          filePath,
        );
      }
    } catch (e) {
      // Fermer dialog si ouvert
      if (context.mounted && Navigator.of(context).canPop()) {
        Navigator.of(context).pop();
      }
      
      if (context.mounted) {
        _showError(context, 'Erreur: ${e.toString()}');
      }
    }
  }

  static void _showError(BuildContext context, String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: Colors.red,
      ),
    );
  }
}
