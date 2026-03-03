import 'dart:io';
import 'package:dio/dio.dart';
import 'package:path_provider/path_provider.dart';
import 'package:permission_handler/permission_handler.dart';
import 'package:flutter/material.dart';
import 'package:open_file/open_file.dart';
import '../l10n/app_localizations.dart';

class DownloadService {
  final Dio _dio = Dio();

  /// Télécharge un fichier et le sauvegarde dans le dossier Downloads
  Future<String> downloadFile({
    required String url,
    required String fileName,
    String? token,
    Function(int, int)? onProgress,
  }) async {
    try {
      print('DEBUG DownloadService: Starting download');
      print('DEBUG DownloadService: URL = $url');
      print('DEBUG DownloadService: fileName = $fileName');
      print('DEBUG DownloadService: token = ${token != null ? "present (${token.substring(0, 20)}...)" : "NULL"}');
      
      // Demander la permission de stockage
      final hasPermission = await _requestStoragePermission();
      if (!hasPermission) {
        print('DEBUG DownloadService: Storage permission denied, using app storage');
      }

      // Obtenir le dossier de téléchargement
      final directory = await _getDownloadDirectory(preferPublic: hasPermission);
      if (directory == null) {
        throw Exception('Impossible d\'accéder au dossier de téléchargement');
      }

      print('DEBUG DownloadService: Download directory = ${directory.path}');
      
      final safeFileName = _sanitizeFileName(fileName);
      // Créer le chemin complet du fichier
      final filePath = '${directory.path}/$safeFileName';
      print('DEBUG DownloadService: Full file path = $filePath');
      
      // Préparer les headers - IMPORTANT: accepter tous les types de fichiers
      final headers = {
        'Accept': '*/*',
        if (token != null) 'Authorization': 'Bearer $token',
      };
      
      print('DEBUG DownloadService: Headers = ${headers.keys.toList()}');
      
      final options = Options(
        headers: headers,
        responseType: ResponseType.bytes,
        followRedirects: true,
        validateStatus: (status) {
          // Accepter tous les codes de succès (200-299)
          return status != null && status >= 200 && status < 300;
        },
      );

      print('DEBUG DownloadService: Starting Dio download...');
      
      // Télécharger le fichier
      final response = await _dio.download(
        url,
        filePath,
        options: options,
        onReceiveProgress: onProgress,
      );

      print('DEBUG DownloadService: Download completed successfully');
      print('DEBUG DownloadService: Response status: ${response.statusCode}');
      print('DEBUG DownloadService: File saved at: $filePath');
      
      // Vérifier que le fichier existe et a une taille > 0
      final file = File(filePath);
      if (!await file.exists()) {
        throw Exception('Le fichier téléchargé n\'existe pas: $filePath');
      }
      
      final fileSize = await file.length();
      print('DEBUG DownloadService: File size: $fileSize bytes');
      
      if (fileSize == 0) {
        await file.delete();
        throw Exception('Le fichier téléchargé est vide (0 bytes)');
      }
      
      return filePath;
    } on DioException catch (e) {
      print('DEBUG DownloadService: Download error: $e');
      print('DEBUG DownloadService: DioException type: ${e.type}');
      print('DEBUG DownloadService: DioException message: ${e.message}');
      print('DEBUG DownloadService: Response status: ${e.response?.statusCode}');
      print('DEBUG DownloadService: Response headers: ${e.response?.headers}');
      
      // Si 404, peut-être que le fichier nécessite un endpoint API spécial
      if (e.response?.statusCode == 404) {
        print('DEBUG DownloadService: Got 404, the file might not exist at this URL');
        print('DEBUG DownloadService: Original URL: $url');
        
        // Informer l'utilisateur de manière claire
        throw Exception('Fichier introuvable sur le serveur (404). L\'URL "$url" ne contient pas de fichier accessible.');
      }
      
      // Tester si c'est une redirection
      final location = e.response?.headers.value('location');
      if (location != null) {
        print('DEBUG DownloadService: Server tried to redirect to: $location');
      }
      
      rethrow;
    } catch (e) {
      print('DEBUG DownloadService: Unexpected error: $e');
      rethrow;
    }
  }

