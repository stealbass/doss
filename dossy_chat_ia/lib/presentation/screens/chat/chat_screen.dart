import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/constants/app_constants.dart';
import '../../../data/providers/auth_provider.dart';
import '../../../data/providers/chat_provider.dart';
import '../../../data/models/message_model.dart';
import '../../widgets/chat/chat_bubble.dart';
import '../../widgets/chat/prompt_suggestion_chip.dart';

class ChatScreen extends StatefulWidget {
  const ChatScreen({super.key});

  @override
  State<ChatScreen> createState() => _ChatScreenState();
}

class _ChatScreenState extends State<ChatScreen> {
  final TextEditingController _messageController = TextEditingController();
  final ScrollController _scrollController = ScrollController();
  bool _useSimpleRag = true;
  bool _useAdvancedRag = false;
  bool _enableAnonymization = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _scrollToBottom();
    });
  }

  @override
  void dispose() {
    _messageController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  void _scrollToBottom() {
    if (_scrollController.hasClients) {
      _scrollController.animateTo(
        _scrollController.position.maxScrollExtent,
        duration: const Duration(milliseconds: 300),
        curve: Curves.easeOut,
      );
    }
  }

  Future<void> _sendMessage() async {
    final message = _messageController.text.trim();
    if (message.isEmpty) return;

    _messageController.clear();

    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final chatProvider = Provider.of<ChatProvider>(context, listen: false);

    if (authProvider.token == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Veuillez vous connecter'),
          backgroundColor: AppColors.error,
        ),
      );
      return;
    }

    // Check quota
    if (!authProvider.user!.canAnalyze) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Quota d\'analyses épuisé. Veuillez souscrire à un plan.'),
          backgroundColor: AppColors.warning,
        ),
      );
      return;
    }

    await chatProvider.sendMessage(
      message: message,
      token: authProvider.token!,
      useSimpleRag: _useSimpleRag,
      useAdvancedRag: _useAdvancedRag,
      enableAnonymization: _enableAnonymization,
    );

    _scrollToBottom();

    // Refresh user data to update quotas
    await authProvider.refreshUser();
  }

  void _showRagOptions() {
    showModalBottomSheet(
      context: context,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20.r)),
      ),
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setModalState) {
            return Padding(
              padding: EdgeInsets.all(24.w),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Options de recherche',
                    style: TextStyle(
                      fontSize: 18.sp,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  SizedBox(height: 16.h),
                  
                  // Simple RAG
                  SwitchListTile(
                    title: const Text('Recherche dans la bibliothèque juridique'),
                    subtitle: const Text('Utilise les textes de loi et jurisprudence'),
                    value: _useSimpleRag,
                    activeColor: AppColors.primary,
                    onChanged: (value) {
                      setModalState(() {
                        _useSimpleRag = value;
                      });
                      setState(() {
                        _useSimpleRag = value;
                      });
                    },
                  ),
                  
                  // Advanced RAG
                  SwitchListTile(
                    title: const Text('Recherche dans mes documents'),
                    subtitle: const Text('Utilise vos documents uploadés'),
                    value: _useAdvancedRag,
                    activeColor: AppColors.primary,
                    onChanged: (value) {
                      setModalState(() {
                        _useAdvancedRag = value;
                      });
                      setState(() {
                        _useAdvancedRag = value;
                      });
                    },
                  ),
                  
                  // Anonymization
                  Consumer<AuthProvider>(
                    builder: (context, authProvider, child) {
                      final hasAnonymization = authProvider.user?.hasAnonymization ?? false;
                      return SwitchListTile(
                        title: const Text('Anonymisation automatique'),
                        subtitle: Text(
                          hasAnonymization
                              ? 'Remplace les noms par [X], [Y]'
                              : 'Disponible avec le plan Professionnel',
                        ),
                        value: _enableAnonymization && hasAnonymization,
                        activeColor: AppColors.primary,
                        onChanged: hasAnonymization
                            ? (value) {
                                setModalState(() {
                                  _enableAnonymization = value;
                                });
                                setState(() {
                                  _enableAnonymization = value;
                                });
                              }
                            : null,
                      );
                    },
                  ),
                  
                  SizedBox(height: 16.h),
                  
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: () => Navigator.pop(context),
                      child: const Text('Fermer'),
                    ),
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Chat IA'),
        actions: [
          IconButton(
            icon: const Icon(Icons.settings),
            onPressed: _showRagOptions,
          ),
          IconButton(
            icon: const Icon(Icons.delete_outline),
            onPressed: () {
              Provider.of<ChatProvider>(context, listen: false).clearChat();
            },
          ),
        ],
      ),
      body: Column(
        children: [
          // Status Bar
          Consumer<AuthProvider>(
            builder: (context, authProvider, child) {
              final user = authProvider.user;
              if (user == null) return const SizedBox.shrink();
              
              return Container(
                padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 8.h),
                color: AppColors.primary.withOpacity(0.1),
                child: Row(
                  children: [
                    Icon(
                      Icons.analytics_outlined,
                      size: 16.sp,
                      color: AppColors.primary,
                    ),
                    SizedBox(width: 8.w),
                    Text(
                      'Analyses: ${user.analysesUsed}/${user.analysesLimit == -1 ? '∞' : user.analysesLimit}',
                      style: TextStyle(
                        fontSize: 12.sp,
                        color: AppColors.textSecondary,
                      ),
                    ),
                    const Spacer(),
                    Container(
                      padding: EdgeInsets.symmetric(horizontal: 8.w, vertical: 4.h),
                      decoration: BoxDecoration(
                        color: AppColors.getPlanColor(user.plan).withOpacity(0.2),
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
            },
          ),
          
          // Prompt Suggestions
          Consumer<ChatProvider>(
            builder: (context, chatProvider, child) {
              if (chatProvider.messages.isNotEmpty) return const SizedBox.shrink();
              
              return Container(
                padding: EdgeInsets.all(16.w),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Suggestions',
                      style: TextStyle(
                        fontSize: 14.sp,
                        fontWeight: FontWeight.w600,
                        color: AppColors.textSecondary,
                      ),
                    ),
                    SizedBox(height: 8.h),
                    Wrap(
                      spacing: 8.w,
                      runSpacing: 8.h,
                      children: AppConstants.chatPromptSuggestions.map((prompt) {
                        return PromptSuggestionChip(
                          prompt: prompt,
                          onTap: () {
                            _messageController.text = prompt;
                          },
                        );
                      }).toList(),
                    ),
                  ],
                ),
              );
            },
          ),
          
          // Messages List
          Expanded(
            child: Consumer<ChatProvider>(
              builder: (context, chatProvider, child) {
                if (chatProvider.messages.isEmpty) {
                  return Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(
                          Icons.chat_bubble_outline,
                          size: 80.sp,
                          color: AppColors.textHint,
                        ),
                        SizedBox(height: 16.h),
                        Text(
                          'Commencez une conversation',
                          style: TextStyle(
                            fontSize: 16.sp,
                            color: AppColors.textSecondary,
                          ),
                        ),
                      ],
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
            ),
          ),
          
          // Typing Indicator
          Consumer<ChatProvider>(
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
                      'DOSSY IA est en train d\'écrire...',
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
          ),
          
          // Verification Message
          Container(
            padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 4.h),
            color: AppColors.warning.withOpacity(0.1),
            child: Row(
              children: [
                Icon(
                  Icons.info_outline,
                  size: 14.sp,
                  color: AppColors.warning,
                ),
                SizedBox(width: 8.w),
                Expanded(
                  child: Text(
                    'Vérifiez toujours les informations juridiques',
                    style: TextStyle(
                      fontSize: 11.sp,
                      color: AppColors.textSecondary,
                    ),
                  ),
                ),
              ],
            ),
          ),
          
          // Input Bar
          Container(
            padding: EdgeInsets.all(16.w),
            decoration: BoxDecoration(
              color: Colors.white,
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.05),
                  blurRadius: 10,
                  offset: const Offset(0, -2),
                ),
              ],
            ),
            child: SafeArea(
              child: Row(
                children: [
                  Expanded(
                    child: TextField(
                      controller: _messageController,
                      maxLines: null,
                      decoration: InputDecoration(
                        hintText: 'Posez votre question juridique...',
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(24.r),
                          borderSide: BorderSide.none,
                        ),
                        filled: true,
                        fillColor: AppColors.inputBackground,
                        contentPadding: EdgeInsets.symmetric(
                          horizontal: 16.w,
                          vertical: 12.h,
                        ),
                      ),
                    ),
                  ),
                  SizedBox(width: 8.w),
                  Consumer<ChatProvider>(
                    builder: (context, chatProvider, child) {
                      return CircleAvatar(
                        radius: 24.r,
                        backgroundColor: AppColors.primary,
                        child: IconButton(
                          icon: Icon(
                            chatProvider.isLoading
                                ? Icons.hourglass_empty
                                : Icons.send,
                            color: Colors.white,
                            size: 20.sp,
                          ),
                          onPressed: chatProvider.isLoading ? null : _sendMessage,
                        ),
                      );
                    },
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
