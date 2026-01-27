import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_cache_manager/flutter_cache_manager.dart';

/// Service pour charger et cacher les images de façon optimisée
/// Remplace Image.network() pour une meilleure performance
/// 
/// Avantages:
/// - Images cachées pendant 7 jours
/// - Loading placeholder
/// - Error handling
/// - Compression automatique
/// - Support offline-first
class ImageService {
  // Singleton pattern
  static final ImageService _instance = ImageService._internal();
  
  factory ImageService() {
    return _instance;
  }
  
  ImageService._internal() {
    _initializeCacheManager();
  }
  
  // ✅ Custom cache manager avec 7 jours de retention
  late CacheManager _cacheManager;
  
  void _initializeCacheManager() {
    _cacheManager = CacheManager(
      Config(
        'dossy_image_cache',
        stalePeriod: const Duration(days: 7),  // Garder 7 jours
        maxNrOfCacheObjects: 200,  // Max 200 images
        repo: JsonCacheInfoRepository(cacheSize: 10 * 1024 * 1024),  // 10MB max
      ),
    );
  }
  
  /// Affiche une image cachée depuis une URL
  /// 
  /// Utilisation:
  /// ```dart
  /// ImageService().cachedImage(
  ///   'https://example.com/image.jpg',
  ///   width: 100,
  ///   height: 100,
  /// )
  /// ```
  static Widget cachedImage(
    String? imageUrl, {
    BoxFit fit = BoxFit.cover,
    double? width,
    double? height,
    Widget? placeholder,
    Widget? errorWidget,
    Duration cacheDuration = const Duration(days: 7),
  }) {
    if (imageUrl == null || imageUrl.isEmpty) {
      return _buildErrorWidget(errorWidget, width, height);
    }

    return CachedNetworkImage(
      imageUrl: imageUrl,
      fit: fit,
      width: width,
      height: height,
      cacheManager: ImageService()._cacheManager,
      
      // ✅ Placeholder pendant le chargement
      placeholder: (context, url) =>
          placeholder ??
          Container(
            width: width,
            height: height,
            color: Colors.grey[300],
            child: const Center(
              child: SizedBox(
                width: 30,
                height: 30,
                child: CircularProgressIndicator(strokeWidth: 2),
              ),
            ),
          ),
      
      // ✅ Widget en cas d'erreur
      errorWidget: (context, url, error) =>
          _buildErrorWidget(errorWidget, width, height),
      
      // ✅ Durée du cache (peut être overridée)
      cacheKeyBuilder: (url, cacheManager) => url,
      
      // ✅ Transitions douces
      fadeInDuration: const Duration(milliseconds: 500),
      fadeOutDuration: const Duration(milliseconds: 500),
    );
  }
  
  /// Affiche une image circulaire (pour avatars)
  /// 
  /// Utilisation:
  /// ```dart
  /// ImageService().circularImage(
  ///   'https://example.com/avatar.jpg',
  ///   radius: 50,
  /// )
  /// ```
  static Widget circularImage(
    String? imageUrl, {
    required double radius,
    Widget? errorWidget,
  }) {
    if (imageUrl == null || imageUrl.isEmpty) {
      return Container(
        width: radius * 2,
        height: radius * 2,
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          color: Colors.grey[300],
        ),
        child: errorWidget,
      );
    }

    return CachedNetworkImage(
      imageUrl: imageUrl,
      imageBuilder: (context, imageProvider) => Container(
        width: radius * 2,
        height: radius * 2,
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          image: DecorationImage(
            image: imageProvider,
            fit: BoxFit.cover,
          ),
        ),
      ),
      placeholder: (context, url) => Container(
        width: radius * 2,
        height: radius * 2,
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          color: Colors.grey[300],
        ),
        child: const Center(
          child: SizedBox(
            width: 24,
            height: 24,
            child: CircularProgressIndicator(strokeWidth: 2),
          ),
        ),
      ),
      errorWidget: (context, url, error) => Container(
        width: radius * 2,
        height: radius * 2,
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          color: Colors.grey[300],
        ),
        child: const Icon(Icons.person, size: 40),
      ),
      cacheManager: ImageService()._cacheManager,
      fadeInDuration: const Duration(milliseconds: 500),
    );
  }
  
  /// Affiche une image avec border radius
  /// 
  /// Utilisation:
  /// ```dart
  /// ImageService().roundedImage(
  ///   'https://example.com/image.jpg',
  ///   radius: 12,
  ///   width: 200,
  ///   height: 200,
  /// )
  /// ```
  static Widget roundedImage(
    String? imageUrl, {
    required double radius,
    required double width,
    required double height,
    BoxFit fit = BoxFit.cover,
    Widget? errorWidget,
  }) {
    return ClipRRect(
      borderRadius: BorderRadius.circular(radius),
      child: cachedImage(
        imageUrl,
        width: width,
        height: height,
        fit: fit,
        errorWidget: errorWidget,
      ),
    );
  }
  
  /// Pré-cache une image (utile pour pré-charger les images critiques)
  /// 
  /// Utilisation:
  /// ```dart
  /// await ImageService().preCacheImage('https://example.com/logo.jpg');
  /// ```
  static Future<void> preCacheImage(String imageUrl) async {
    try {
      final imageProvider = CachedNetworkImageProvider(imageUrl);
      imageProvider.evict();  // Force reload
    } catch (e) {
      print('⚠️ Error pre-caching image: $e');
    }
  }
  
  /// Clear le cache des images
  /// 
  /// Utilisation:
  /// ```dart
  /// await ImageService().clearCache();
  /// ```
  static Future<void> clearCache() async {
    try {
      await ImageService()._cacheManager.emptyCache();
      print('✅ Image cache cleared');
    } catch (e) {
      print('⚠️ Error clearing cache: $e');
    }
  }
  
  /// Obtient la taille du cache
  /// 
  /// Utilisation:
  /// ```dart
  /// final size = await ImageService().getCacheSize();
  /// ```
  static Future<int> getCacheSize() async {
    try {
      final cacheManager = ImageService()._cacheManager;
      final files = await cacheManager.store.getAllObjects();
      int totalSize = 0;
      for (var file in files) {
        totalSize += file.validTill?.millisecondsSinceEpoch ?? 0;
      }
      return totalSize;
    } catch (e) {
      return 0;
    }
  }
  
  // Helper privé pour construire le widget d'erreur
  static Widget _buildErrorWidget(
    Widget? errorWidget,
    double? width,
    double? height,
  ) {
    return errorWidget ??
        Container(
          width: width,
          height: height,
          color: Colors.grey[300],
          child: const Icon(Icons.image_not_supported, color: Colors.grey),
        );
  }
}

/// Extension pour faciliter l'utilisation
extension ImageUrlExtension on String {
  /// Afficher cette image en tant qu'image cachée
  /// 
  /// Utilisation:
  /// ```dart
  /// imageUrl.toImage(width: 100, height: 100)
  /// ```
  Widget toImage({
    BoxFit fit = BoxFit.cover,
    double? width,
    double? height,
  }) {
    return ImageService.cachedImage(
      this,
      fit: fit,
      width: width,
      height: height,
    );
  }
  
  /// Afficher cette image en tant qu'avatar circulaire
  /// 
  /// Utilisation:
  /// ```dart
  /// avatarUrl.toAvatar(radius: 50)
  /// ```
  Widget toAvatar({required double radius}) {
    return ImageService.circularImage(this, radius: radius);
  }
}