  /// Demande la permission de stockage
  Future<bool> _requestStoragePermission() async {
    if (Platform.isAndroid) {
      // Android 13+ utilise des permissions granulaires
      if (await Permission.storage.isGranted) {
        return true;
      }
      
      final status = await Permission.storage.request();
      if (status.isGranted) {
        return true;
      }

      // Pour Android 13+, essayer les nouvelles permissions
      if (await Permission.manageExternalStorage.isGranted) {
        return true;
      }
      
      final newStatus = await Permission.manageExternalStorage.request();
      return newStatus.isGranted;
    }
    
    return true; // iOS n'a pas besoin de permission pour app documents
  }

  /// Obtient le dossier de téléchargement approprié
  Future<Directory?> _getDownloadDirectory({required bool preferPublic}) async {
    if (Platform.isAndroid) {
      if (preferPublic) {
        // Essayer plusieurs chemins possibles pour le dossier Downloads
        final possiblePaths = [
          '/storage/emulated/0/Download',
          '/storage/emulated/0/Downloads',
          '/sdcard/Download',
          '/sdcard/Downloads',
        ];

        for (final path in possiblePaths) {
          try {
            final directory = Directory(path);
            if (await directory.exists() && await _isDirectoryWritable(directory)) {
              print('DEBUG: Using Downloads directory: $path');
              return directory;
            }
          } catch (e) {
            print('DEBUG: Cannot access $path: $e');
          }
        }
      }

      // Fallback sur le dossier externe de l'application (toujours accessible)
      final externalDir = await getExternalStorageDirectory();
      print('DEBUG: Using fallback directory: ${externalDir?.path}');
      return externalDir;
    } else {
      // iOS - utiliser le dossier documents de l'application
      return await getApplicationDocumentsDirectory();
    }
  }

  Future<bool> _isDirectoryWritable(Directory directory) async {
    try {
      final testFile = File('${directory.path}/.dossy_write_test');
      await testFile.writeAsString('test');
      await testFile.delete();
      return true;
    } catch (_) {
      return false;
    }
  }

  String _sanitizeFileName(String fileName) {
    final trimmed = fileName.trim();
    final lastDot = trimmed.lastIndexOf('.');
    if (lastDot <= 0) return trimmed;

    final ext = trimmed.substring(lastDot + 1).toLowerCase();
    final before = trimmed.substring(0, lastDot);
    final secondDot = before.lastIndexOf('.');
    if (secondDot <= 0) return trimmed;

    final prevExt = before.substring(secondDot + 1).toLowerCase();
    if (prevExt == ext) {
      return before;
    }

    return trimmed;
  }

  /// Montre une notification de téléchargement (optionnel)
  static void showDownloadSnackbar(
    BuildContext context,
    String fileName,
    String filePath,
  ) {
    final l10n = AppLocalizations.of(context)!;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('✓ $fileName ${l10n.fileDownloaded}'),
        backgroundColor: Colors.green,
        duration: const Duration(seconds: 5),
        action: SnackBarAction(
          label: l10n.openFile,
          textColor: Colors.white,
          onPressed: () async {
            try {
              print('DEBUG: Opening file: $filePath');
              final result = await OpenFile.open(filePath);
              print('DEBUG: OpenFile result: ${result.type} - ${result.message}');
              
              if (result.type != ResultType.done) {
                // Afficher un message si l'ouverture a échoué
                if (context.mounted) {
                  final l10n = AppLocalizations.of(context)!;
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(
                      content: Text('${l10n.cannotOpenFile}: ${result.message}'),
                      backgroundColor: Colors.orange,
                    ),
                  );
                }
              }
            } catch (e) {
              print('DEBUG: Error opening file: $e');
              if (context.mounted) {
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: Text('Erreur lors de l\'ouverture: $e'),
                    backgroundColor: Colors.red,
                  ),
                );
              }
            }
          },
        ),
      ),
    );
  }

  /// Affiche un dialog de progression de téléchargement
  static void showDownloadDialog(
    BuildContext context,
    String fileName,
    ValueNotifier<double> progressNotifier,
  ) {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => AlertDialog(
        title: const Text('Téléchargement'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(fileName, style: const TextStyle(fontSize: 12)),
            const SizedBox(height: 16),
            ValueListenableBuilder<double>(
              valueListenable: progressNotifier,
              builder: (context, progress, child) {
                return Column(
                  children: [
                    LinearProgressIndicator(value: progress),
                    const SizedBox(height: 8),
                    Text('${(progress * 100).toStringAsFixed(0)}%'),
                  ],
                );
              },
            ),
          ],
        ),
      ),
    );
  }
}
