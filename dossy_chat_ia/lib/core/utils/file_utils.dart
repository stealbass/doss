import 'dart:io';
import 'package:path/path.dart' as path;

/// File utilities
class FileUtils {
  /// Get file extension
  static String getExtension(String filePath) {
    return path.extension(filePath).toLowerCase();
  }

  /// Get file name without extension
  static String getNameWithoutExtension(String filePath) {
    return path.basenameWithoutExtension(filePath);
  }

  /// Get file name with extension
  static String getFileName(String filePath) {
    return path.basename(filePath);
  }

  /// Check if file is PDF
  static bool isPDF(String filePath) {
    return getExtension(filePath) == '.pdf';
  }

  /// Check if file is Word document
  static bool isWord(String filePath) {
    final ext = getExtension(filePath);
    return ext == '.doc' || ext == '.docx';
  }

  /// Check if file is image
  static bool isImage(String filePath) {
    final ext = getExtension(filePath);
    return ['.jpg', '.jpeg', '.png', '.gif', '.bmp', '.webp'].contains(ext);
  }

  /// Check if file is audio
  static bool isAudio(String filePath) {
    final ext = getExtension(filePath);
    return ['.mp3', '.wav', '.m4a', '.aac', '.ogg'].contains(ext);
  }

  /// Check if file is video
  static bool isVideo(String filePath) {
    final ext = getExtension(filePath);
    return ['.mp4', '.mov', '.avi', '.mkv', '.webm'].contains(ext);
  }

  /// Get file size in bytes
  static Future<int> getFileSize(String filePath) async {
    final file = File(filePath);
    if (await file.exists()) {
      return await file.length();
    }
    return 0;
  }

  /// Format file size (bytes to KB, MB, GB)
  static String formatFileSize(int bytes) {
    if (bytes < 1024) {
      return '$bytes B';
    } else if (bytes < 1024 * 1024) {
      return '${(bytes / 1024).toStringAsFixed(1)} KB';
    } else if (bytes < 1024 * 1024 * 1024) {
      return '${(bytes / (1024 * 1024)).toStringAsFixed(1)} MB';
    } else {
      return '${(bytes / (1024 * 1024 * 1024)).toStringAsFixed(1)} GB';
    }
  }

  /// Check if file size is within limit
  static bool isWithinSizeLimit(int bytes, int limitMB) {
    final limitBytes = limitMB * 1024 * 1024;
    return bytes <= limitBytes;
  }

  /// Get file icon name based on extension
  static String getFileIcon(String filePath) {
    final ext = getExtension(filePath);
    
    switch (ext) {
      case '.pdf':
        return 'pdf';
      case '.doc':
      case '.docx':
        return 'word';
      case '.xls':
      case '.xlsx':
        return 'excel';
      case '.ppt':
      case '.pptx':
        return 'powerpoint';
      case '.zip':
      case '.rar':
      case '.7z':
        return 'archive';
      case '.jpg':
      case '.jpeg':
      case '.png':
      case '.gif':
        return 'image';
      case '.mp3':
      case '.wav':
      case '.m4a':
        return 'audio';
      case '.mp4':
      case '.mov':
      case '.avi':
        return 'video';
      case '.txt':
        return 'text';
      default:
        return 'file';
    }
  }

  /// Validate file type for upload
  static bool isValidFileType(String filePath, List<String> allowedExtensions) {
    final ext = getExtension(filePath);
    return allowedExtensions.contains(ext);
  }

  /// Get MIME type
  static String getMimeType(String filePath) {
    final ext = getExtension(filePath);
    
    final mimeTypes = {
      '.pdf': 'application/pdf',
      '.doc': 'application/msword',
      '.docx': 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
      '.jpg': 'image/jpeg',
      '.jpeg': 'image/jpeg',
      '.png': 'image/png',
      '.gif': 'image/gif',
      '.mp3': 'audio/mpeg',
      '.wav': 'audio/wav',
      '.m4a': 'audio/mp4',
      '.mp4': 'video/mp4',
      '.txt': 'text/plain',
      '.json': 'application/json',
    };
    
    return mimeTypes[ext] ?? 'application/octet-stream';
  }

  /// Create safe filename (remove special characters)
  static String sanitizeFileName(String fileName) {
    // Replace spaces with underscores
    var sanitized = fileName.replaceAll(' ', '_');
    
    // Remove special characters except dots, underscores, and hyphens
    sanitized = sanitized.replaceAll(RegExp(r'[^\w\.-]'), '');
    
    // Limit length
    if (sanitized.length > 100) {
      final ext = getExtension(sanitized);
      final nameWithoutExt = getNameWithoutExtension(sanitized);
      sanitized = '${nameWithoutExt.substring(0, 100 - ext.length)}$ext';
    }
    
    return sanitized;
  }

  /// Generate unique filename with timestamp
  static String generateUniqueFileName(String originalFileName) {
    final timestamp = DateTime.now().millisecondsSinceEpoch;
    final ext = getExtension(originalFileName);
    final nameWithoutExt = getNameWithoutExtension(originalFileName);
    final sanitizedName = sanitizeFileName(nameWithoutExt);
    
    return '${sanitizedName}_$timestamp$ext';
  }

  /// Check if file exists
  static Future<bool> exists(String filePath) async {
    final file = File(filePath);
    return await file.exists();
  }

  /// Delete file
  static Future<bool> deleteFile(String filePath) async {
    try {
      final file = File(filePath);
      if (await file.exists()) {
        await file.delete();
        return true;
      }
      return false;
    } catch (e) {
      return false;
    }
  }

  /// Create directory if not exists
  static Future<void> ensureDirectoryExists(String dirPath) async {
    final dir = Directory(dirPath);
    if (!await dir.exists()) {
      await dir.create(recursive: true);
    }
  }

  /// Get temporary directory path
  static Future<String> getTempDirPath() async {
    final dir = Directory.systemTemp;
    return dir.path;
  }

  /// Allowed document types for legal documents
  static const allowedDocumentTypes = ['.pdf', '.doc', '.docx'];

  /// Allowed audio types for transcription
  static const allowedAudioTypes = ['.mp3', '.wav', '.m4a', '.aac'];

  /// Maximum file sizes (in MB)
  static const maxDocumentSizeMB = 10;
  static const maxAudioSizeMB = 25;
  static const maxImageSizeMB = 5;
}
