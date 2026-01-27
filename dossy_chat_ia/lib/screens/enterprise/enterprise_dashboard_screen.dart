import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/enterprise_provider.dart';
import '../../providers/auth_provider.dart';
import '../../widgets/common_widgets.dart';
import '../../l10n/app_localizations.dart';

class EnterpriseDashboardScreen extends StatefulWidget {
  const EnterpriseDashboardScreen({super.key});

  @override
  State<EnterpriseDashboardScreen> createState() =>
      _EnterpriseDashboardScreenState();
}

class _EnterpriseDashboardScreenState extends State<EnterpriseDashboardScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _loadData();
    });
  }

  Future<void> _loadData() async {
    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final enterpriseProvider =
        Provider.of<EnterpriseProvider>(context, listen: false);

    if (authProvider.token != null) {
      await Future.wait([
        enterpriseProvider.fetchDashboard(authProvider.token!),
        enterpriseProvider.fetchSubAccounts(authProvider.token!),
      ]);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    return Scaffold(
      appBar: AppBar(
        title: Text(l10n.enterpriseDashboard),
        elevation: 0,
        actions: [
          IconButton(
            onPressed: _loadData,
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: Consumer<EnterpriseProvider>(
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
                    onPressed: _loadData,
                    icon: const Icon(Icons.refresh),
                    label: Text(l10n.retry),
                  ),
                ],
              ),
            );
          }

          final dashboard = provider.dashboard;

          return RefreshIndicator(
            onRefresh: _loadData,
            child: SingleChildScrollView(
              physics: const AlwaysScrollableScrollPhysics(),
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Statistics Cards
                    if (dashboard != null) ...[
                      Row(
                        children: [
                          Expanded(
                            child: StatCard(
                              label: 'Total Comptes',
                              value: '${dashboard.totalSubAccounts}',
                              icon: Icons.people,
                              color: Colors.blue,
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: StatCard(
                              label: 'Actifs',
                              value: '${dashboard.activeSubAccounts}',
                              icon: Icons.check_circle,
                              color: Colors.green,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      Row(
                        children: [
                          Expanded(
                            child: StatCard(
                              label: 'Inactifs',
                              value: '${dashboard.inactiveSubAccounts}',
                              icon: Icons.pause_circle,
                              color: Colors.orange,
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: StatCard(
                              label: 'Places Restantes',
                              value: '${dashboard.remainingSlots}',
                              icon: Icons.add_circle,
                              color: Colors.purple,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 24),
                    ],

                    // Sub Accounts Header
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text(
                          'Sous-Comptes',
                          style: TextStyle(
                            fontSize: 20,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        ElevatedButton.icon(
                          onPressed: () {
                            // Navigate to create sub account screen
                            Navigator.pushNamed(context, '/create-sub-account')
                                .then((_) => _loadData());
                          },
                          icon: const Icon(Icons.add, size: 18),
                          label: const Text('Ajouter'),
                          style: ElevatedButton.styleFrom(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 16,
                              vertical: 8,
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),

                    // Sub Accounts List
                    if (provider.subAccounts.isEmpty)
                      Center(
                        child: Padding(
                          padding: const EdgeInsets.all(32),
                          child: Column(
                            children: [
                              Icon(Icons.people_outline,
                                  size: 64, color: Colors.grey[400]),
                              const SizedBox(height: 16),
                              Text(
                                'Aucun sous-compte',
                                style: TextStyle(
                                  fontSize: 16,
                                  color: Colors.grey[600],
                                ),
                              ),
                              const SizedBox(height: 8),
                              const Text(
                                l10n.createFirstSubAccount,
                                style: TextStyle(fontSize: 14),
                              ),
                            ],
                          ),
                        ),
                      )
                    else
                      ...provider.subAccounts.map((subAccount) {
                        return SubAccountCard(
                          name: subAccount.name,
                          email: subAccount.email,
                          role: subAccount.roleDisplayName,
                          isActive: subAccount.isActive,
                          onTap: () {
                            // Navigate to sub account details
                          },
                          onToggle: () async {
                            final authProvider = Provider.of<AuthProvider>(
                                context,
                                listen: false);
                            if (authProvider.token != null) {
                              final success =
                                  await provider.toggleSubAccountStatus(
                                subAccount.id,
                                authProvider.token!,
                              );
                              if (success) {
                                ScaffoldMessenger.of(context).showSnackBar(
                                  SnackBar(
                                    content: Text(subAccount.isActive
                                        ? l10n.accountDisabled
                                        : l10n.accountActivated),
                                    backgroundColor: Colors.green,
                                  ),
                                );
                              }
                            }
                          },
                          onDelete: () async {
                            final confirmed = await showDialog<bool>(
                              context: context,
                              builder: (context) => AlertDialog(
                                title: Text(l10n.confirmDeletion),
                                content: Text(
                                    'Voulez-vous vraiment supprimer le compte de ${subAccount.name} ?'),
                                actions: [
                                  TextButton(
                                    onPressed: () =>
                                        Navigator.pop(context, false),
                                    child: Text(l10n.cancel),
                                  ),
                                  TextButton(
                                    onPressed: () =>
                                        Navigator.pop(context, true),
                                    style: TextButton.styleFrom(
                                      foregroundColor: Colors.red,
                                    ),
                                    child: Text(l10n.delete),
                                  ),
                                ],
                              ),
                            );

                            if (confirmed == true) {
                              final authProvider = Provider.of<AuthProvider>(
                                  context,
                                  listen: false);
                              if (authProvider.token != null) {
                                final success = await provider.deleteSubAccount(
                                  subAccount.id,
                                  authProvider.token!,
                                );
                                if (success) {
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    const SnackBar(
                                      content: Text(l10n.accountDeleted),
                                      backgroundColor: Colors.green,
                                    ),
                                  );
                                }
                              }
                            }
                          },
                        );
                      }),
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}
