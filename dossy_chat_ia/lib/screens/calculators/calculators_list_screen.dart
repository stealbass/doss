import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/calculator_provider.dart';
import '../../providers/auth_provider.dart';
import '../../widgets/common_widgets.dart';
import '../../l10n/app_localizations.dart';

class CalculatorsListScreen extends StatefulWidget {
  const CalculatorsListScreen({super.key});

  @override
  State<CalculatorsListScreen> createState() => _CalculatorsListScreenState();
}

class _CalculatorsListScreenState extends State<CalculatorsListScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _loadCalculators();
    });
  }

  Future<void> _loadCalculators() async {
    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final calculatorProvider =
        Provider.of<CalculatorProvider>(context, listen: false);

    if (authProvider.token != null) {
      await calculatorProvider.fetchCalculators(authProvider.token!);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    return Scaffold(
      appBar: AppBar(
        title: Text(l10n.calculatorsSimulators),
        elevation: 0,
      ),
      body: Consumer<CalculatorProvider>(
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
                    onPressed: _loadCalculators,
                    icon: const Icon(Icons.refresh),
                    label: Text(l10n.retry),
                  ),
                ],
              ),
            );
          }

          if (provider.calculators.isEmpty) {
            return Center(
              child: Text(l10n.noCalculatorAvailable),
            );
          }

          return GridView.builder(
            padding: const EdgeInsets.all(16),
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 2,
              childAspectRatio: 0.85,
              crossAxisSpacing: 12,
              mainAxisSpacing: 12,
            ),
            itemCount: provider.calculators.length,
            itemBuilder: (context, index) {
              final calculator = provider.calculators[index];
              return CalculatorCard(
                name: calculator.name,
                description: calculator.description,
                calculatorType: calculator.calculatorType,
                onTap: () {
                  // Navigate to calculator screen
                  Navigator.pushNamed(
                    context,
                    '/calculator-form',
                    arguments: calculator,
                  );
                },
              );
            },
          );
        },
      ),
    );
  }
}
