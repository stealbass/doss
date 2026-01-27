import 'package:flutter/material.dart';
import '../../core/constants/app_constants.dart';
import '../../l10n/app_localizations.dart';

/// Empty State Widgets pour différents scénarios
/// Utilisés dans toute l'application pour afficher des états vides de manière cohérente

/// Empty State générique
class EmptyState extends StatelessWidget {
  final IconData icon;
  final String title;
  final String message;
  final String? actionLabel;
  final VoidCallback? onAction;
  final Color? iconColor;

  const EmptyState({
    super.key,
    required this.icon,
    required this.title,
    required this.message,
    this.actionLabel,
    this.onAction,
    this.iconColor,
  });

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;

    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              icon,
              size: 80,
              color: iconColor ?? Colors.grey.shade400,
            ),
            const SizedBox(height: 24),
            Text(
              title,
              textAlign: TextAlign.center,
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: colorScheme.onSurface,
              ),
            ),
            const SizedBox(height: 12),
            Text(
              message,
              textAlign: TextAlign.center,
              style: TextStyle(
                fontSize: 14,
                color: colorScheme.onSurface.withAlpha((0.7 * 255).round()),
                height: 1.5,
              ),
            ),
            if (actionLabel != null && onAction != null) ...[
              const SizedBox(height: 32),
              ElevatedButton(
                onPressed: onAction,
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppConstants.primaryGreen,
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(
                    horizontal: 32,
                    vertical: 14,
                  ),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(8),
                  ),
                ),
                child: Text(
                  actionLabel!,
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

/// Empty State - Aucune donnée
class EmptyDataState extends StatelessWidget {
  final String? title;
  final String? message;
  final String? actionLabel;
  final VoidCallback? onAction;

  const EmptyDataState({
    super.key,
    this.title,
    this.message,
    this.actionLabel,
    this.onAction,
  });

  @override
  Widget build(BuildContext context) {
    return EmptyState(
      icon: Icons.inbox_outlined,
      title: title ?? 'Aucune donnée',
      message: message ?? 'Aucune information disponible pour le moment.',
      actionLabel: actionLabel,
      onAction: onAction,
      iconColor: Colors.grey.shade400,
    );
  }
}

/// Empty State - Pas de connexion internet
class NoConnectionState extends StatelessWidget {
  final VoidCallback? onRetry;

  const NoConnectionState({
    super.key,
    this.onRetry,
  });

  @override
  Widget build(BuildContext context) {
    return EmptyState(
      icon: Icons.wifi_off,
      title: 'Pas de connexion',
      message: 'Vérifiez votre connexion internet et réessayez',
      actionLabel: 'Réessayer',
      onAction: onRetry,
      iconColor: Colors.orange.shade400,
    );
  }
}

/// Empty State - Erreur générale
class ErrorState extends StatelessWidget {
  final String? title;
  final String? message;
  final VoidCallback? onRetry;

  const ErrorState({
    super.key,
    this.title,
    this.message,
    this.onRetry,
  });

  @override
  Widget build(BuildContext context) {
    return EmptyState(
      icon: Icons.error_outline,
      title: title ?? 'Erreur',
      message: message ?? 'Une erreur est survenue. Veuillez réessayer.',
      actionLabel: 'Réessayer',
      onAction: onRetry,
      iconColor: Colors.red.shade400,
    );
  }
}

/// Empty State - Aucun résultat de recherche
class NoSearchResultsState extends StatelessWidget {
  final String? searchQuery;
  final VoidCallback? onClear;

  const NoSearchResultsState({
    super.key,
    this.searchQuery,
    this.onClear,
  });

  @override
  Widget build(BuildContext context) {
    return EmptyState(
      icon: Icons.search_off,
      title: 'Aucun résultat',
      message: searchQuery != null
          ? 'Aucun résultat trouvé pour "$searchQuery"'
          : 'Aucun résultat ne correspond à votre recherche. essayez avec des termes différents',
      actionLabel: onClear != null ? 'Effacer la recherche' : null,
      onAction: onClear,
      iconColor: Colors.blue.shade400,
    );
  }
}

/// Empty State - Liste de messages vide
class EmptyMessagesState extends StatelessWidget {
  final VoidCallback? onStartChat;

  const EmptyMessagesState({
    super.key,
    this.onStartChat,
  });

  @override
  Widget build(BuildContext context) {
    return EmptyState(
      icon: Icons.chat_bubble_outline,
      title: 'Aucun message',
      message: 'Commencez une conversation avec l\'assistant juridique',
      actionLabel: 'Démarrer le chat',
      onAction: onStartChat,
      iconColor: AppConstants.primaryGreen,
    );
  }
}

/// Empty State - Liste de documents vide
class EmptyDocumentsState extends StatelessWidget {
  final VoidCallback? onUpload;

  const EmptyDocumentsState({
    super.key,
    this.onUpload,
  });

  @override
  Widget build(BuildContext context) {
    return EmptyState(
      icon: Icons.folder_open,
      title: 'Bibliothèque vide',
      message: 'Vous n\'avez pas encore uploadé de documents',
      actionLabel: 'Ajouter un document',
      onAction: onUpload,
      iconColor: Colors.blue.shade400,
    );
  }
}

/// Empty State - Historique vide
class EmptyHistoryState extends StatelessWidget {
  final String? type;

  const EmptyHistoryState({
    super.key,
    this.type,
  });

  @override
  Widget build(BuildContext context) {
    return EmptyState(
      icon: Icons.history,
      title: 'Historique vide',
      message: type != null
          ? 'Aucun historique de $type'
          : 'Votre historique est vide pour le moment',
      iconColor: Colors.grey.shade400,
    );
  }
}

/// Empty State - Notifications vides
class EmptyNotificationsState extends StatelessWidget {
  const EmptyNotificationsState({super.key});

  @override
  Widget build(BuildContext context) {
    return EmptyState(
      icon: Icons.notifications_none,
      title: 'Aucune notification',
      message: 'Vous n\'avez rien pour le moment',
      iconColor: Colors.grey.shade400,
    );
  }
}

/// Empty State - Favoris vide
class EmptyFavoritesState extends StatelessWidget {
  const EmptyFavoritesState({super.key});

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    return EmptyState(
      icon: Icons.favorite_border,
      title: l10n.noFavorites,
      message: l10n.noArticlesAdded,
      iconColor: Colors.pink.shade400,
    );
  }
}

