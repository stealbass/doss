import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:connectivity_plus/connectivity_plus.dart';
import 'dart:io';
import '../../../core/theme/app_colors.dart';
import '../../../core/constants/app_constants.dart';
import '../../../data/services/api_service.dart';
import '../../../core/utils/api_helpers.dart';

/// Écran de diagnostic pour vérifier la connectivité et l'API
/// Accessible uniquement en mode debug
class DiagnosticScreen extends StatefulWidget {
  const DiagnosticScreen({super.key});

  @override
  State<DiagnosticScreen> createState() => _DiagnosticScreenState();
}

class _DiagnosticScreenState extends State<DiagnosticScreen> {
  final ApiService _apiService = ApiService();
  bool _isRunningTests = false;
  final List<Map<String, dynamic>> _testResults = [];

  @override
  void initState() {
    super.initState();
    _runDiagnostics();
  }

  Future<void> _runDiagnostics() async {
    setState(() {
      _isRunningTests = true;
      _testResults.clear();
    });

    // Test 1: Check device connectivity
    await _testConnectivity();

    // Test 2: Check internet access
    await _testInternetAccess();

    // Test 3: Check Google DNS
    await _testDnsResolution();

    // Test 4: Ping API server
    await _testApiServerReachability();

    // Test 5: Test API endpoint
    await _testApiEndpoint();

    setState(() {
      _isRunningTests = false;
    });
  }

  Future<void> _testConnectivity() async {
    try {
      final connectivityResult = await Connectivity().checkConnectivity();
      final isConnected = connectivityResult != ConnectivityResult.none;
      
      _addResult(
        'Connectivité Appareil',
        isConnected ? 'Connecté (${connectivityResult.name})' : 'Non connecté',
        isConnected,
      );
    } catch (e) {
      _addResult('Connectivité Appareil', 'Erreur: $e', false);
    }
  }

  Future<void> _testInternetAccess() async {
    try {
      final hasInternet = await ApiHelpers.hasInternetConnection();
      _addResult(
        'Accès Internet',
        hasInternet ? 'Disponible' : 'Non disponible',
        hasInternet,
      );
    } catch (e) {
      _addResult('Accès Internet', 'Erreur: $e', false);
    }
  }

  Future<void> _testDnsResolution() async {
    try {
      final result = await InternetAddress.lookup('google.com')
          .timeout(const Duration(seconds: 5));
      _addResult(
        'Résolution DNS',
        'OK (${result.first.address})',
        true,
      );
    } catch (e) {
      _addResult('Résolution DNS', 'Échec: $e', false);
    }
  }

  Future<void> _testApiServerReachability() async {
    try {
      // Extract host from baseUrl
      final uri = Uri.parse(AppConstants.baseUrl);
      final host = uri.host;
      
      final result = await InternetAddress.lookup(host)
          .timeout(const Duration(seconds: 5));
      _addResult(
        'Serveur API ($host)',
        'Accessible (${result.first.address})',
        true,
      );
    } catch (e) {
      _addResult('Serveur API', 'Inaccessible: $e', false);
    }
  }

  Future<void> _testApiEndpoint() async {
    try {
      final response = await _apiService.ping();
      final success = response['success'] == true;
      _addResult(
        'Endpoint API ${AppConstants.baseUrl}',
        success 
            ? 'Réponse OK (${response['statusCode']})' 
            : 'Erreur: ${response['message']}',
        success,
      );
    } catch (e) {
      _addResult('Endpoint API', 'Échec: $e', false);
    }
  }

