import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/legal_alert_provider.dart';
import '../../providers/auth_provider.dart';
import '../../widgets/common_widgets.dart';

class AlertsListScreen extends StatefulWidget {
  const AlertsListScreen({Key? key}) : super(key: key);

  @override
  State<AlertsListScreen> createState() => _AlertsListScreenState();
}

class _AlertsListScreenState extends State<AlertsListScreen> {
  String _selectedFilter = 'all';

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _loadAlerts();
    });
  }

  Future<void> _loadAlerts() async {
    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final alertProvider = Provider.of<LegalAlertProvider>(context, listen: false);

    if (authProvider.token != null) {
      await alertProvider.fetchAlerts(authProvider.token!);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Alertes Juridiques'),
        elevation: 0,
        actions: [
          Consumer<LegalAlertProvider>(
            builder: (context, provider, child) {
              if (provider.unreadCount > 0) {
                return Stack(
                  children: [
                    IconButton(
                      onPressed: () {},
                      icon: const Icon(Icons.notifications),
                    ),
                    Positioned(
                      right: 8,
                      top: 8,
                      child: Container(
                        padding: const EdgeInsets.all(4),
                        decoration: const BoxDecoration(
                          color: Colors.red,
                          shape: BoxShape.circle,
                        ),
                        constraints: const BoxConstraints(
                          minWidth: 16,
                          minHeight: 16,
                        ),
                        child: Text(
                          '${provider.unreadCount}',
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 10,
                            fontWeight: FontWeight.bold,
                          ),
                          textAlign: TextAlign.center,
                        ),
                      ),
                    ),
                  ],
                );
              }
              return const SizedBox.shrink();
            },
          ),
          IconButton(
            onPressed: _loadAlerts,
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: Consumer<LegalAlertProvider>(
        builder: (context, provider, child) {
          if (provider.isLoading) {
            return const Center(child: CircularProgressIndicator());
          }

          if (provider.error != null) {
            return Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.error_outline, size: 64, color: Colors.red[300]),
                  const SizedBox(height: 16),
                  Text(provider.error!, textAlign: TextAlign.center),
                  const SizedBox(height: 16),
                  ElevatedButton.icon(
                    onPressed: _loadAlerts,
                    icon: const Icon(Icons.refresh),
                    label: const Text('Réessayer'),
                  ),
                ],
              ),
            );
          }

          // Filter alerts based on selection
          List<dynamic> alerts;
          switch (_selectedFilter) {
            case 'unread':
              alerts = provider.unreadAlerts;
              break;
            case 'urgent':
              alerts = provider.urgentAlerts;
              break;
            default:
              alerts = provider.alerts;
          }

          return Column(
            children: [
              // Filter chips
              Padding(
                padding: const EdgeInsets.all(16),
                child: Row(
                  children: [
                    FilterChip(
                      label: Text('Toutes (${provider.alerts.length})'),
                      selected: _selectedFilter == 'all',
                      onSelected: (selected) {
                        setState(() => _selectedFilter = 'all');
                      },
                    ),
                    const SizedBox(width: 8),
                    FilterChip(
                      label: Text('Non lues (${provider.unreadCount})'),
                      selected: _selectedFilter == 'unread',
                      onSelected: (selected) {
                        setState(() => _selectedFilter = 'unread');
                      },
                    ),
                    const SizedBox(width: 8),
                    FilterChip(
                      label: Text('Urgentes (${provider.urgentAlerts.length})'),
                      selected: _selectedFilter == 'urgent',
                      onSelected: (selected) {
                        setState(() => _selectedFilter = 'urgent');
                      },
                    ),
                  ],
                ),
              ),

              // Alerts list
              Expanded(
                child: alerts.isEmpty
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.notifications_none,
                                size: 64, color: Colors.grey[400]),
                            const SizedBox(height: 16),
                            Text(
                              'Aucune alerte',
                              style: TextStyle(
                                fontSize: 16,
                                color: Colors.grey[600],
                              ),
                            ),
                          ],
                        ),
                      )
                    : RefreshIndicator(
                        onRefresh: _loadAlerts,
                        child: ListView.builder(
                          padding: const EdgeInsets.all(16),
                          itemCount: alerts.length,
                          itemBuilder: (context, index) {
                            final alert = alerts[index];
                            return AlertCard(
                              title: alert.title,
                              content: alert.content,
                              priority: alert.priority,
                              isRead: alert.isRead,
                              createdAt: alert.createdAt,
                              onTap: () async {
                                // Navigate to details and mark as read
                                if (!alert.isRead) {
                                  final authProvider = Provider.of<AuthProvider>(
                                      context,
                                      listen: false);
                                  if (authProvider.token != null) {
                                    await provider.markAsRead(
                                      alert.id,
                                      authProvider.token!,
                                    );
                                  }
                                }
                                // Navigate to details screen
                                Navigator.pushNamed(
                                  context,
                                  '/alert-details',
                                  arguments: alert,
                                );
                              },
                            );
                          },
                        ),
                      ),
              ),
            ],
          );
        },
      ),
    );
  }
}
