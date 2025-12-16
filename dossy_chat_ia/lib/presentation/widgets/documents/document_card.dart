import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/theme/app_colors.dart';
import '../../../data/models/document_model.dart';

class DocumentCard extends StatelessWidget {
  final DocumentModel document;
  final VoidCallback onDelete;

  const DocumentCard({
    super.key,
    required this.document,
    required this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: EdgeInsets.only(bottom: 12.h),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12.r),
        border: Border.all(color: AppColors.border),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 4,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(12.r),
          onTap: () => _openDocument(),
          child: Padding(
            padding: EdgeInsets.all(12.w),
            child: Row(
              children: [
                // File Icon
                Container(
                  width: 48.w,
                  height: 48.w,
                  decoration: BoxDecoration(
                    color: _getFileColor().withOpacity(0.1),
                    borderRadius: BorderRadius.circular(8.r),
                  ),
                  child: Center(
                    child: Icon(
                      _getFileIcon(),
                      size: 24.sp,
                      color: _getFileColor(),
                    ),
                  ),
                ),
                
                SizedBox(width: 12.w),
                
                // Document Info
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        document.name,
                        style: TextStyle(
                          fontSize: 14.sp,
                          fontWeight: FontWeight.w600,
                          color: AppColors.textPrimary,
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                      SizedBox(height: 4.h),
                      Row(
                        children: [
                          if (document.category != null) ...[
                            Container(
                              padding: EdgeInsets.symmetric(
                                horizontal: 6.w,
                                vertical: 2.h,
                              ),
                              decoration: BoxDecoration(
                                color: AppColors.getCategoryColor(document.category!)
                                    .withOpacity(0.1),
                                borderRadius: BorderRadius.circular(4.r),
                              ),
                              child: Text(
                                document.category!,
                                style: TextStyle(
                                  fontSize: 10.sp,
                                  color: AppColors.getCategoryColor(document.category!),
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                            ),
                            SizedBox(width: 8.w),
                          ],
                          Text(
                            document.fileSizeFormatted,
                            style: TextStyle(
                              fontSize: 11.sp,
                              color: AppColors.textSecondary,
                            ),
                          ),
                          SizedBox(width: 8.w),
                          Text(
                            '•',
                            style: TextStyle(
                              fontSize: 11.sp,
                              color: AppColors.textHint,
                            ),
                          ),
                          SizedBox(width: 8.w),
                          Text(
                            document.fileExtension,
                            style: TextStyle(
                              fontSize: 11.sp,
                              color: AppColors.textSecondary,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ],
                      ),
                      if (document.description != null && document.description!.isNotEmpty) ...[
                        SizedBox(height: 4.h),
                        Text(
                          document.description!,
                          style: TextStyle(
                            fontSize: 11.sp,
                            color: AppColors.textSecondary,
                            fontStyle: FontStyle.italic,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ],
                      SizedBox(height: 4.h),
                      Text(
                        _formatDate(document.uploadedAt),
                        style: TextStyle(
                          fontSize: 10.sp,
                          color: AppColors.textHint,
                        ),
                      ),
                    ],
                  ),
                ),
                
                // Actions
                PopupMenuButton(
                  icon: Icon(
                    Icons.more_vert,
                    size: 20.sp,
                    color: AppColors.textSecondary,
                  ),
                  itemBuilder: (context) => [
                    PopupMenuItem(
                      child: Row(
                        children: [
                          const Icon(Icons.open_in_new, size: 18),
                          SizedBox(width: 8.w),
                          const Text('Ouvrir'),
                        ],
                      ),
                      onTap: () => _openDocument(),
                    ),
                    PopupMenuItem(
                      child: Row(
                        children: [
                          const Icon(Icons.delete, size: 18, color: AppColors.error),
                          SizedBox(width: 8.w),
                          const Text('Supprimer', style: TextStyle(color: AppColors.error)),
                        ],
                      ),
                      onTap: () {
                        // Delay to allow menu to close first
                        Future.delayed(const Duration(milliseconds: 100), onDelete);
                      },
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  IconData _getFileIcon() {
    switch (document.fileType.toLowerCase()) {
      case 'pdf':
        return Icons.picture_as_pdf;
      case 'doc':
      case 'docx':
        return Icons.description;
      case 'jpg':
      case 'jpeg':
      case 'png':
        return Icons.image;
      default:
        return Icons.insert_drive_file;
    }
  }

  Color _getFileColor() {
    switch (document.fileType.toLowerCase()) {
      case 'pdf':
        return const Color(0xFFE53935);
      case 'doc':
      case 'docx':
        return const Color(0xFF1976D2);
      case 'jpg':
      case 'jpeg':
      case 'png':
        return const Color(0xFF43A047);
      default:
        return AppColors.textSecondary;
    }
  }

  String _formatDate(DateTime date) {
    final now = DateTime.now();
    final difference = now.difference(date);

    if (difference.inDays == 0) {
      if (difference.inHours < 1) {
        return 'Il y a ${difference.inMinutes} min';
      }
      return 'Il y a ${difference.inHours}h';
    } else if (difference.inDays < 7) {
      return 'Il y a ${difference.inDays} jours';
    } else {
      return '${date.day}/${date.month}/${date.year}';
    }
  }

  Future<void> _openDocument() async {
    final Uri url = Uri.parse(document.fileUrl);
    if (await canLaunchUrl(url)) {
      await launchUrl(url, mode: LaunchMode.externalApplication);
    }
  }
}