  void _addResult(String test, String result, bool success) {
    setState(() {
      _testResults.add({
        'test': test,
        'result': result,
        'success': success,
      });
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Diagnostic Réseau'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _isRunningTests ? null : _runDiagnostics,
          ),
        ],
      ),
      body: _isRunningTests && _testResults.isEmpty
          ? Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const CircularProgressIndicator(),
                  SizedBox(height: 16.h),
                  Text(
                    'Tests en cours...',
                    style: TextStyle(
                      fontSize: 16.sp,
                      color: AppColors.textSecondary,
                    ),
                  ),
                ],
              ),
            )
          : ListView(
              padding: EdgeInsets.all(16.w),
              children: [
                // Configuration Info
                Container(
                  padding: EdgeInsets.all(16.w),
                  decoration: BoxDecoration(
                    color: AppColors.cardBackground,
                    borderRadius: BorderRadius.circular(12.r),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Configuration',
                        style: TextStyle(
                          fontSize: 18.sp,
                          fontWeight: FontWeight.bold,
                          color: AppColors.textPrimary,
                        ),
                      ),
                      SizedBox(height: 12.h),
                      _buildInfoRow('URL API', AppConstants.baseUrl),
                      _buildInfoRow('Version', AppConstants.appVersion),
                    ],
                  ),
                ),

                SizedBox(height: 24.h),

                // Test Results
                Text(
                  'Résultats des Tests',
                  style: TextStyle(
                    fontSize: 18.sp,
                    fontWeight: FontWeight.bold,
                    color: AppColors.textPrimary,
                  ),
                ),

                SizedBox(height: 12.h),

                if (_testResults.isEmpty)
                  Center(
                    child: Text(
                      'Aucun test effectué',
                      style: TextStyle(
                        fontSize: 14.sp,
                        color: AppColors.textSecondary,
                      ),
                    ),
                  )
                else
                  ..._testResults.map((result) => _buildTestResultCard(result)),

                SizedBox(height: 24.h),

                // Instructions
                Container(
                  padding: EdgeInsets.all(16.w),
                  decoration: BoxDecoration(
                    color: Colors.blue.shade50,
                    borderRadius: BorderRadius.circular(12.r),
                    border: Border.all(color: Colors.blue.shade200),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Icon(
                            Icons.info_outline,
                            color: Colors.blue.shade700,
                            size: 20.sp,
                          ),
                          SizedBox(width: 8.w),
                          Text(
                            'Solutions',
                            style: TextStyle(
                              fontSize: 16.sp,
                              fontWeight: FontWeight.bold,
                              color: Colors.blue.shade900,
                            ),
                          ),
                        ],
                      ),
                      SizedBox(height: 8.h),
                      Text(
                        '• Vérifiez que le Wi-Fi ou les données mobiles sont activés\n'
                        '• Essayez de vous reconnecter à votre réseau\n'
                        '• Désactivez puis réactivez le mode avion\n'
                        '• Redémarrez l\'application\n'
                        '• Contactez le support si le problème persiste',
                        style: TextStyle(
                          fontSize: 14.sp,
                          color: Colors.blue.shade800,
                          height: 1.5,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
    );
  }

  Widget _buildInfoRow(String label, String value) {
    return Padding(
      padding: EdgeInsets.symmetric(vertical: 4.h),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 100.w,
            child: Text(
              '$label:',
              style: TextStyle(
                fontSize: 14.sp,
                color: AppColors.textSecondary,
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: TextStyle(
                fontSize: 14.sp,
                color: AppColors.textPrimary,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTestResultCard(Map<String, dynamic> result) {
    final success = result['success'] as bool;
    
    return Container(
      margin: EdgeInsets.only(bottom: 12.h),
      padding: EdgeInsets.all(16.w),
      decoration: BoxDecoration(
        color: success ? Colors.green.shade50 : Colors.red.shade50,
        borderRadius: BorderRadius.circular(12.r),
        border: Border.all(
          color: success ? Colors.green.shade200 : Colors.red.shade200,
        ),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(
            success ? Icons.check_circle : Icons.error,
            color: success ? Colors.green.shade700 : Colors.red.shade700,
            size: 24.sp,
          ),
          SizedBox(width: 12.w),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  result['test'],
                  style: TextStyle(
                    fontSize: 16.sp,
                    fontWeight: FontWeight.w600,
                    color: success ? Colors.green.shade900 : Colors.red.shade900,
                  ),
                ),
                SizedBox(height: 4.h),
                Text(
                  result['result'],
                  style: TextStyle(
                    fontSize: 14.sp,
                    color: success ? Colors.green.shade800 : Colors.red.shade800,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
