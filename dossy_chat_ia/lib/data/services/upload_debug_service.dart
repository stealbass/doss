import 'package:http/http.dart' as http;
import 'dart:io';
import 'dart:convert';

/// Service d'upload amélioré pour debug détaillé
class UploadDebugService {
  final String baseUrl;
  
  UploadDebugService({required this.baseUrl});
  
  /// Upload avec logs détaillés
  Future<Map<String, dynamic>> uploadDocumentWithDebug({
    required String token,
    required File file,
    String? title,
    void Function(String)? onLog,
  }) async {
    final log = onLog ?? print;
    
    try {
      log('🔵 [DEBUG] Starting upload process');
      log('📋 [INFO] Token: ${token.substring(0, 20)}...');
      log('📁 [INFO] File path: ${file.path}');
      log('📊 [INFO] File exists: ${file.existsSync()}');
      
      if (!file.existsSync()) {
        log('❌ [ERROR] File not found at: ${file.path}');
        return {
          'success': false,
          'message': 'File not found',
          'debug': 'File does not exist at the specified path'
        };
      }
      
      final fileSize = file.lengthSync();
      log('📦 [INFO] File size: $fileSize bytes (${(fileSize / 1024 / 1024).toStringAsFixed(2)} MB)');
      
      // Check file size limit (typically 50MB)
      if (fileSize > 50 * 1024 * 1024) {
        log('⚠️ [WARNING] File exceeds 50MB limit');
      }
      
      log('🔌 [DEBUG] Creating multipart request to: $baseUrl/documents/upload');
      
      final request = http.MultipartRequest(
        'POST',
        Uri.parse('$baseUrl/documents/upload'),
      );
      
      log('📝 [DEBUG] Adding headers...');
      request.headers.addAll({
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
      });
      log('✅ [DEBUG] Headers added');
      
      log('📄 [DEBUG] Adding file to request...');
      final multipartFile = await http.MultipartFile.fromPath('file', file.path);
      request.files.add(multipartFile);
      log('✅ [DEBUG] File added (field name: "file")');
      
      if (title != null) {
        log('📌 [DEBUG] Adding title: $title');
        request.fields['title'] = title;
      }
      
      log('🚀 [DEBUG] Sending request...');
      log('⏱️ [DEBUG] Request timeout: 120 seconds');
      
      final streamedResponse = await request.send().timeout(
        const Duration(seconds: 120),
        onTimeout: () {
          log('❌ [ERROR] Request timeout after 120 seconds');
          throw TimeoutException('Upload timeout', const Duration(seconds: 120));
        },
      );
      
      log('📩 [DEBUG] Response received - Status code: ${streamedResponse.statusCode}');
      log('📊 [DEBUG] Content length: ${streamedResponse.contentLength}');
      
      final response = await http.Response.fromStream(streamedResponse);
      
      log('📖 [DEBUG] Response body length: ${response.body.length} chars');
      
      if (response.statusCode == 200 || response.statusCode == 201) {
        log('✅ [SUCCESS] Upload successful!');
        log('📄 [DEBUG] Response: ${response.body.substring(0, Math.min(200, response.body.length))}...');
        
        try {
          final decoded = json.decode(response.body);
          log('✅ [DEBUG] Response decoded successfully');
          return decoded;
        } catch (e) {
          log('⚠️ [WARNING] Could not decode JSON: $e');
          return {
            'success': true,
            'message': 'Upload successful (JSON decode error)',
            'debug': response.body.substring(0, Math.min(500, response.body.length))
          };
        }
      } else {
        log('❌ [ERROR] Upload failed with status: ${response.statusCode}');
        log('📄 [DEBUG] Response body: ${response.body}');
        
        try {
          final errorData = json.decode(response.body);
          log('❌ [ERROR] Error message: ${errorData['message'] ?? 'Unknown error'}');
          return {
            'success': false,
            'message': errorData['message'] ?? 'Upload failed',
            'status': response.statusCode,
            'debug': response.body
          };
        } catch (e) {
          return {
            'success': false,
            'message': 'Upload failed with status ${response.statusCode}',
            'status': response.statusCode,
            'debug': response.body.substring(0, Math.min(500, response.body.length))
          };
        }
      }
    } on SocketException catch (e) {
      log('❌ [ERROR] Network error (SocketException): ${e.message}');
      log('💡 [HINT] Check if the server is reachable at: $baseUrl');
      return {
        'success': false,
        'message': 'Network error: ${e.message}',
        'error_type': 'SocketException',
        'debug': 'Network connectivity issue - server may be unreachable'
      };
    } on HandshakeException catch (e) {
      log('❌ [ERROR] SSL/TLS Handshake error: ${e.message}');
      log('💡 [HINT] Check SSL certificate or use http:// instead of https://');
      return {
        'success': false,
        'message': 'SSL error: ${e.message}',
        'error_type': 'HandshakeException',
        'debug': 'SSL/TLS certificate issue'
      };
    } on TimeoutException catch (e) {
      log('❌ [ERROR] Timeout: ${e.message}');
      log('💡 [HINT] Server is taking too long to respond');
      return {
        'success': false,
        'message': 'Request timeout',
        'error_type': 'TimeoutException',
        'debug': 'Server did not respond within 120 seconds'
      };
    } catch (e) {
      log('❌ [ERROR] Unexpected error: $e');
      log('📋 [DEBUG] Error type: ${e.runtimeType}');
      return {
        'success': false,
        'message': 'Unexpected error: $e',
        'error_type': e.runtimeType.toString(),
        'debug': e.toString()
      };
    }
  }
}

// Helper for Math.min since Dart doesn't have it
class Math {
  static int min(int a, int b) => a < b ? a : b;
}
