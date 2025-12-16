import 'package:flutter/material.dart';
import '../../core/constants/app_constants.dart';

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
    Key? key,
    required this.icon,
    required this.title,
    required this.message,
    this.actionLabel,
    this.onAction,
    this.iconColor,
  }) : super(key: key);

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
                color: colorScheme.onSurface.withOpacity(0.7),
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
                  style: TextStyle(
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
    Key? key,
    this.title,
    this.message,
    this.actionLabel,
    this.onAction,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return EmptyState(
      icon: Icons.inbox_outlined,
      title: title ?? 'Aucune donnée',
      message: message ?? 'Il n\'y a rien à afficher pour le moment',
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
    Key? key,
    this.onRetry,
  }) : super(key: key);

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
    Key? key,
    this.title,
    this.message,
    this.onRetry,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return EmptyState(
      icon: Icons.error_outline,
      title: title ?? 'Oups ! Une erreur s\'est produite',
      message: message ?? 'Quelque chose s\'est mal passé. Veuillez réessayer.',
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
    Key? key,
    this.searchQuery,
    this.onClear,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return EmptyState(
      icon: Icons.search_off,
      title: 'Aucun résultat',
      message: searchQuery != null
          ? 'Aucun résultat trouvé pour "$searchQuery"'
          : 'Aucun résultat ne correspond à votre recherche',
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
    Key? key,
    this.onStartChat,
  }) : super(key: key);

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
    Key? key,
    this.onUpload,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return EmptyState(
      icon: Icons.folder_open,
      title: 'Aucun document',
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
    Key? key,
    this.type,
  }) : super(key: key);

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
  const EmptyNotificationsState({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return EmptyState(
      icon: Icons.notifications_none,
      title: 'Aucune notification',
      message: 'Vous n\'avez aucune notification pour le moment',
      iconColor: Colors.grey.shade400,
    );
  }
}

/// Empty State - Favoris vide
class EmptyFavoritesState extends StatelessWidget {
  const EmptyFavoritesState({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return EmptyState(
      icon: Icons.favorite_border,
      title: 'Aucun favori',
      message: 'Vous n\'avez pas encore ajouté de favoris',
      iconColor: Colors.pink.shade400,
    );
  }
}

/// Empty State - Quota dépassé
class QuotaExceededState extends StatelessWidget {
  final VoidCallback? onUpgrade;

  const QuotaExceededState({
    Key? key,
    this.onUpgrade,
  }) : super(key: key);

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
    Key? key,
    this.featureName,
    this.onUpgrade,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return EmptyState(
      icon: Icons.lock_outline,
      title: 'Fonctionnalité Premium',
      message: featureName != null
          ? '$featureName est réservé aux abonnés premium'
          : 'Cette fonctionnalité est réservée aux abonnés premium',
      actionLabel: 'Voir les plans',
      onAction: onUpgrade,
      iconColor: Colors.amber.shade600,
    );
  }
}

/// Empty State - Chargement initial
class LoadingState extends StatelessWidget {
  final String? message;

  const LoadingState({
    Key? key,
    this.message,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          CircularProgressIndicator(
            color: AppConstants.primaryGreen,
          ),
          if (message != null) ...[
            const SizedBox(height: 24),
            Text(
              message!,
              style: TextStyle(
                fontSize: 14,
                color: Colors.grey.shade600,
              ),
            ),
          ],
        ],
      ),
    );
  }
}

/// Empty State - Maintenance
class MaintenanceState extends StatelessWidget {
  const MaintenanceState({Key? key}) : super(key: key);

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
    Key? key,
    this.onUpdate,
  }) : super(key: key);

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
