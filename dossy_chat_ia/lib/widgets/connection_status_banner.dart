import 'package:flutter/material.dart';
import 'dart:async';
import '../core/services/offline_service.dart';

/// Widget affichant le statut de connexion Internet
/// 
/// Affiche une bannière en haut de l'écran quand l'utilisateur est offline
/// avec options de synchronisation manuelle
class ConnectionStatusBanner extends StatefulWidget {
  final Widget child;

  const ConnectionStatusBanner({
    Key? key,
    required this.child,
  }) : super(key: key);

  @override
  State<ConnectionStatusBanner> createState() => _ConnectionStatusBannerState();
}

class _ConnectionStatusBannerState extends State<ConnectionStatusBanner> {
  final OfflineService _offlineService = OfflineService();
  StreamSubscription<bool>? _connectionSubscription;
  bool _isOnline = true;
  int _pendingActionsCount = 0;
  bool _isSyncing = false;

  @override
  void initState() {
    super.initState();
    _initializeConnectionListener();
  }

  Future<void> _initializeConnectionListener() async {
    // Récupérer le statut initial
    _isOnline = _offlineService.isOnline;
    _pendingActionsCount = await _offlineService.getPendingActionsCount();
    
    if (mounted) {
      setState(() {});
    }

    // Écouter les changements de connexion
    _connectionSubscription = _offlineService.connectionStatus.listen((isOnline) async {
      if (mounted) {
        setState(() {
          _isOnline = isOnline;
        });
      }

      // Mettre à jour le compteur d'actions en attente
      _pendingActionsCount = await _offlineService.getPendingActionsCount();
      if (mounted) {
        setState(() {});
      }
    });
  }

  Future<void> _handleSyncButtonPressed() async {
    if (_isSyncing) return;

    setState(() {
      _isSyncing = true;
    });

    try {
      await _offlineService.forceSyncNow();
      _pendingActionsCount = await _offlineService.getPendingActionsCount();
      
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Row(
              children: [
                Icon(Icons.check_circle, color: Colors.white),
                SizedBox(width: 12),
                Text('Synchronisation terminée'),
              ],
            ),
            backgroundColor: Colors.green,
            duration: Duration(seconds: 2),
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Row(
              children: [
                const Icon(Icons.error, color: Colors.white),
                const SizedBox(width: 12),
                Text('Erreur de synchronisation: $e'),
              ],
            ),
            backgroundColor: Colors.red,
            duration: const Duration(seconds: 3),
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() {
          _isSyncing = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        // Bannière de statut
        AnimatedContainer(
          duration: const Duration(milliseconds: 300),
          height: !_isOnline ? 56 : 0,
          color: Colors.orange.shade700,
          child: !_isOnline
              ? Material(
                  color: Colors.transparent,
                  child: SafeArea(
                    bottom: false,
                    child: Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                      child: Row(
                        children: [
                          const Icon(
                            Icons.cloud_off,
                            color: Colors.white,
                            size: 20,
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                const Text(
                                  'Mode Hors Ligne',
                                  style: TextStyle(
                                    color: Colors.white,
                                    fontWeight: FontWeight.bold,
                                    fontSize: 14,
                                  ),
                                ),
                                if (_pendingActionsCount > 0)
                                  Text(
                                    '$_pendingActionsCount action(s) en attente',
                                    style: const TextStyle(
                                      color: Colors.white70,
                                      fontSize: 12,
                                    ),
                                  ),
                              ],
                            ),
                          ),
                          if (_pendingActionsCount > 0 && _isOnline)
                            TextButton.icon(
                              onPressed: _isSyncing ? null : _handleSyncButtonPressed,
                              icon: _isSyncing
                                  ? const SizedBox(
                                      width: 16,
                                      height: 16,
                                      child: CircularProgressIndicator(
                                        strokeWidth: 2,
                                        valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                                      ),
                                    )
                                  : const Icon(Icons.sync, color: Colors.white, size: 18),
                              label: const Text(
                                'Synchroniser',
                                style: TextStyle(color: Colors.white),
                              ),
                              style: TextButton.styleFrom(
                                backgroundColor: Colors.white24,
                                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                              ),
                            ),
                        ],
                      ),
                    ),
                  ),
                )
              : const SizedBox.shrink(),
        ),
        // Contenu de l'application
        Expanded(child: widget.child),
      ],
    );
  }

  @override
  void dispose() {
    _connectionSubscription?.cancel();
    super.dispose();
  }
}

/// Indicateur de statut de connexion (petit widget)
class ConnectionStatusIndicator extends StatefulWidget {
  final bool showLabel;

  const ConnectionStatusIndicator({
    Key? key,
    this.showLabel = true,
  }) : super(key: key);

  @override
  State<ConnectionStatusIndicator> createState() => _ConnectionStatusIndicatorState();
}

class _ConnectionStatusIndicatorState extends State<ConnectionStatusIndicator> {
  final OfflineService _offlineService = OfflineService();
  StreamSubscription<bool>? _connectionSubscription;
  bool _isOnline = true;

  @override
  void initState() {
    super.initState();
    _isOnline = _offlineService.isOnline;
    
    _connectionSubscription = _offlineService.connectionStatus.listen((isOnline) {
      if (mounted) {
        setState(() {
          _isOnline = isOnline;
        });
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(
        color: _isOnline ? Colors.green.shade50 : Colors.orange.shade50,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(
          color: _isOnline ? Colors.green : Colors.orange,
          width: 1,
        ),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(
            _isOnline ? Icons.cloud_done : Icons.cloud_off,
            size: 16,
            color: _isOnline ? Colors.green : Colors.orange,
          ),
          if (widget.showLabel) ...[
            const SizedBox(width: 6),
            Text(
              _isOnline ? 'En ligne' : 'Hors ligne',
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w600,
                color: _isOnline ? Colors.green.shade800 : Colors.orange.shade800,
              ),
            ),
          ],
        ],
      ),
    );
  }

  @override
  void dispose() {
    _connectionSubscription?.cancel();
    super.dispose();
  }
}