/// Empty State - Quota dépassé
class QuotaExceededState extends StatelessWidget {
  final VoidCallback? onUpgrade;

  const QuotaExceededState({
    super.key,
    this.onUpgrade,
  });

  @override
  Widget build(BuildContext context) {
    return EmptyState(
      icon: Icons.block,
      title: 'Quota dépassé',
      message: 'Vous avez atteint la limite de votre plan.\nPassez à un plan supérieur pour continuer.',
      actionLabel: 'Mettre à niveau',
      onAction: onUpgrade,
      iconColor: Colors.orange.shade600,
    );
  }
}

/// Empty State - Accès refusé (Premium)
class PremiumRequiredState extends StatelessWidget {
  final String? featureName;
  final VoidCallback? onUpgrade;

  const PremiumRequiredState({
    super.key,
    this.featureName,
    this.onUpgrade,
  });

  @override
  Widget build(BuildContext context) {
    return EmptyState(
      icon: Icons.lock_outline,
      title: 'Fonctionnalité PRO',
      message: featureName != null
          ? '$featureName est réservé aux abonnés premium'
          : 'Cette fonctionnalité est réservée aux abonnés premium',
      actionLabel: 'Mettre à niveau',
      onAction: onUpgrade,
      iconColor: Colors.amber.shade600,
    );
  }
}

/// Empty State - Chargement initial
class LoadingState extends StatelessWidget {
  final String? message;

  const LoadingState({
    super.key,
    this.message,
  });

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const CircularProgressIndicator(
            color: AppConstants.primaryGreen,
          ),
          const SizedBox(height: 24),
          Text(
            message ?? l10n.loading,
            style: TextStyle(
              fontSize: 14,
              color: Colors.grey.shade600,
            ),
          ),
        ],
      ),
    );
  }
}

/// Empty State - Maintenance
class MaintenanceState extends StatelessWidget {
  const MaintenanceState({super.key});

  @override
  Widget build(BuildContext context) {
    return EmptyState(
      icon: Icons.construction,
      title: 'Maintenance en cours',
      message: 'Cette fonctionnalité est temporairement indisponible.\nNous serons bientôt de retour !',
      iconColor: Colors.orange.shade600,
    );
  }
}

/// Empty State - Version obsolète
class UpdateRequiredState extends StatelessWidget {
  final VoidCallback? onUpdate;

  const UpdateRequiredState({
    super.key,
    this.onUpdate,
  });

  @override
  Widget build(BuildContext context) {
    return EmptyState(
      icon: Icons.system_update,
      title: 'Mise à jour requise',
      message: 'Une nouvelle version de l\'application est disponible.\nVeuillez mettre à jour pour continuer.',
      actionLabel: 'Mettre à jour',
      onAction: onUpdate,
      iconColor: Colors.blue.shade600,
    );
  }
}
