import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:provider/provider.dart';
import 'package:file_picker/file_picker.dart';
import 'dart:io';
import '../../../core/theme/app_colors.dart';
import '../../../data/providers/auth_provider.dart';
import '../../../data/providers/document_provider.dart';
import '../../../l10n/app_localizations.dart';
import '../../widgets/documents/document_card.dart';

class DocumentsScreenDebug extends StatefulWidget {
  const DocumentsScreenDebug({super.key});

  @override
  State<DocumentsScreenDebug> createState() => _DocumentsScreenDebugState();
}

class _DocumentsScreenDebugState extends State<DocumentsScreenDebug> {
  String _debugLog = '🔍 DEBUG MODE - Logs will appear here\n';
  final ScrollController _scrollController = ScrollController();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _loadDocuments();
    });
    _addLog('✅ Debug screen initialized');
  }

  void _addLog(String message) {
    setState(() {
      _debugLog += '${DateTime.now().toString().split('.')[0]} | $message\n';
    });
    // Auto-scroll to bottom
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scrollController.hasClients) {
        _scrollController.jumpTo(_scrollController.position.maxScrollExtent);
      }
    });
    print('🔍 $message');
  }

  Future<void> _loadDocuments() async {
    _addLog('📥 Loading documents...');
    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final documentProvider =
        Provider.of<DocumentProvider>(context, listen: false);

    if (authProvider.token != null && authProvider.user != null) {
      _addLog('✅ Auth token found: ${authProvider.token!.substring(0, 20)}...');
      _addLog('👤 User: ${authProvider.user!.id}');
      try {
        await documentProvider.loadDocuments(
          token: authProvider.token!,
          userId: authProvider.user!.id,
          forceRefresh: true,
        );
        _addLog('✅ Documents loaded: ${documentProvider.documents.length} documents');
      } catch (e) {
        _addLog('❌ Error loading documents: $e');
      }
    } else {
      _addLog('❌ No auth token or user found!');
    }
  }

  Future<void> _pickAndUploadDocument() async {
    _addLog('📁 Opening file picker...');
    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final documentProvider =
        Provider.of<DocumentProvider>(context, listen: false);
    final l10n = AppLocalizations.of(context)!;

    if (!authProvider.user!.canDownload) {
      _addLog('⚠️ Upload quota exhausted');
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(l10n.uploadQuotaExhausted),
          backgroundColor: AppColors.warning,
        ),
      );
      return;
    }

    try {
      FilePickerResult? result = await FilePicker.platform.pickFiles(
        type: FileType.custom,
        allowedExtensions: [
          'pdf',
          'doc',
          'docx',
          'xls',
          'xlsx',
          'ppt',
          'pptx',
          'txt'
        ],
      );

      if (result != null && result.files.single.path != null) {
        final file = File(result.files.single.path!);
        _addLog('📄 File selected: ${result.files.single.name}');
        _addLog('📍 File path: ${file.path}');
        _addLog('📊 File size: ${file.lengthSync()} bytes');

        if (!mounted) return;

        _addLog('📤 Starting upload...');

        try {
          final success = await documentProvider.uploadDocument(
            token: authProvider.token!,
            file: file,
            userId: authProvider.user!.id,
            title: result.files.single.name,
          );

          if (!mounted) return;

          if (success) {
            _addLog('✅ Upload successful!');
            _addLog('📝 New document added to list');
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: Text(l10n.documentUploadedSuccess),
                backgroundColor: AppColors.success,
              ),
            );
          } else {
            final errorMessage = documentProvider.error ?? l10n.uploadError;
            _addLog('❌ Upload failed: $errorMessage');
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: Text(errorMessage),
                backgroundColor: AppColors.error,
              ),
            );
          }
        } catch (uploadError) {
          _addLog('❌ Exception during upload: $uploadError');
          _addLog('📋 Stack trace: ${uploadError.toString()}');
          if (!mounted) return;
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('Upload error: $uploadError'),
              backgroundColor: AppColors.error,
            ),
          );
        }
      } else {
        _addLog('⚠️ No file selected');
      }
    } catch (e) {
      _addLog('❌ File picker error: $e');
      print('❌ File picker error: $e');
    }
  }

  void _clearLog() {
    setState(() {
      _debugLog = '🔍 DEBUG MODE - Logs cleared\n';
    });
  }

  void _copyLogToClipboard() {
    // Copy to clipboard functionality
    _addLog('📋 Log copied to clipboard');
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('📄 Documents - DEBUG MODE'),
        backgroundColor: Colors.indigo,
        elevation: 0,
        actions: [
          IconButton(
            icon: const Icon(Icons.delete),
            onPressed: _clearLog,
            tooltip: 'Clear logs',
          ),
          IconButton(
            icon: const Icon(Icons.copy),
            onPressed: _copyLogToClipboard,
            tooltip: 'Copy logs',
          ),
        ],
      ),
      body: Column(
        children: [
          // Debug Log Panel
          Container(
            height: 250.h,
            color: Colors.grey[900],
            child: Column(
              children: [
                Padding(
                  padding: EdgeInsets.all(8.w),
                  child: const Text(
                    '🔍 DEBUG LOG',
                    style: TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.bold,
                      fontSize: 12,
                    ),
                  ),
                ),
                Expanded(
                  child: SingleChildScrollView(
                    controller: _scrollController,
                    child: Padding(
                      padding: EdgeInsets.all(8.w),
                      child: Text(
                        _debugLog,
                        style: const TextStyle(
                          color: Colors.greenAccent,
                          fontFamily: 'Courier',
                          fontSize: 10,
                        ),
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
          // Button Section
          Padding(
            padding: EdgeInsets.all(16.w),
            child: Column(
              children: [
                ElevatedButton.icon(
                  onPressed: _pickAndUploadDocument,
                  icon: const Icon(Icons.upload_file),
                  label: const Text('📤 Téléverser un document'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.indigo,
                    minimumSize: Size(double.infinity, 50.h),
                  ),
                ),
                SizedBox(height: 12.h),
                ElevatedButton.icon(
                  onPressed: _loadDocuments,
                  icon: const Icon(Icons.refresh),
                  label: const Text('🔄 Recharger la liste'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.teal,
                    minimumSize: Size(double.infinity, 50.h),
                  ),
                ),
              ],
            ),
          ),
          // Documents List
          Expanded(
            child: Consumer<DocumentProvider>(
              builder: (context, provider, _) {
                if (provider.isLoading) {
                  return const Center(
                    child: CircularProgressIndicator(),
                  );
                }

                if (provider.documents.isEmpty) {
                  return Center(
                    child: Text(
                      'Aucun document',
                      style: TextStyle(
                        fontSize: 16.sp,
                        color: Colors.grey,
                      ),
                    ),
                  );
                }

                return ListView.builder(
                  itemCount: provider.documents.length,
                  itemBuilder: (context, index) {
                    final document = provider.documents[index];
                    return Card(
                      margin: EdgeInsets.symmetric(
                        horizontal: 16.w,
                        vertical: 8.h,
                      ),
                      child: ListTile(
                        leading: const Icon(
                          Icons.description,
                          color: Colors.indigo,
                        ),
                        title: Text(
                          document.name,
                          style: const TextStyle(fontWeight: FontWeight.bold),
                        ),
                        subtitle: Text(
                          'ID: ${document.id}\nSize: ${(document.fileSize ?? 0) / 1024 / 1024} MB',
                          style: TextStyle(fontSize: 10.sp),
                        ),
                        trailing: const Icon(
                          Icons.check_circle,
                          color: Colors.green,
                        ),
                      ),
                    );
                  },
                );
              },
            ),
          ),
        ],
      ),
    );
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }
}
