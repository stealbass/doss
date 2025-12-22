import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:flutter_pdfview/flutter_pdfview.dart';
import 'package:share_plus/share_plus.dart';
import 'dart:io';
import '../../../data/models/document.dart';
import '../../../data/services/storage_service.dart';
import '../../widgets/documents/document_action_buttons.dart';
import '../../widgets/documents/document_info_sheet.dart';

/// Écran de visualisation de documents PDF
/// Fonctionnalités :
/// - Affichage PDF natif
/// - Navigation par pages
/// - Zoom et recherche
/// - Partage et téléchargement
/// - Gestion offline
class DocumentViewerScreen extends StatefulWidget {
  final Document document;
  final String? localFilePath;

  const DocumentViewerScreen({
    Key? key,
    required this.document,
    this.localFilePath,
  }) : super(key: key);

  @override
  State<DocumentViewerScreen> createState() => _DocumentViewerScreenState();
}

class _DocumentViewerScreenState extends State<DocumentViewerScreen> {
  final StorageService _storageService = StorageService();
  
  PDFViewController? _pdfViewController;
  int _currentPage = 0;
  int _totalPages = 0;
  bool _isReady = false;
  bool _isLoading = true;
  bool _isFavorite = false;
  String? _errorMessage;
  String? _localPath;

  @override
  void initState() {
    super.initState();
    _initializeDocument();
  }

  /// Initialiser le document
  Future<void> _initializeDocument() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      // Vérifier si le document est en favoris
      final favorites = await _storageService.getFavorites();
      setState(() {
        _isFavorite = favorites.contains(widget.document.id);
      });

      // Utiliser le chemin local si disponible
      if (widget.localFilePath != null && File(widget.localFilePath!).existsSync()) {
        setState(() {
          _localPath = widget.localFilePath;
          _isLoading = false;
        });
        return;
      }

      // Vérifier le cache
      final cached = await _storageService.getCachedData('document_${widget.document.id}');
      if (cached != null) {
        setState(() {
          _localPath = cached;
          _isLoading = false;
        });
        return;
      }

