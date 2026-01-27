import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:provider/provider.dart';
import 'package:http/http.dart' as http;
import 'package:path_provider/path_provider.dart';
import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;
import 'package:open_file/open_file.dart';
import 'dart:io';
import '../../../core/theme/app_colors.dart';
import '../../../data/models/message_model.dart';
import '../../../l10n/app_localizations.dart';
import '../../../data/providers/auth_provider.dart';

class ChatBubble extends StatelessWidget {
  final MessageModel message;

  const ChatBubble({
    super.key,
    required this.message,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(bottom: 16.h),
      child: Row(
        mainAxisAlignment:
            message.isUser ? MainAxisAlignment.end : MainAxisAlignment.start,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (!message.isUser) ...[
            CircleAvatar(
              radius: 16.r,
              backgroundColor: AppColors.primary,
              child: Icon(
                Icons.smart_toy,
                size: 16.sp,
                color: Colors.white,
              ),
            ),
            SizedBox(width: 8.w),
          ],
          Flexible(
            child: Column(
              crossAxisAlignment: message.isUser
                  ? CrossAxisAlignment.end
                  : CrossAxisAlignment.start,
              children: [
                GestureDetector(
                  onLongPress: () {
                    _showMessageOptions(context);
                  },
                  child: Container(
                    padding: EdgeInsets.symmetric(
                      horizontal: 16.w,
                      vertical: 12.h,
                    ),
                    decoration: BoxDecoration(
                      color: message.isUser
                          ? AppColors.userMessageBg
                          : AppColors.aiMessageBg,
                      borderRadius: BorderRadius.circular(16.r),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          message.content,
                          style: TextStyle(
                            fontSize: 14.sp,
                            color: message.isUser
                                ? AppColors.userMessageText
                                : AppColors.aiMessageText,
                          ),
                        ),
                        
                        // Anonymization Badge
                        if (message.isAnonymized == true) ...[
                          SizedBox(height: 8.h),
                          Container(
                            padding: EdgeInsets.symmetric(
                              horizontal: 8.w,
                              vertical: 4.h,
                            ),
                            decoration: BoxDecoration(
                              color: AppColors.info.withAlpha((0.2 * 255).round()),
                              borderRadius: BorderRadius.circular(8.r),
                            ),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Icon(
                                  Icons.shield_outlined,
                                  size: 12.sp,
                                  color: AppColors.info,
                                ),
                                SizedBox(width: 4.w),
                                Text(
                                  'Anonymisé',
                                  style: TextStyle(
                                    fontSize: 10.sp,
                                    color: AppColors.info,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                        
                        // Generated Document (if applicable)
                        if (!message.isUser && message.isDocumentGeneration == true && message.generatedDocument != null) ...[
                          SizedBox(height: 12.h),
                          _buildGeneratedDocumentWidget(context, message.generatedDocument!),
                        ],
                        
                        // Sources
                        if (!message.isUser && message.sources != null && message.sources!.isNotEmpty) ...[
                          SizedBox(height: 12.h),
                          Text(
                            'Sources :',
                            style: TextStyle(
                              fontSize: 11.sp,
                              color: AppColors.textSecondary,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                          SizedBox(height: 6.h),
                          Wrap(
                            spacing: 6.w,
                            runSpacing: 6.h,
                            children: message.sources!.map((source) {
                              final title = source['title'] ?? 'Document';
                              final type = source['type'] ?? 'unknown';
                              final docId = source['id'];
                              
                              // Get color and icon based on source type
                              final (sourceColor, sourceIcon, sourceLabel) = _getSourceTypeStyle(type);
                              
                              return InkWell(
                                onTap: () {
                                  _onSourceTap(context, source);
                                },
                                borderRadius: BorderRadius.circular(8.r),
                                child: Container(
                                  padding: EdgeInsets.symmetric(
                                    horizontal: 10.w,
                                    vertical: 6.h,
                                  ),
                                  decoration: BoxDecoration(
                                    color: sourceColor.withAlpha((0.15 * 255).round()),
                                    borderRadius: BorderRadius.circular(8.r),
                                    border: Border.all(
                                      color: sourceColor.withAlpha((0.3 * 255).round()),
                                      width: 1,
                                    ),
                                  ),
                                  child: Row(
                                    mainAxisSize: MainAxisSize.min,
                                    children: [
                                      Icon(
                                        sourceIcon,
                                        size: 14.sp,
                                        color: sourceColor,
                                      ),
                                      SizedBox(width: 4.w),
                                      Flexible(
                                        child: Column(
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          children: [
                                            Text(
                                              sourceLabel,
                                              style: TextStyle(
                                                fontSize: 9.sp,
                                                color: sourceColor,
                                                fontWeight: FontWeight.w600,
                                              ),
                                            ),
                                            Text(
                                              title.length > 25
                                                  ? '${title.substring(0, 25)}...'
                                                  : title,
                                              style: TextStyle(
                                                fontSize: 11.sp,
                                                color: sourceColor,
                                                fontWeight: FontWeight.w500,
                                              ),
                                              maxLines: 1,
                                              overflow: TextOverflow.ellipsis,
                                            ),
                                          ],
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              );
                            }).toList(),
                          ),
                        ],
                      ],
                    ),
                  ),
                ),
                
                SizedBox(height: 4.h),
                
                // Timestamp
                Text(
                  _formatTime(message.timestamp),
                  style: TextStyle(
                    fontSize: 11.sp,
                    color: AppColors.textHint,
                  ),
                ),
              ],
            ),
          ),
          if (message.isUser) ...[
            SizedBox(width: 8.w),
            CircleAvatar(
              radius: 16.r,
              backgroundColor: AppColors.primary.withAlpha((0.2 * 255).round()),
              child: Icon(
                Icons.person,
                size: 16.sp,
                color: AppColors.primary,
              ),
            ),
          ],
        ],
      ),
    );
  }

  String _formatTime(DateTime timestamp) {
    final now = DateTime.now();
    final difference = now.difference(timestamp);

    if (difference.inMinutes < 1) {
      return 'À l\'instant';
    } else if (difference.inHours < 1) {
      return '${difference.inMinutes} min';
    } else if (difference.inDays < 1) {
      return '${difference.inHours}h';
    } else {
      return '${timestamp.day}/${timestamp.month}/${timestamp.year}';
    }
  }

  void _showMessageOptions(BuildContext context) {
    showModalBottomSheet(
      context: context,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20.r)),
      ),
      builder: (context) {
        return SafeArea(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              ListTile(
                leading: const Icon(Icons.copy),
                title: const Text('Copier'),
                onTap: () {
                  Clipboard.setData(ClipboardData(text: message.content));
                  Navigator.pop(context);
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(
                      content: Text('Message copié'),
                      duration: Duration(seconds: 1),
                    ),
                  );
                },
              ),
              if (!message.isUser && message.sources != null && message.sources!.isNotEmpty)
                ListTile(
                  leading: const Icon(Icons.source),
                  title: const Text('Voir les sources'),
                  onTap: () {
                    Navigator.pop(context);
                    _showSources(context);
                  },
                ),
            ],
          ),
        );
      },
    );
  }

  void _onSourceTap(BuildContext context, Map<String, dynamic> source) {
    final l10n = AppLocalizations.of(context)!;
    final title = source['title'] ?? 'Document';
    final type = source['type'] ?? 'unknown';
    final docId = source['id'];
    final fileName = source['file_name'];
    final filePath = source['file_path'];
    
    // Get type-specific label
    final (sourceColor, _, sourceLabel) = _getSourceTypeStyle(type);
    final typeDescription = _getSourceTypeDescription(type);
    
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(title),
        content: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                padding: EdgeInsets.all(8.w),
                decoration: BoxDecoration(
                  color: sourceColor.withAlpha((0.1 * 255).round()),
                  borderRadius: BorderRadius.circular(6.r),
                  border: Border.all(
                    color: sourceColor.withAlpha((0.3 * 255).round()),
                    width: 1,
                  ),
                ),
                child: Text(
                  sourceLabel,
                  style: TextStyle(
                    fontSize: 12.sp,
                    color: sourceColor,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
              SizedBox(height: 12.h),
              Text(
                typeDescription,
                style: TextStyle(
                  fontSize: 12.sp,
                  color: AppColors.textSecondary,
                ),
              ),
              if (docId != null) ...[
                SizedBox(height: 8.h),
                Text(
                  'ID: $docId',
                  style: TextStyle(
                    fontSize: 11.sp,
                    color: AppColors.textHint,
                  ),
                ),
              ],
              if (fileName != null) ...[
                SizedBox(height: 8.h),
                Text(
                  'Fichier: $fileName',
                  style: TextStyle(
                    fontSize: 11.sp,
                    color: AppColors.textHint,
                  ),
                ),
              ],
              SizedBox(height: 16.h),
              Text(
                _getNavigationMessage(type),
                style: TextStyle(fontSize: 13.sp),
              ),
            ],
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: Text(l10n.cancel),
          ),
          ElevatedButton.icon(
            onPressed: () {
              Navigator.pop(context);
              _navigateToSource(context, source, type);
            },
            icon: Icon(_getNavigationIcon(type)),
            label: Text(_getNavigationButtonLabel(type)),
            style: ElevatedButton.styleFrom(
              backgroundColor: sourceColor,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildGeneratedDocumentWidget(BuildContext context, Map<String, dynamic> generatedDoc) {
    final downloadUrl = generatedDoc['download_url'] as String?;
    final templateName = generatedDoc['template_name'] as String? ?? 'Document généré';
    final fileName = generatedDoc['file_name'] as String? ?? 'document.txt';
    final fileSize = generatedDoc['file_size'] as int?;

    return Container(
      width: double.infinity,
      padding: EdgeInsets.all(12.w),
      decoration: BoxDecoration(
        color: AppColors.primary.withAlpha((0.08 * 255).round()),
        borderRadius: BorderRadius.circular(10.r),
        border: Border.all(
          color: AppColors.primary.withAlpha((0.2 * 255).round()),
          width: 1,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(Icons.description, color: AppColors.primary, size: 18.sp),
              SizedBox(width: 8.w),
              Expanded(
                child: Text(
                  templateName,
                  style: TextStyle(
                    fontSize: 13.sp,
                    fontWeight: FontWeight.w700,
                    color: AppColors.primary,
                  ),
                ),
              ),
            ],
          ),
          SizedBox(height: 8.h),
          Text(
            fileName,
            style: TextStyle(fontSize: 12.sp, color: AppColors.textSecondary),
          ),
          if (fileSize != null) ...[
            SizedBox(height: 4.h),
            Text(
              '${(fileSize / 1024).toStringAsFixed(1)} Ko',
              style: TextStyle(fontSize: 11.sp, color: AppColors.textHint),
            ),
          ],
          SizedBox(height: 10.h),
          Row(
            children: [
              Expanded(
                child: ElevatedButton.icon(
                  onPressed: downloadUrl == null
                      ? null
                      : () => _downloadGeneratedDocument(context, downloadUrl, fileName),
                  icon: const Icon(Icons.download),
                  label: const Text('Télécharger'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.primary,
                    foregroundColor: Colors.white,
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Future<void> _downloadGeneratedDocument(BuildContext context, String url, String fileName) async {
    final auth = context.read<AuthProvider>();
    final token = auth.token;

    if (token == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Session expirée. Veuillez vous reconnecter.')),
      );
      return;
    }

    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Téléchargement du document...')),
    );

    try {
      // 1. Download HTML from backend
      final response = await http.get(
        Uri.parse(url),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'text/html',
        },
      ).timeout(const Duration(seconds: 20));

      if (response.statusCode != 200) {
        throw Exception('HTTP ${response.statusCode}');
      }

      // 2. Extract HTML content
      final htmlContent = response.body;

      // 3. Convert HTML to PDF
      final pdf = pw.Document();
      
      // Parse and add HTML content to PDF
      _addHtmlToPdf(pdf, htmlContent, fileName);

      // 4. Get documents directory
      final directory = await getApplicationDocumentsDirectory();
      final downloadsDir = Directory('${directory.path}/Documents');
      if (!await downloadsDir.exists()) {
        await downloadsDir.create(recursive: true);
      }

      // 5. Save PDF with unique timestamp
      final safeName = _sanitizeFileName(fileName);
      final timestamp = DateTime.now().millisecondsSinceEpoch;
      final pdfFileName = safeName.replaceAll('.html', '.pdf').replaceAll('.txt', '.pdf');
      final uniquePdfName = pdfFileName.endsWith('.pdf') 
          ? pdfFileName.replaceFirst('.pdf', '_$timestamp.pdf')
          : '$pdfFileName\_$timestamp.pdf';
      final filePath = '${downloadsDir.path}/$uniquePdfName';
      
      final pdfBytes = await pdf.save();
      final file = File(filePath);
      await file.writeAsBytes(pdfBytes);

      // 6. Log success
      debugPrint('[ChatBubble] Document downloaded: $uniquePdfName (${(pdfBytes.length / 1024).toStringAsFixed(1)} Ko)');

      // 7. Show dialog with option to open
      if (context.mounted) {
        showDialog(
          context: context,
          builder: (context) => AlertDialog(
            title: const Text('Document téléchargé'),
            content: Text('Le fichier "$uniquePdfName" a été enregistré avec succès.\n\nTaille: ${(pdfBytes.length / 1024).toStringAsFixed(1)} Ko'),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(context),
                child: const Text('Fermer'),
              ),
              ElevatedButton.icon(
                onPressed: () async {
                  Navigator.pop(context);
                  final result = await OpenFile.open(filePath);
                  if (result.type != ResultType.done) {
                    if (context.mounted) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        SnackBar(content: Text('Impossible d\'ouvrir le fichier: ${result.message}')),
                      );
                    }
                  }
                },
                icon: const Icon(Icons.open_in_new),
                label: const Text('Ouvrir'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.primary,
                  foregroundColor: Colors.white,
                ),
              ),
            ],
          ),
        );
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Échec du téléchargement: $e')),
      );
      debugPrint('[ChatBubble] Download error: $e');
    }
  }

  /// Convert HTML to PDF by extracting text and formatting
  void _addHtmlToPdf(pw.Document pdf, String htmlContent, String title) {
    // Extract text from HTML (simple regex-based extraction)
    final cleanText = _extractTextFromHtml(htmlContent);
    
    // Split content into paragraphs for better pagination
    final paragraphs = cleanText.split('\n\n').where((p) => p.isNotEmpty).toList();
    
    // Create a formatted PDF with proper pagination
    pdf.addPage(
      pw.MultiPage(
        pageFormat: PdfPageFormat.a4,
        margin: pw.EdgeInsets.all(20),
        build: (pw.Context context) {
          final List<pw.Widget> widgets = [];
          
          // Header with title
          widgets.add(
            pw.Column(
              crossAxisAlignment: pw.CrossAxisAlignment.start,
              children: [
                pw.Text(
                  _sanitizeFileName(title),
                  style: pw.TextStyle(
                    fontSize: 20,
                    fontWeight: pw.FontWeight.bold,
                  ),
                ),
                pw.SizedBox(height: 5),
                pw.Text(
                  'Généré le: ${DateTime.now().toString().split('.')[0]}',
                  style: pw.TextStyle(
                    fontSize: 10,
                    color: PdfColors.grey,
                  ),
                ),
                pw.Divider(),
                pw.SizedBox(height: 10),
              ],
            ),
          );
          
          // Add paragraphs with proper spacing
          for (final paragraph in paragraphs) {
            widgets.add(
              pw.Paragraph(
                text: paragraph,
                style: pw.TextStyle(
                  fontSize: 11,
                  lineSpacing: 1.5,
                ),
              ),
            );
            widgets.add(pw.SizedBox(height: 8));
          }
          
          // Footer
          widgets.add(pw.SizedBox(height: 10));
          widgets.add(pw.Divider());
          widgets.add(
            pw.Text(
              'Document généré par Dossy AI',
              style: pw.TextStyle(
                fontSize: 9,
                color: PdfColors.grey,
              ),
              textAlign: pw.TextAlign.center,
            ),
          );
          
          return widgets;
        },
        footer: (pw.Context context) {
          return pw.Container(
            alignment: pw.Alignment.centerRight,
            margin: const pw.EdgeInsets.only(top: 1.0 * PdfPageFormat.cm),
            child: pw.Text(
              'Page ${context.pageNumber}/${context.pagesCount}',
              style: const pw.TextStyle(fontSize: 8),
            ),
          );
        },
      ),
    );
  }

  /// Extract clean text from HTML content
  String _extractTextFromHtml(String html) {
    // Remove script tags
    String cleaned = html.replaceAll(RegExp(r'<script.*?</script>', dotAll: true), '');
    
    // Remove style tags
    cleaned = cleaned.replaceAll(RegExp(r'<style.*?</style>', dotAll: true), '');
    
    // Replace common HTML tags with newlines
    cleaned = cleaned.replaceAll(RegExp(r'</p>'), '\n\n');
    cleaned = cleaned.replaceAll(RegExp(r'</h[1-6]>'), '\n\n');
    cleaned = cleaned.replaceAll(RegExp(r'<br\s*/?>', multiLine: true), '\n');
    cleaned = cleaned.replaceAll(RegExp(r'</div>'), '\n');
    cleaned = cleaned.replaceAll(RegExp(r'</li>'), '\n');
    
    // Remove all remaining HTML tags
    cleaned = cleaned.replaceAll(RegExp(r'<[^>]+>'), '');
    
    // Decode HTML entities
    cleaned = cleaned
        .replaceAll('&nbsp;', ' ')
        .replaceAll('&lt;', '<')
        .replaceAll('&gt;', '>')
        .replaceAll('&amp;', '&')
        .replaceAll('&quot;', '"')
        .replaceAll('&#39;', "'");
    
    // Clean up multiple spaces and newlines
    cleaned = cleaned.replaceAll(RegExp(r' +'), ' ');
    cleaned = cleaned.replaceAll(RegExp(r'\n{3,}'), '\n\n');
    
    return cleaned.trim();
  }

  /// Sanitize file name
  String _sanitizeFileName(String fileName) {
    return fileName
        .replaceAll(RegExp(r'[<>:"/\\|?*]'), '')
        .replaceAll(RegExp(r'\.{2,}'), '.')
        .trim();
  }

  (Color, IconData, String) _getSourceTypeStyle(String type) {
    switch (type) {
      // Backend types
      case 'legal':
      case 'legal_document':
        return (const Color(0xFF4CAF50), Icons.library_books, 'Document juridique');
      case 'template':
      case 'document_template':
        return (const Color(0xFF2196F3), Icons.description, 'Modèle de document');
      case 'fiscal':
      case 'fiscal_resource':
        return (const Color(0xFFFF9800), Icons.account_balance_wallet, 'Ressource fiscale');
      case 'user_document_semantic':
      case 'user_document_local':
        return (const Color(0xFF9C27B0), Icons.folder, 'Mes documents');
      default:
        return (AppColors.primary, Icons.article_outlined, 'Document');
    }
  }

  String _getSourceTypeDescription(String type) {
    switch (type) {
      case 'legal':
      case 'legal_document':
        return 'Document provenant de la bibliothèque juridique spécialisée';
      case 'template':
      case 'document_template':
        return 'Modèle de document prêt à l\'emploi et personnalisable';
      case 'fiscal':
      case 'fiscal_resource':
        return 'Ressource fiscale ou paramètre fiscal à jour';
      case 'user_document_semantic':
      case 'user_document_local':
        return 'Document téléchargé par vous';
      default:
        return 'Document de référence';
    }
  }

  String _getNavigationMessage(String type) {
    switch (type) {
      case 'legal':
      case 'legal_document':
        return 'Ce document est disponible dans la bibliothèque juridique.';
      case 'template':
      case 'document_template':
        return 'Vous pouvez télécharger ce modèle et l\'adapter à vos besoins.';
      case 'fiscal':
      case 'fiscal_resource':
        return 'Cette ressource fiscale est disponible dans vos ressources.';
      case 'user_document_semantic':
      case 'user_document_local':
        return 'Voir ce document dans vos fichiers.';
      default:
        return 'Cliquez ci-dessous pour accéder à ce document.';
    }
  }

  String _getNavigationButtonLabel(String type) {
    switch (type) {
      case 'legal':
      case 'legal_document':
        return 'Voir';
      case 'template':
      case 'document_template':
        return 'Télécharger';
      case 'fiscal':
      case 'fiscal_resource':
        return 'Consulter';
      case 'user_document_semantic':
      case 'user_document_local':
        return 'Ouvrir';
      default:
        return 'Ouvrir';
    }
  }

  IconData _getNavigationIcon(String type) {
    switch (type) {
      case 'legal':
      case 'legal_document':
        return Icons.library_books;
      case 'template':
      case 'document_template':
        return Icons.download;
      case 'fiscal':
      case 'fiscal_resource':
        return Icons.account_balance_wallet;
      case 'user_document_semantic':
      case 'user_document_local':
        return Icons.folder;
      default:
        return Icons.open_in_new;
    }
  }

  void _navigateToSource(BuildContext context, Map<String, dynamic> source, String type) {
    final docId = source['id'];
    
    switch (type) {
      case 'legal':
      case 'legal_document':
        Navigator.pushNamed(
          context,
          '/legal-library',
          arguments: {'documentId': docId},
        );
        break;
      case 'template':
      case 'document_template':
        // Navigate to template details
        // First, we need to fetch the template data from the backend
        // For now, redirect to templates list
        Navigator.pushNamed(
          context,
          '/templates',
          arguments: {'templateId': docId},
        );
        break;
      case 'fiscal':
      case 'fiscal_resource':
        // Navigate to fiscal resource detail
        Navigator.pushNamed(
          context,
          '/fiscal-resources',
          arguments: {'resourceId': docId},
        );
        break;
      case 'user_document_semantic':
      case 'user_document_local':
        // Navigate to user documents
        Navigator.pushNamed(
          context,
          '/documents',
          arguments: {'documentId': docId},
        );
        break;
      default:
        // Fallback to legal library
        Navigator.pushNamed(
          context,
          '/legal-library',
          arguments: {'documentId': docId},
        );
    }
  }

  void _showSources(BuildContext context) {
    showDialog(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: const Text('Sources'),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: message.sources!.map((source) {
                final title = source is Map ? (source['title'] ?? 'Document') : source.toString();
                final type = source is Map ? (source['type'] ?? 'unknown') : 'text';
                return Padding(
                  padding: EdgeInsets.only(bottom: 8.h),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Icon(
                        Icons.article,
                        size: 16.sp,
                        color: AppColors.primary,
                      ),
                      SizedBox(width: 8.w),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              title,
                              style: TextStyle(
                                fontSize: 13.sp,
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                            if (type.isNotEmpty && type != 'text')
                              Padding(
                                padding: EdgeInsets.only(top: 4.h),
                                child: Text(
                                  type,
                                  style: TextStyle(
                                    fontSize: 11.sp,
                                    color: AppColors.textSecondary,
                                  ),
                                ),
                              ),
                          ],
                        ),
                      ),
                    ],
                  ),
                );
              }).toList(),
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: Text(AppLocalizations.of(context)!.close),
            ),
          ],
        );
      },
    );
  }
}
