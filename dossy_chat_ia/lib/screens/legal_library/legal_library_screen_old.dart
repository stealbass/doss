import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../data/providers/auth_provider.dart';
import '../../providers/legal_library_provider.dart';

class LegalLibraryScreen extends StatefulWidget {
  const LegalLibraryScreen({super.key});

  @override
  State<LegalLibraryScreen> createState() => _LegalLibraryScreenState();
}

class _LegalLibraryScreenState extends State<LegalLibraryScreen> {
  final TextEditingController _searchCtrl = TextEditingController();

  @override
  void initState() {
    super.initState();
    // Charger tous les documents au démarrage
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _loadInitialDocuments();
    });
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    super.dispose();
  }

  Future<void> _loadInitialDocuments() async {
    final auth = context.read<AuthProvider>();
    final provider = context.read<LegalLibraryProvider>();
    final token = auth.token;
    final jurisdiction = auth.user?.jurisdiction ?? 'CM';
    
    if (token != null) {
      // Recherche vide pour charger tous les documents
      await provider.search(
        query: '',
        jurisdiction: jurisdiction,
        token: token,
      );
    }
  }

  Future<void> _runSearch(BuildContext context) async {
    final auth = context.read<AuthProvider>();
    final provider = context.read<LegalLibraryProvider>();
    final token = auth.token;
    final jurisdiction = auth.user?.jurisdiction ?? 'CM';
    if (token == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Veuillez vous connecter.')),
      );
      return;
    }
    await provider.search(
      query: _searchCtrl.text,
      jurisdiction: jurisdiction,
      token: token,
    );
  }

  @override
  Widget build(BuildContext context) {
    final isFr = Localizations.localeOf(context).languageCode == 'fr';

    return Scaffold(
      appBar: AppBar(
        title: Text(isFr ? 'Bibliothèque juridique' : 'Legal Library'),
      ),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            TextField(
              controller: _searchCtrl,
              textInputAction: TextInputAction.search,
              onSubmitted: (_) => _runSearch(context),
              decoration: InputDecoration(
                hintText: isFr ? 'Rechercher un document juridique...' : 'Search legal content...',
                prefixIcon: const Icon(Icons.search),
                suffixIcon: IconButton(
                  icon: const Icon(Icons.clear),
                  onPressed: () {
                    _searchCtrl.clear();
                    context.read<LegalLibraryProvider>().search(
                          query: '',
                          jurisdiction: context.read<AuthProvider>().user?.jurisdiction ?? 'CM',
                          token: context.read<AuthProvider>().token ?? '',
                        );
                  },
                ),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
            ),
            const SizedBox(height: 16),
            Expanded(
              child: Consumer<LegalLibraryProvider>(
                builder: (context, provider, _) {
                  if (provider.isLoading) {
                    return const Center(child: CircularProgressIndicator());
                  }
                  if (provider.error != null) {
                    return Center(child: Text(provider.error!));
                  }
                  if (provider.results.isEmpty) {
                    return Center(
                      child: Text(
                        isFr
                            ? 'Aucun résultat. Essayez une recherche.'
                            : 'No results yet. Try a search.',
                      ),
                    );
                  }
                  return ListView.separated(
                    itemCount: provider.results.length,
                    separatorBuilder: (_, __) => const Divider(height: 1),
                    itemBuilder: (context, index) {
                      final doc = provider.results[index];
                      return ListTile(
                        title: Text(doc.title),
                        subtitle: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            if (doc.summary != null)
                              Padding(
                                padding: const EdgeInsets.only(top: 4.0),
                                child: Text(doc.summary!, maxLines: 2, overflow: TextOverflow.ellipsis),
                              ),
                            if (doc.category != null)
                              Padding(
                                padding: const EdgeInsets.only(top: 4.0),
                                child: Text(doc.category!, style: const TextStyle(color: Colors.grey)),
                              ),
                          ],
                        ),
                        trailing: IconButton(
                          icon: const Icon(Icons.file_download),
                          onPressed: () async {
                            if (doc.fileUrl.isEmpty) {
                              ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(content: Text(isFr ? 'Aucune URL disponible' : 'No URL available')),
                              );
                              return;
                            }
                            final ok = await launchUrl(
                              Uri.parse(doc.fileUrl),
                              mode: LaunchMode.externalApplication,
                            );
                            if (!ok) {
                              ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(content: Text(isFr ? 'Impossible d’ouvrir le lien' : 'Cannot open link')),
                              );
                            }
                          },
                        ),
                        onTap: () async {
                          if (doc.fileUrl.isEmpty) return;
                          await launchUrl(
                            Uri.parse(doc.fileUrl),
                            mode: LaunchMode.externalApplication,
                          );
                        },
                      );
                    },
                  );
                },
              ),
            ),
          ],
        ),
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _runSearch(context),
        icon: const Icon(Icons.search),
        label: Text(isFr ? 'Chercher' : 'Search'),
      ),
    );
  }
}