      // TODO: Télécharger le document depuis l'API
      // Pour le moment, afficher une erreur
      setState(() {
        _errorMessage = 'Le document doit être téléchargé';
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _errorMessage = 'Erreur lors du chargement: $e';
        _isLoading = false;
      });
    }
  }

  /// Basculer les favoris
  Future<void> _toggleFavorite() async {
    try {
      final favorites = await _storageService.getFavorites();
      if (_isFavorite) {
        favorites.remove(widget.document.id.toString());
      } else {
        favorites.add(widget.document.id.toString());
      }
      await _storageService.saveFavorites(favorites);
      
      setState(() {
        _isFavorite = !_isFavorite;
      });

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(_isFavorite ? 'Ajouté aux favoris' : 'Retiré des favoris'),
          duration: const Duration(seconds: 2),
        ),
      );
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Erreur: $e'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  /// Partager le document
  Future<void> _shareDocument() async {
    if (_localPath == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Le document doit être téléchargé pour être partagé'),
          backgroundColor: Colors.orange,
        ),
      );
      return;
    }

    try {
      await Share.shareXFiles(
        [XFile(_localPath!)],
        text: widget.document.title,
        subject: 'Document juridique - ${widget.document.title}',
      );
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Erreur de partage: $e'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  /// Afficher les informations du document
  void _showDocumentInfo() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => DocumentInfoSheet(document: widget.document),
    );
  }

  /// Aller à une page spécifique
  Future<void> _goToPage() async {
    final pageController = TextEditingController(text: '${_currentPage + 1}');
    
    final result = await showDialog<int>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Aller à la page'),
        content: TextField(
          controller: pageController,
          keyboardType: TextInputType.number,
          decoration: InputDecoration(
            labelText: 'Numéro de page (1-$_totalPages)',
            border: const OutlineInputBorder(),
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: const Text('Annuler'),
          ),
          TextButton(
            onPressed: () {
              final page = int.tryParse(pageController.text);
              if (page != null && page > 0 && page <= _totalPages) {
                Navigator.of(context).pop(page - 1);
              }
            },
            child: const Text('OK'),
          ),
        ],
      ),
    );

    if (result != null && _pdfViewController != null) {
      await _pdfViewController!.setPage(result);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              widget.document.title,
              style: TextStyle(fontSize: 16.sp),
              overflow: TextOverflow.ellipsis,
            ),
            if (_isReady)
              Text(
                'Page ${_currentPage + 1}/$_totalPages',
                style: TextStyle(
                  fontSize: 12.sp,
                  color: Colors.white70,
                ),
              ),
          ],
        ),
        actions: [
          IconButton(
            icon: Icon(_isFavorite ? Icons.favorite : Icons.favorite_border),
            onPressed: _toggleFavorite,
            tooltip: _isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris',
          ),
          IconButton(
            icon: const Icon(Icons.share),
            onPressed: _shareDocument,
            tooltip: 'Partager',
          ),
          IconButton(
            icon: const Icon(Icons.info_outline),
            onPressed: _showDocumentInfo,
            tooltip: 'Informations',
          ),
        ],
      ),
      body: _buildBody(),
      bottomNavigationBar: _isReady ? _buildNavigationBar() : null,
    );
  }

  /// Construire le corps
  Widget _buildBody() {
    if (_isLoading) {
      return const Center(
        child: CircularProgressIndicator(),
      );
    }

    if (_errorMessage != null) {
      return Center(
        child: Padding(
          padding: EdgeInsets.all(24.w),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(
                Icons.error_outline,
                size: 64.sp,
                color: Colors.red,
              ),
              SizedBox(height: 16.h),
              Text(
                _errorMessage!,
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 16.sp),
              ),
              SizedBox(height: 24.h),
              ElevatedButton.icon(
                onPressed: _initializeDocument,
                icon: const Icon(Icons.refresh),
                label: const Text('Réessayer'),
              ),
            ],
          ),
        ),
      );
    }

    if (_localPath == null) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              Icons.download,
              size: 64.sp,
              color: Colors.grey,
            ),
            SizedBox(height: 16.h),
            Text(
              'Ce document doit être téléchargé',
              style: TextStyle(fontSize: 16.sp),
            ),
            SizedBox(height: 24.h),
            ElevatedButton.icon(
              onPressed: () {
                // TODO: Implémenter le téléchargement
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(content: Text('Téléchargement à implémenter')),
                );
              },
              icon: const Icon(Icons.download),
              label: const Text('Télécharger'),
            ),
          ],
        ),
      );
    }

    return PDFView(
      filePath: _localPath!,
      enableSwipe: true,
      swipeHorizontal: false,
      autoSpacing: true,
      pageFling: true,
      pageSnap: true,
      defaultPage: _currentPage,
      fitPolicy: FitPolicy.WIDTH,
      preventLinkNavigation: false,
      onRender: (pages) {
        setState(() {
          _totalPages = pages ?? 0;
          _isReady = true;
        });
      },
      onError: (error) {
        setState(() {
          _errorMessage = error.toString();
          _isReady = false;
        });
      },
      onPageError: (page, error) {
        setState(() {
          _errorMessage = 'Erreur page $page: $error';
        });
      },
      onViewCreated: (PDFViewController pdfViewController) {
        setState(() {
          _pdfViewController = pdfViewController;
        });
      },
      onLinkHandler: (String? uri) {
        debugPrint('Lien cliqué: $uri');
      },
      onPageChanged: (int? page, int? total) {
        setState(() {
          _currentPage = page ?? 0;
        });
      },
    );
  }

  /// Barre de navigation
  Widget _buildNavigationBar() {
    return Container(
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.1),
            blurRadius: 4,
            offset: const Offset(0, -2),
          ),
        ],
      ),
      child: SafeArea(
        child: Padding(
          padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 8.h),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              // Page précédente
              IconButton(
                icon: const Icon(Icons.chevron_left),
                onPressed: _currentPage > 0
                    ? () async {
                        await _pdfViewController?.setPage(_currentPage - 1);
                      }
                    : null,
                tooltip: 'Page précédente',
              ),

              // Indicateur de page
              InkWell(
                onTap: _goToPage,
                child: Container(
                  padding: EdgeInsets.symmetric(
                    horizontal: 16.w,
                    vertical: 8.h,
                  ),
                  decoration: BoxDecoration(
                    color: Theme.of(context).primaryColor.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(20.r),
                  ),
                  child: Text(
                    '${_currentPage + 1} / $_totalPages',
                    style: TextStyle(
                      fontSize: 14.sp,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
              ),

              // Page suivante
              IconButton(
                icon: const Icon(Icons.chevron_right),
                onPressed: _currentPage < _totalPages - 1
                    ? () async {
                        await _pdfViewController?.setPage(_currentPage + 1);
                      }
                    : null,
                tooltip: 'Page suivante',
              ),
            ],
          ),
        ),
      ),
    );
  }
}
