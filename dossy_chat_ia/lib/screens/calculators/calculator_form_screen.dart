import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import '../../providers/calculator_provider.dart' hide Calculator;
import '../../models/calculator_model.dart';
import '../../providers/auth_provider.dart';
import '../../l10n/app_localizations.dart';
import 'calculator_result_screen.dart';

class CalculatorFormScreen extends StatefulWidget {
  final Calculator calculator;

  const CalculatorFormScreen({
    super.key,
    required this.calculator,
  });

  @override
  State<CalculatorFormScreen> createState() => _CalculatorFormScreenState();
}

class _CalculatorFormScreenState extends State<CalculatorFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final Map<String, TextEditingController> _controllers = {};
  bool _isCalculating = false;

  @override
  void initState() {
    super.initState();
    _initializeControllers();
  }

  void _initializeControllers() {
    for (var input in widget.calculator.inputs) {
      _controllers[input['name']] = TextEditingController();
    }
  }

  @override
  void dispose() {
    _controllers.forEach((_, controller) => controller.dispose());
    super.dispose();
  }

  Future<void> _calculate() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isCalculating = true);

    final l10n = AppLocalizations.of(context)!;
    try {
      // Prepare input data
      final Map<String, dynamic> inputData = {};
      _controllers.forEach((key, controller) {
        final input = widget.calculator.inputs.firstWhere((i) => i['name'] == key);
        final type = input['type'] as String;
        
        if (type == 'number') {
          inputData[key] = double.tryParse(controller.text) ?? 0;
        } else {
          inputData[key] = controller.text;
        }
      });

      final authToken = context.read<AuthProvider>().token;
      if (authToken == null) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(l10n.userNotAuthenticated), backgroundColor: Colors.red),
          );
        }
        return;
      }

      final provider = context.read<CalculatorProvider>();
      final result = await provider.calculate(
        widget.calculator.id,
        inputData,
        authToken,
      );

      if (result == null) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(l10n.calculationError), backgroundColor: Colors.red),
          );
        }
        return;
      }

      if (mounted) {
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (context) => CalculatorResultScreen(
              calculator: widget.calculator,
              result: result,
              inputs: inputData,
            ),
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('${l10n.calculationError}: ${e.toString()}'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _isCalculating = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.calculator.name),
      ),
      body: Form(
        key: _formKey,
        child: Column(
          children: [
            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Calculator Info Card
                    Card(
                      color: Theme.of(context).primaryColor.withAlpha((0.1 * 255).round()),
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                Icon(
                                  _getCalculatorIcon(widget.calculator.type),
                                  color: Theme.of(context).primaryColor,
                                ),
                                const SizedBox(width: 12),
                                Expanded(
                                  child: Text(
                                    widget.calculator.name,
                                    style: Theme.of(context).textTheme.titleLarge?.copyWith(
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 8),
                            Text(
                              widget.calculator.description,
                              style: Theme.of(context).textTheme.bodyMedium,
                            ),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(height: 24),

                    // Input Fields
                    Text(
                      'Saisir les informations',
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 16),

                    ...widget.calculator.inputs.map((input) {
                      return _buildInputField(input);
                    }),

                    const SizedBox(height: 24),

                    // Instructions
                    if (widget.calculator.instructions.isNotEmpty) ...[
                      Text(
                        'Instructions',
                        style: Theme.of(context).textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Card(
                        color: Colors.blue.shade50,
                        child: Padding(
                          padding: const EdgeInsets.all(12),
                          child: Row(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Icon(
                                Icons.info_outline,
                                color: Colors.blue.shade700,
                                size: 20,
                              ),
                              const SizedBox(width: 8),
                              Expanded(
                                child: Text(
                                  widget.calculator.instructions,
                                  style: TextStyle(
                                    color: Colors.blue.shade900,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ],
                  ],
                ),
              ),
            ),

            // Calculate Button
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white,
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withAlpha((0.1 * 255).round()),
                    blurRadius: 4,
                    offset: const Offset(0, -2),
                  ),
                ],
              ),
              child: SafeArea(
                child: ElevatedButton.icon(
                  onPressed: _isCalculating ? null : _calculate,
                  icon: _isCalculating
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                          ),
                        )
                      : const Icon(Icons.calculate),
                  label: Text(_isCalculating ? 'Calcul en cours...' : 'Calculer'),
                  style: ElevatedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    minimumSize: const Size.fromHeight(50),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildInputField(Map<String, dynamic> input) {
    final name = input['name'] as String;
    final label = input['label'] as String;
    final type = input['type'] as String;
    final required = input['required'] as bool? ?? true;
    final hint = input['hint'] as String? ?? '';

    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Text(
                label,
                style: const TextStyle(
                  fontWeight: FontWeight.w500,
                  fontSize: 14,
                ),
              ),
              if (required)
                const Text(
                  ' *',
                  style: TextStyle(color: Colors.red),
                ),
            ],
          ),
          const SizedBox(height: 8),
          TextFormField(
            controller: _controllers[name],
            keyboardType: type == 'number'
                ? const TextInputType.numberWithOptions(decimal: true)
                : TextInputType.text,
            inputFormatters: type == 'number'
                ? [FilteringTextInputFormatter.allow(RegExp(r'^\d+\.?\d{0,2}'))]
                : null,
            decoration: InputDecoration(
              hintText: hint.isNotEmpty ? hint : 'Entrer $label',
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(8),
              ),
              filled: true,
              fillColor: Colors.grey.shade50,
              suffixIcon: type == 'number'
                  ? const Icon(Icons.numbers, size: 20)
                  : null,
            ),
            validator: (value) {
              if (required && (value == null || value.isEmpty)) {
                return 'Ce champ est requis';
              }
              if (type == 'number' && value != null && value.isNotEmpty) {
                if (double.tryParse(value) == null) {
                  return 'Veuillez entrer un nombre valide';
                }
              }
              return null;
            },
          ),
          if (hint.isNotEmpty)
            Padding(
              padding: const EdgeInsets.only(top: 4),
              child: Text(
                hint,
                style: TextStyle(
                  fontSize: 12,
                  color: Colors.grey[600],
                ),
              ),
            ),
        ],
      ),
    );
  }

  IconData _getCalculatorIcon(String type) {
    switch (type.toLowerCase()) {
      case 'salaire':
        return Icons.attach_money;
      case 'impot':
        return Icons.receipt_long;
      case 'conges':
        return Icons.beach_access;
      case 'indemnite':
        return Icons.payment;
      case 'charges':
        return Icons.account_balance;
      default:
        return Icons.calculate;
    }
  }
}
