import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/constants/app_constants.dart';
import '../../../l10n/app_localizations.dart';
import '../../../data/providers/auth_provider.dart';
import '../../../data/providers/chat_provider.dart';
import '../../../data/providers/document_provider.dart';
import '../../../data/services/storage_service.dart';
import '../../widgets/chat/chat_bubble.dart';
import '../../widgets/chat/prompt_suggestion_chip.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_colors.dart';
import '../../../l10n/app_localizations.dart';
import '../../../data/providers/auth_provider.dart';
import '../../../data/providers/chat_provider.dart';
import '../../../data/providers/document_provider.dart';
import '../../widgets/chat/chat_bubble.dart';
import 'conversations_list_screen.dart';

class ChatScreen extends StatefulWidget {
  const ChatScreen({super.key});

  @override
  State<ChatScreen> createState() => _ChatScreenState();
}

class _ChatScreenState extends State<ChatScreen> {
  final TextEditingController _messageController = TextEditingController();
  final ScrollController _scrollController = ScrollController();
  final Set<int> _selectedDocumentIds = {};
  bool _showDocumentSelector = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _scrollToBottom());
  }

  @override
  void dispose() {
    _messageController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  void _scrollToBottom() {
    if (!_scrollController.hasClients) return;
    _scrollController.animateTo(
      _scrollController.position.maxScrollExtent,
      duration: const Duration(milliseconds: 300),
      curve: Curves.easeOut,
    );
  }

  Future<void> _sendMessage() async {
    final message = _messageController.text.trim();
    if (message.isEmpty) return;

    _messageController.clear();

    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final chatProvider = Provider.of<ChatProvider>(context, listen: false);
    final l10n = AppLocalizations.of(context)!;

    if (authProvider.token == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(l10n.pleaseLogin),
          backgroundColor: AppColors.error,
        ),
      );
      return;
    }

    if (authProvider.needsProfileCompletion) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(l10n.completeProfileMessage),
          backgroundColor: AppColors.warning,
        ),
      );
      if (!mounted) return;
      Navigator.pushNamed(context, '/profile');
      return;
    }

    final user = authProvider.user;
    if (user != null && !user.canAnalyze) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(l10n.analysisQuotaExhausted),
          backgroundColor: AppColors.warning,
        ),
      );
      return;
    }

    final documentIds =
        _selectedDocumentIds.isEmpty ? null : _selectedDocumentIds.toList();
    final hasSelectedDocs = documentIds != null && documentIds.isNotEmpty;

    await chatProvider.sendMessage(
      message: message,
      token: authProvider.token!,
      useSimpleRag: !hasSelectedDocs,
      useAdvancedRag: true,
      documentIds: documentIds,
      enableAnonymization: true,
    );

    if (chatProvider.error != null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(chatProvider.error!),
          backgroundColor: AppColors.error,
        ),
      );
    }

    _scrollToBottom();
    await authProvider.refreshUser();
  }

  Widget _buildProfileGate(AppLocalizations l10n, AuthProvider authProvider) {
    final missingFields = authProvider.missingProfileFields;

    String missingLabel(String field) {
      if (field == 'jurisdiction') return l10n.jurisdiction;
      if (field == 'mobile_role') return l10n.role;
      return field;
    }

    final missingText =
        missingFields.isNotEmpty ? missingFields.map(missingLabel).join(', ') : '';

    return Center(
      child: Padding(
        padding: EdgeInsets.all(24.w),
        child: Card(
          elevation: 2,
          child: Padding(
            padding: EdgeInsets.all(20.w),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(
                  Icons.lock_outline,
                  size: 48.sp,
                  color: AppColors.warning,
                ),
                SizedBox(height: 12.h),
                Text(
                  l10n.completeProfileTitle,
                  style: TextStyle(
                    fontSize: 18.sp,
                    fontWeight: FontWeight.w700,
                  ),
                  textAlign: TextAlign.center,
                ),
                SizedBox(height: 8.h),
                Text(
                  missingText.isEmpty
                      ? l10n.completeProfileMessage
                      : '${l10n.completeProfileMessage}\n\n${l10n.missingFieldsLabel}: $missingText',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 13.sp,
                    color: AppColors.textSecondary,
                  ),
                ),
                SizedBox(height: 16.h),
                ElevatedButton(
                  onPressed: () {
                    Navigator.pushNamed(context, '/profile');
                  },
                  child: Text(l10n.completeNow),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildStatusBar(AppLocalizations l10n, AuthProvider authProvider) {
    final user = authProvider.user;
    if (user == null) return const SizedBox.shrink();

    return Container(
      padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 8.h),
      color: AppColors.primary.withAlpha((0.1 * 255).round()),
      child: Row(
        children: [
          Icon(
            Icons.analytics_outlined,
            size: 16.sp,
            color: AppColors.primary,
          ),
          SizedBox(width: 8.w),
          Text(
            '${l10n.analyses}: ${user.analysesUsed}/${user.analysesLimit == -1 ? '∞' : user.analysesLimit}',
            style: TextStyle(
              fontSize: 12.sp,
              color: AppColors.textSecondary,
            ),
          ),
          const Spacer(),
          if (user.analysesLimit != -1 && user.plan.isNotEmpty)
            Container(
              padding: EdgeInsets.symmetric(horizontal: 8.w, vertical: 4.h),
              decoration: BoxDecoration(
                color: AppColors.getPlanColor(user.plan)
                    .withAlpha((0.2 * 255).round()),
                borderRadius: BorderRadius.circular(12.r),
              ),
              child: Text(
                user.plan,
                style: TextStyle(
                  fontSize: 11.sp,
                  fontWeight: FontWeight.w600,
                  color: AppColors.getPlanColor(user.plan),
                ),
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildMessageList() {
    return Consumer<ChatProvider>(
      builder: (context, chatProvider, child) {
        if (chatProvider.messages.isEmpty) {
          return Center(
            child: Text(
              'Aucun message pour le moment',
              style: TextStyle(color: AppColors.textSecondary, fontSize: 12.sp),
            ),
          );
        }
        return ListView.builder(
          controller: _scrollController,
          padding: EdgeInsets.all(16.w),
          itemCount: chatProvider.messages.length,
          itemBuilder: (context, index) {
            final message = chatProvider.messages[index];
            return ChatBubble(message: message);
          },
        );
      },
    );
  }

  Widget _buildTypingIndicator(AppLocalizations l10n) {
    return Consumer<ChatProvider>(
      builder: (context, chatProvider, child) {
        if (!chatProvider.isTyping) return const SizedBox.shrink();
        return Padding(
          padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 8.h),
          child: Row(
            children: [
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
              Text(
                l10n.dossyIsTyping,
                style: TextStyle(
                  fontSize: 12.sp,
                  color: AppColors.textSecondary,
                  fontStyle: FontStyle.italic,
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  Widget _buildSelectedDocs() {
    if (_selectedDocumentIds.isEmpty) return const SizedBox.shrink();

    return Consumer<DocumentProvider>(
      builder: (context, docProvider, child) {
        final selectedDocs = docProvider.documents
            .where((doc) => _selectedDocumentIds.contains(doc.id))
            .toList();

        return Container(
          margin: EdgeInsets.only(bottom: 8.h),
          padding: EdgeInsets.symmetric(horizontal: 8.w, vertical: 4.h),
          decoration: BoxDecoration(
            color: AppColors.primary.withAlpha((0.1 * 255).round()),
            borderRadius: BorderRadius.circular(8.r),
          ),
          child: Wrap(
            spacing: 4.w,
            children: [
              ...selectedDocs.map(
                (doc) => Chip(
                  label: Text(
                    doc.name.length > 20
                        ? '${doc.name.substring(0, 20)}...'
                        : doc.name,
                    style: TextStyle(fontSize: 11.sp),
                  ),
                  onDeleted: () {
                    setState(() {
                      _selectedDocumentIds.remove(doc.id);
                    });
                  },
                  deleteIcon: Icon(Icons.close, size: 14.sp),
                  backgroundColor: Colors.white,
                  side: const BorderSide(color: AppColors.primary),
                ),
              ),
              if (_selectedDocumentIds.length < docProvider.documents.length)
                ActionChip(
                  label: Text(
                    '+${docProvider.documents.length - _selectedDocumentIds.length}',
                    style: TextStyle(fontSize: 11.sp, color: AppColors.primary),
                  ),
                  onPressed: () {
                    setState(() {
                      _showDocumentSelector = !_showDocumentSelector;
                    });
                  },
                  backgroundColor: Colors.white,
                  side: const BorderSide(color: AppColors.primary),
                ),
            ],
          ),
        );
      },
    );
  }

  Widget _buildDocumentSelector() {
    if (!_showDocumentSelector) return const SizedBox.shrink();

    return Consumer<DocumentProvider>(
      builder: (context, docProvider, child) {
        if (docProvider.documents.isEmpty) {
          return Padding(
            padding: EdgeInsets.only(bottom: 8.h),
            child: Text(
              'Aucun document disponible',
              style: TextStyle(fontSize: 12.sp, color: Colors.grey),
            ),
          );
        }
        return Container(
          margin: EdgeInsets.only(bottom: 8.h),
          constraints: BoxConstraints(maxHeight: 150.h),
          child: ListView(
            shrinkWrap: true,
            children: docProvider.documents.map((doc) {
              final isSelected = _selectedDocumentIds.contains(doc.id);
              return CheckboxListTile(
                dense: true,
                contentPadding: EdgeInsets.symmetric(horizontal: 4.w),
                title: Text(
                  doc.name,
                  style: TextStyle(fontSize: 12.sp),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                subtitle: Text(
                  doc.fileSizeFormatted,
                  style: TextStyle(fontSize: 10.sp, color: Colors.grey),
                ),
                value: isSelected,
                onChanged: (selected) {
                  setState(() {
                    if (selected == true) {
                      _selectedDocumentIds.add(doc.id);
                    } else {
                      _selectedDocumentIds.remove(doc.id);
                    }
                  });
                },
              );
            }).toList(),
          ),
        );
      },
    );
  }

  Widget _buildInputArea(AppLocalizations l10n) {
    return Container(
      padding: EdgeInsets.symmetric(horizontal: 12.w, vertical: 6.h),
      decoration: BoxDecoration(
        color: Colors.white,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withAlpha((0.05 * 255).round()),
            blurRadius: 10,
            offset: const Offset(0, -2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: [
          _buildSelectedDocs(),
          _buildDocumentSelector(),
          Row(
            children: [
              Expanded(
                child: TextField(
                  controller: _messageController,
                  maxLines: 3,
                  minLines: 1,
                  keyboardType: TextInputType.multiline,
                  textInputAction: TextInputAction.newline,
                  onSubmitted: (value) {
                    if (value.isNotEmpty) _sendMessage();
                  },
                  decoration: InputDecoration(
                    hintText: l10n.askLegalQuestion,
                    prefixIcon: Consumer<DocumentProvider>(
                      builder: (context, docProvider, child) {
                        final hasDocuments = docProvider.documents.isNotEmpty;
                        return IconButton(
                          icon: Icon(
                            Icons.attach_file,
                            color: _selectedDocumentIds.isNotEmpty
                                ? AppColors.primary
                                : (hasDocuments
                                    ? Colors.grey
                                    : Colors.grey.shade300),
                            size: 20.sp,
                          ),
                          onPressed: hasDocuments
                              ? () {
                                  setState(() {
                                    _showDocumentSelector = !_showDocumentSelector;
                                  });
                                }
                              : null,
                          tooltip: 'Selectionner des documents',
                        );
                      },
                    ),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(24.r),
                      borderSide: BorderSide.none,
                    ),
                    filled: true,
                    fillColor: AppColors.inputBackground,
                    contentPadding: EdgeInsets.symmetric(
                      horizontal: 12.w,
                      vertical: 8.h,
                    ),
                  ),
                ),
              ),
              SizedBox(width: 4.w),
              Consumer<ChatProvider>(
                builder: (context, chatProvider, child) {
                  return CircleAvatar(
                    radius: 20.r,
                    backgroundColor: AppColors.primary,
                    child: IconButton(
                      icon: Icon(
                        chatProvider.isLoading
                            ? Icons.hourglass_empty
                            : Icons.send,
                        color: Colors.white,
                        size: 18.sp,
                      ),
                      onPressed: chatProvider.isLoading ? null : _sendMessage,
                    ),
                  );
                },
              ),
            ],
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;

    return Scaffold(
      resizeToAvoidBottomInset: false,
      appBar: AppBar(
        title: Text(l10n.navChat),
        actions: [
          IconButton(
            icon: const Icon(Icons.add_comment_outlined),
            tooltip: 'Nouveau chat',
            onPressed: () {
              Provider.of<ChatProvider>(context, listen: false).clearChat();
              _messageController.clear();
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(
                  content: Text('Nouveau chat cree'),
                  duration: Duration(seconds: 2),
                ),
              );
            },
          ),
          IconButton(
            icon: const Icon(Icons.history),
            tooltip: l10n.chatHistory,
            onPressed: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (context) => const ConversationsListScreen(),
                ),
              );
            },
          ),
          IconButton(
            icon: const Icon(Icons.delete_outline),
            tooltip: l10n.deleteConversation,
            onPressed: () {
              Provider.of<ChatProvider>(context, listen: false).clearChat();
            },
          ),
        ],
      ),
      body: SafeArea(
        bottom: false,
        child: Consumer<AuthProvider>(
          builder: (context, authProvider, child) {
            if (authProvider.needsProfileCompletion) {
              return _buildProfileGate(l10n, authProvider);
            }

            return Column(
              children: [
                _buildStatusBar(l10n, authProvider),
                Expanded(
                  child: Column(
                    children: [
                      Expanded(child: _buildMessageList()),
                      _buildTypingIndicator(l10n),
                    ],
                  ),
                ),
                _buildInputArea(l10n),
              ],
            );
          },
        ),
      ),
    );
  }
}
